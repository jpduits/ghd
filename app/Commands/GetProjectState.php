<?php

namespace App\Commands;

use Carbon\Carbon;
use App\Models\Repository;
use Illuminate\Support\Str;
use App\Models\ProjectState;
use App\QualityModel\Community;
use App\QualityModel\Maintainability;
use Illuminate\Support\Facades\Validator;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Console\Helper\Table;
use Illuminate\Database\Eloquent\Collection;
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
                            {--run-id= : Run ID (UUID)}
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
    private string $uuid;
    private GithubMeta $githubMeta;
    private string $checkoutDir;
    private Maintainability $maintainability;
    private Community $community;

    private string $runId = '';


    public function __construct(Community $community, Maintainability $maintainability)
    {
        parent::__construct();

        $this->checkoutDir = env('GITHUB_TMP_CHECKOUT_DIR');

        $this->maintainability = $maintainability;
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

        // generate UUID for this run
        $this->uuid = (string) Str::uuid();
        $this->outputFormat = $input['output-format'];

        $this->runId = $input['run-id'];

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

            $maintainability = $this->maintainability->get($repository, $startDate->copy(), $interval, clone($endDate));
            $community = $this->community->get($repository, $startDate->copy(), $interval, clone($endDate));

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
            }, $community, $maintainability);

            // store results in database
            foreach($measurements as $key => $measurement) {
                $measurement = array_merge([
                    'uuid' => $this->uuid,
                    'full_name' => $fullName, // 'owner/repository
                    'repository_id' => $repository->id,
                    'interval' => $interval,
                ], $measurement);
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
        else if ($input['output-format'] == 'json') {
            $stateCollection = ProjectState::where('run_uuid', '=', $this->uuid)->get();
            echo $stateCollection->toJson();
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

/*
    private function generateTable(Collection $measurements)
    {
        $table = new Table(new ConsoleOutput);

        $table->setHeaders([
            'run', // run_uuid
            'repo id', // repository_id
            'start', // start_date
            'end', // period_end_date
            'prev start', // previous_period_start_date
            'prev end', // previous_period_end_date

            'interval', // interval_weeks
            'sticky', // sticky_metric_score
            'magnet', // magnet_metric_score

            'devs current', // developers_current_period
            'devs new current', // developers_new_current_period
            'devs total', // developers_total

            'devs contrib. prev', // developers_with_contributions_previous_period
            'devs contrib. prev+current', // developers_with_contributions_previous_and_current_period

            'issues current', // issues_count_current_period
            'issues total', // issues_count_total

            'stars current', // stargazers_count_current_period
            'stars total', // stargazers_count_total

            'pull req. current', // pull_requests_count_current_period
            'pull req. total', // pull_requests_count_total

            'forks current', // forks_count_current_period
            'forks total', // forks_count_total

            'checkout_sha'
        ]);

        foreach ($measurements as $measurement) {

            $table->addRow([
                $measurement->run_uuid,
                $measurement->repository_id,
                $measurement->period_start_date->format('Y-m-d'),
                $measurement->period_end_date->format('Y-m-d'),
                $measurement->previous_period_start_date->format('Y-m-d'),
                $measurement->previous_period_end_date->format('Y-m-d'),

                $measurement->interval_weeks,
                $measurement->sticky_metric_score,
                $measurement->magnet_metric_score,

                $measurement->developers_current_period,
                $measurement->developers_new_current_period,
                $measurement->developers_total,


                $measurement->developers_with_contributions_previous_period,
                $measurement->developers_with_contributions_previous_and_current_period,

                $measurement->issues_count_current_period,
                $measurement->issues_count_total,

                $measurement->stargazers_count_current_period,
                $measurement->stargazers_count_total,

                $measurement->pull_requests_count_current_period,
                $measurement->pull_requests_count_total,

                $measurement->forks_count_current_period,
                $measurement->forks_count_total,

                $measurement->checkout_sha,

            ]);
        }
        $table->render();
    }*/



    public function validate(array $input)
    {


        $rules = [
            'run-id' => 'required|uuid',
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
                'run_uuid' => $this->uuid,
                'repository_id' => $measurement['repository_id'],

                'period_start_date' => $measurement['period_start_date'],
                'period_end_date' => $measurement['period_end_date'],
                'previous_period_start_date' => $measurement['previous_period_start_date'],
                'previous_period_end_date' => $measurement['previous_period_end_date'],
                'interval_weeks' => $measurement['interval'],
                'sticky_metric_score' => $measurement['sticky_value'],
                'magnet_metric_score' => $measurement['magnet_value'],
                'quadrant' => $measurement['quadrant'],

                'developers_new_current_period' => $measurement['developers_new_current_period'],
                'developers_current_period' => $measurement['developers_current_period'],
                'developers_total' => $measurement['developers_total'],

                'developers_with_contributions_previous_period' => $measurement['developers_with_contributions_previous_period'],
                'developers_with_contributions_current_period' => $measurement['developers_with_contributions_current_period'],
                'developers_with_contributions_previous_and_current_period' => $measurement['developers_with_contributions_previous_and_current_period'],

                'issues_count_current_period' => $measurement['issues_count_current_period'],
                'issues_count_total' => $measurement['issues_count_total'],
                'stargazers_count_current_period' => $measurement['stargazers_count_current_period'],
                'stargazers_count_total' => $measurement['stargazers_count_total'],
                'pull_requests_count_current_period' => $measurement['pull_requests_count_current_period'],
                'pull_requests_count_total' => $measurement['pull_requests_count_total'],
                'forks_count_current_period' => $measurement['forks_count_current_period'],
                'forks_count_total' => $measurement['forks_count_total'],
                'bugs_current_period' => $measurement['bugs_current_period'],
                'bugs_total' => $measurement['bugs_total'],
                'bugs_closed_current_period' => $measurement['bugs_closed_current_period'],
                'bugs_closed_total' => $measurement['bugs_closed_total'],
                'support_current_period' => $measurement['support_current_period'],
                'support_total' => $measurement['support_total'],
                'support_closed_current_period' => $measurement['support_closed_current_period'],
                'support_closed_total' => $measurement['support_closed_total'],
                'total_loc' => $measurement['total_loc'],
                'total_kloc' => $measurement['total_kloc'],
                'sig_volume_ranking' => $measurement['sig_volume_ranking'],
                'sig_volume_ranking_numeric' => $measurement['sig_volume_ranking_numeric'],
                'loc_complexity_per_risk' => $measurement['loc_complexity_per_risk'],
                'percentage_complexity_per_risk' => $measurement['percentage_complexity_per_risk'],
                'loc_unit_size_per_risk' => $measurement['loc_unit_size_per_risk'],
                'percentage_unit_size_per_risk' => $measurement['percentage_unit_size_per_risk'],
                'sig_complexity_ranking' => $measurement['sig_complexity_ranking'],
                'sig_complexity_ranking_value' => $measurement['sig_complexity_ranking_value'],
                'sig_unit_size_ranking' => $measurement['sig_unit_size_ranking'],
                'sig_unit_size_ranking_value' => $measurement['sig_unit_size_ranking_value'],
                'duplication_line_count' => $measurement['duplication_line_count'],
                'duplication_block_count' => $measurement['duplication_block_count'],
                'duplication_percentage' => $measurement['duplication_percentage'],
                'sig_duplication_ranking' => $measurement['sig_duplication_ranking'],
                'sig_duplication_ranking_numeric' => $measurement['sig_duplication_ranking_numeric'],
                'sig_analysability_ranking' => $measurement['sig_analysability_ranking'],
                'sig_analysability_ranking_numeric' => $measurement['sig_analysability_ranking_numeric'],
                'sig_changeability_ranking' => $measurement['sig_changeability_ranking'],
                'sig_changeability_ranking_numeric' => $measurement['sig_changeability_ranking_numeric'],
                'sig_testability_ranking' => $measurement['sig_testability_ranking'],
                'sig_testability_ranking_numeric' => $measurement['sig_testability_ranking_numeric'],
                'checkout_sha' => $measurement['checkout_sha']
            ]);
        $projectState->save();

    }




}
