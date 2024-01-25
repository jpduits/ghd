<?php

namespace App\Commands;

use Carbon\Carbon;
use App\Models\Repository;
use Illuminate\Support\Str;
use App\Models\ProjectState;
use App\QualityModel\Community;
use App\QualityModel\SourceCode;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Console\Helper\Table;
use Illuminate\Support\Arr;
use App\QualityModel\Metrics\Community\GithubMeta;
use Symfony\Component\Console\Output\ConsoleOutput;
use App\QualityModel\Metrics\Yamashita\StickyMetric;
use App\QualityModel\Metrics\Yamashita\MagnetMetric;

class GetProjectState extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = "get:project-state {owner} {repository}
                            {--run-uuid= : Run ID (UUID)}
                            {--start-date= : Starting date (YYYY-MM-DD)}
                            {--end-date= : End date (YYYY-MM-DD), default today}
                            {--interval=26 : Interval (week(s), default 26)}
                            {--output-format=cli : Output format (json, csv or cli)}
                        ";

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Get the state of a GitHub project in the dataset';

    private string $outputFormat = 'cli';
    private Carbon $startDate;
    private ?Carbon $endDate = null;
    private int $interval = 26; // default half a year
    private string $owner = '';
    private string $repository = '';
    private string $fullName = '';
    private StickyMetric $stickyMetric;
    private MagnetMetric $magnetMetric;
    private GithubMeta $githubMeta;
    private string $checkoutDir;
    private SourceCode $sourceCode;
    private Community $community;

    private string $runId = '';


    public function __construct(Community $community, SourceCode $sourceCode)
    {
        parent::__construct();

        $this->checkoutDir = env('GITHUB_TMP_CHECKOUT_DIR');

        $this->sourceCode = $sourceCode;
        $this->community = $community;
    }
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        if (!file_exists($this->checkoutDir)) {
            $this->error('Temporary checkout directory does not exist: '.$this->checkoutDir);
            exit(1);
        }

        // validate input arguments
        $input = array_merge($this->arguments(), $this->options());
        $this->validate($input);

        $this->outputFormat = $input['output-format'];

        $this->runUuid = $input['run-uuid'];

        $startDate = Carbon::createFromFormat( 'Y-m-d', $input['start-date'])->startOfDay();

        $endDate = null;
        if ($input['end-date']) {
            $endDate = (Carbon::createFromFormat('Y-m-d', $input['end-date'])->startOfDay()) ?: Carbon::now()->startOfDay();
        }

        $interval = $input['interval'];

        // repository
