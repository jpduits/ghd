<?php

namespace App\Commands;

use Carbon\Carbon;
use App\Models\Repository;
use Illuminate\Support\Str;
use App\Models\ProjectState;
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


    public function __construct(StickyMetric $stickyMetric, MagnetMetric $magnetMetric, GithubMeta $githubMeta, Maintainability $maintainability)
    {
        parent::__construct();

        $this->checkoutDir = env('GITHUB_TMP_CHECKOUT_DIR');

        $this->stickyMetric = $stickyMetric;
        $this->magnetMetric = $magnetMetric;
        $this->githubMeta = $githubMeta;
        $this->maintainability = $maintainability;
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

//            $qualityMeasurements = $this->qualityMetric->get($repository, $startDate->copy(), $interval, clone($endDate));

            $maintainability = $this->maintainability->get($repository, $startDate->copy(), $interval, clone($endDate));
            dd($maintainability);

exit(1);
            $stickyMeasurements = $this->stickyMetric->get($repository, $startDate->copy(), $interval, clone($endDate)); // use clone because nullable
            $magnetMeasurements = $this->magnetMetric->get($repository, $startDate->copy(), $interval, clone($endDate));
            $gitHubMeta = $this->githubMeta->get($repository, $startDate->copy(), $interval, clone($endDate));

            // merge these arrays, dates and prev dats
            $measurements = array_reduce([$stickyMeasurements, $magnetMeasurements, $gitHubMeta], function($result, $current) {
                foreach ($current as $item) {
                    $key = array_search($item['period_start_date'], array_column($result, 'period_start_date'));
                    $key2 = array_search($item['period_end_date'], array_column($result, 'period_end_date'));
                    $key3 = array_search($item['previous_period_start_date'], array_column($result, 'previous_period_start_date'));
                    $key4 = array_search($item['previous_period_end_date'], array_column($result, 'previous_period_end_date'));

                    if ($key !== false && $key2 !== false && $key3 !== false && $key4 !== false) {
                        // if all keys exist, merge the items
                        $result[$key] = array_merge($result[$key], $item);
                    } else {
                        // otherwise add the item to the result array
                        $result[] = $item;
                    }
                }

                return $result;
            }, []);


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
            }, $measurements, $qualityMeasurements);


            foreach($measurements as $measurement) {


                $projectState = new ProjectState(
                    [
                        'run_uuid' => $this->uuid,
                        'repository_id' => $repository['id'],
                        'period_start_date' => $measurement['period_start_date'],
                        'period_end_date' => $measurement['period_end_date'],
                        'previous_period_start_date' => $measurement['previous_period_start_date'],
                        'previous_period_end_date' => $measurement['previous_period_end_date'],
                        'interval_weeks' => $interval,
                        'sticky_metric_score' => $measurement['sticky_value'],
                        'magnet_metric_score' => $measurement['magnet_value'],

                        'developers_new_current_period' => $measurement['developers_new_current_period'],
                        'developers_current_period' => $measurement['developers_current_period'],
                        'developers_total' => $measurement['developers_total'],

                        'developers_with_contributions_previous_period' => $measurement['developers_with_contributions_previous_period'],
                        'developers_with_contributions_previous_and_current_period' => $measurement['developers_with_contributions_previous_and_current_period'],

                        'issues_count_current_period' => $measurement['issues_count_current_period'],
                        'issues_count_total' => $measurement['issues_count_total'],
                        'stargazers_count_current_period' => $measurement['stargazers_count_current_period'],
                        'stargazers_count_total' => $measurement['stargazers_count_total'],
                        'pull_requests_count_current_period' => $measurement['pull_requests_count_current_period'],
                        'pull_requests_count_total' => $measurement['pull_requests_count_total'],
                        'forks_count_current_period' => $measurement['forks_count_current_period'],
                        'forks_count_total' => $measurement['forks_count_total'],

                        'checkout_sha' => $measurement['checkout_sha'],
                    ]


            );
                $projectState->save();

            }

        }
        else {
            $this->error('Repository '.$this->fullName.' does not exist in the dataset');
            exit(1);
        }

        $stateCollection = ProjectState::where('run_uuid', '=', $this->uuid)->get();
        if ($input['output-format'] == 'cli') {
           $this->generateTable($stateCollection);

        }
        else if ($input['output-format'] == 'json') {
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
    }



    public function validate(array $input)
    {


        $rules = [
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




}