/*        $ownerName = $input['owner'];
        $repositoryName = $input['repository'];*/
        $fullName = $input['owner'].'/'.$input['repository'];

        $repository = Repository::where('full_name', '=', $fullName)->first();
        if ($repository instanceof Repository) {
            // start parsing dataset
            $this->line('Repository '.$fullName.' found in the dataset (ID: '.$repository->id.')');


            $sourceCode = $this->sourceCode->get($repository, $startDate->copy(), $interval, $endDate);
            $community = $this->community->get($repository, $startDate->copy(), $interval, $endDate);

            // add quality measurements to the array
            $measurements = array_map(function($item1, $item2) {
                // Controleer of period_start_date en period_end_date al aanwezig zijn in $item1
                // Zo niet, voeg ze toe vanuit $item2
                if (!isset($item1['period_start_date'])) {
                    $item1['period_start_date'] = $item2['period_start_date'];
                }
                if (!isset($item1['period_end_date'])) {
                    $item1['period_end_date'] = $item2['period_end_date'];
                }

                return array_merge($item1, $item2);
            }, $community, $sourceCode);

            // store results in database
            foreach($measurements as $key => $measurement) {
                $measurement = array_merge($measurement, [
                    'run_uuid' => $this->runUuid,
                    'full_name' => $fullName, // 'owner/repository
                    'repository_id' => $repository->id,
                    'interval' => $interval,
                ]);
                $measurements[$key] = $measurement;
                $this->storeMeasurement($measurement);
            }

        }
        else {
            $this->error('Repository '.$this->fullName.' does not exist in the dataset');
            exit(1);
        }


        if ($input['output-format'] == 'cli') {

            $table = new Table(new ConsoleOutput);
            $table->addRow(array_keys($measurements[0]));
            foreach ($measurements as $measurement) {
                $table->addRow($measurement);
            }
            $table->render();

        }
        else if (($input['output-format'] == 'json') || ($input['output-format'] == 'csv')) {

            $stateCollection = ProjectState::where('run_uuid', '=', $this->runUuid)->get(); // get all measurements with the selected run_uuid
            $stateCollection = $stateCollection->map(function($state) { $state->full_name = $state->repository->full_name; return $state; });

            $stateCollection->each(function ($item) {
                // hide relation for output
                $item->makeHidden('repository');
            });


            // add fullname as first item
            $this->newLine();
            foreach ($stateCollection as $state) {

                foreach ($state->toArray() as $key => $value) {
                    if (!is_array($value)) {
                        $this->line('- ' . $key . ': ' . $value);
                    }
                }
                $this->line('---');
                $this->newLine();
            }

            if ($input['output-format'] == 'json') {
                $fileName = time().'output__' . $this->runUuid . '__' . Carbon::now()->format('Y-m-d__H:i') . '.json';
                $content = $stateCollection->toJson();

            }
            elseif ($input['output-format'] == 'csv') {
                $fileName = time().'output__' . $this->runUuid . '__' . Carbon::now()->format('Y-m-d__H:i') . '.csv';
                // add headers
                $data = $stateCollection->toArray();
                unset($data['repository']);

                $fullNameRepositoryRange = $data[0]['full_name']; // create new line between repo's
                $data = Arr::prepend($data, array_keys($data[0])); // add the headers as first row

                $content = '';
                foreach ($data as $line) {

                    if ((isset($line['full_name'])) && ($line['full_name'] != $fullNameRepositoryRange)) {
                        $content .= PHP_EOL;
                        $fullNameRepositoryRange = $line['full_name'];
                    }
                    $content .= implode("\t", $line).PHP_EOL;
                }

            }

            if ($fileName && $content) {
                $this->line('Results saving to: '.$fileName);
                Storage::disk('local')->put($fileName, $content);

            }

        }

        exit(0);
    }

    /**
     * Define the command's schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }


    public function validate(array $input)
    {


        $rules = [
            'run-uuid' => 'required|uuid',
            'start-date' => 'required|date_format:Y-m-d',
            'end-date' => 'nullable|date_format:Y-m-d',
            'interval' => 'nullable|integer',
            'output-format' => 'required|in:csv,json,cli',
        ];

        $validator = Validator::make($input, $rules);

        if ($validator->fails()) {

            foreach ($validator->errors()->all() as $error) {
                $this->error("\033[0;31mError: {$error}\033[0m");

            }

            exit(1);
        }

/*        if ($this->option('interval') !== null && $this->option('end-date') !== null) {
            $this->error("Only one of 'end-date' and 'interval' can be provided.");
            exit(1);
        }*/
    }

    private function storeMeasurement(array $measurement)
    {

        $projectState = new ProjectState(
            [
                'run_uuid' => $this->runUuid,
                'repository_id' => $measurement['repository_id'],

                'period_start_date' => $measurement['period_start_date'],
                'period_end_date' => $measurement['period_end_date'],
                'previous_period_start_date' => $measurement['previous_period_start_date'],
                'previous_period_end_date' => $measurement['previous_period_end_date'],
                'interval_weeks' => $measurement['interval'],
                'sticky_metric_score' => $measurement['sticky_value'],
                'magnet_metric_score' => $measurement['magnet_value'],
                'quadrant' => $measurement['quadrant'],

                'developers_new_current_period' => $measurement['developers_new_current_period'] ?? 0,
                'developers_current_period' => $measurement['developers_current_period'] ?? 0,
                'developers_total' => $measurement['developers_total'] ?? 0,

                'developers_with_contributions_previous_period' => $measurement['developers_with_contributions_previous_period'] ?? 0,
                'developers_with_contributions_current_period' => $measurement['developers_with_contributions_current_period'] ?? 0,
                'developers_with_contributions_previous_and_current_period' => $measurement['developers_with_contributions_previous_and_current_period'] ?? 0,

                'issues_count_current_period' => $measurement['issues_count_current_period'] ?? 0,
                'issues_count_total' => $measurement['issues_count_total'] ?? 0,
                'stargazers_count_current_period' => $measurement['stargazers_count_current_period'] ?? 0,
                'stargazers_count_total' => $measurement['stargazers_count_total'] ?? 0,
                'pull_requests_count_current_period' => $measurement['pull_requests_count_current_period'] ?? 0,
                'pull_requests_count_total' => $measurement['pull_requests_count_total'] ?? 0,
                'forks_count_current_period' => $measurement['forks_count_current_period'] ?? 0,
                'forks_count_total' => $measurement['forks_count_total'] ?? 0,
                'bugs_current_period' => $measurement['bugs_current_period'] ?? 0,
                'bugs_total' => $measurement['bugs_total'] ?? 0,
                'bugs_closed_current_period' => $measurement['bugs_closed_current_period'] ?? 0,
                'bugs_closed_total' => $measurement['bugs_closed_total'] ?? 0,
                'support_current_period' => $measurement['support_current_period'] ?? 0,
                'support_total' => $measurement['support_total'] ?? 0,
                'support_closed_current_period' => $measurement['support_closed_current_period'] ?? 0,
                'support_closed_total' => $measurement['support_closed_total'] ?? 0,
                'total_loc' => $measurement['total_loc'] ?? 0,
                'total_kloc' => $measurement['total_kloc'] ?? 0,
                'total_lines' => $measurement['total_lines'] ?? 0,
                'sig_volume_ranking' => $measurement['sig_volume_ranking'] ?? null,
                'sig_volume_ranking_numeric' => $measurement['sig_volume_ranking_numeric'] ?? null,
                'loc_complexity_per_risk' => $measurement['loc_complexity_per_risk'] ?? '',
                'percentage_complexity_per_risk' => $measurement['percentage_complexity_per_risk'] ?? '',
                'loc_unit_size_per_risk' => $measurement['loc_unit_size_per_risk'] ?? '',
                'percentage_unit_size_per_risk' => $measurement['percentage_unit_size_per_risk'] ?? '',
                'sig_complexity_ranking' => $measurement['sig_complexity_ranking'] ?? null,
                'sig_complexity_ranking_value' => $measurement['sig_complexity_ranking_value'] ?? null,
                'sig_unit_size_ranking' => $measurement['sig_unit_size_ranking'] ?? null,
                'sig_unit_size_ranking_value' => $measurement['sig_unit_size_ranking_value'] ?? null,
                'duplication_line_count' => $measurement['duplication_line_count'] ?? 0,
                'duplication_block_count' => $measurement['duplication_block_count'] ?? 0,
                'duplication_percentage' => $measurement['duplication_percentage'] ?? 0,
                'sig_duplication_ranking' => $measurement['sig_duplication_ranking'] ?? null,
                'sig_duplication_ranking_numeric' => $measurement['sig_duplication_ranking_numeric'] ?? null,
                'sig_analysability_ranking' => $measurement['sig_analysability_ranking'] ?? null,
                'sig_analysability_ranking_numeric' => $measurement['sig_analysability_ranking_numeric'] ?? null,
                'sig_changeability_ranking' => $measurement['sig_changeability_ranking'] ?? null,
                'sig_changeability_ranking_numeric' => $measurement['sig_changeability_ranking_numeric'] ?? null,
                'sig_testability_ranking' => $measurement['sig_testability_ranking'] ?? null,
                'sig_testability_ranking_numeric' => $measurement['sig_testability_ranking_numeric'] ?? null,
                'checkout_sha' => $measurement['checkout_sha'] ?? 'NOT FOUND',
                'issues_average_duration_days' => $measurement['issues_average_duration_days']  ?? 0,
                'comments_total' => $measurement['comments_total']  ?? 0,
                'comments_relevant_percentage' => $measurement['comments_relevant_percentage']  ?? 0,
                'comments_relevant' => $measurement['comments_relevant'] ?? 0,
                'comments_copyright' => $measurement['comments_copyright'] ?? 0,
                'comments_auxiliary' => $measurement['comments_auxiliary'] ?? 0,
                'comments_relevant_loc' => $measurement['comments_relevant_loc'] ?? 0,
                'comments_copyright_loc' => $measurement['comments_copyright_loc'] ?? 0,
                'comments_auxiliary_loc' => $measurement['comments_auxiliary_loc'] ?? 0,
                'comments_loc' => $measurement['comments_loc'] ?? 0,
                'comments_percentage' => $measurement['comments_percentage'] ?? 0,
                'sig_comments_ranking' => $measurement['sig_comments_ranking'] ?? null,
                'sig_comments_ranking_numeric' => $measurement['sig_comments_ranking_numeric'] ?? null
            ]);
        $projectState->save();

    }
}
