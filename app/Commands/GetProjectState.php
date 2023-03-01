<?php

namespace App\Commands;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Repository;
use Illuminate\Support\Str;
use App\Models\ProjectState;
use App\Metrics\StickyMetric;
use App\Metrics\MagnetMetric;
use Illuminate\Support\Facades\Validator;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Console\Helper\Table;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

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


    public function __construct(StickyMetric $stickyMetric, MagnetMetric $magnetMetric)
    {
        parent::__construct();
        $this->stickyMetric = $stickyMetric;
        $this->magnetMetric = $magnetMetric;
    }
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
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
        $owner = $input['owner'];
        $repository = $input['repository'];
        $fullName = $input['owner'].'/'.$input['repository'];

        $repository = Repository::where('full_name', '=', $fullName)->first();
        if ($repository instanceof Repository) {
            // start parsing dataset
            $this->line('Repository '.$fullName.' found in the dataset (ID: '.$repository->id.')');


            $stickyMeasurements = $this->stickyMetric->get($repository, $startDate->copy(), $interval, clone($endDate)); // use clone because nullable
            $magnetMeasurements = $this->magnetMetric->get($repository, $startDate->copy(), $interval, clone($endDate));

            // merge these arrays
            $measurements = array_reduce($stickyMeasurements, function($result, $item) use ($magnetMeasurements) {
                $key = array_search($item['period_start_date'], array_column($magnetMeasurements, 'period_start_date'));
                $key2 = array_search($item['period_end_date'], array_column($magnetMeasurements, 'period_end_date'));
                $key3 = array_search($item['previous_period_start_date'], array_column($magnetMeasurements, 'previous_period_start_date'));
                $key4 = array_search($item['previous_period_end_date'], array_column($magnetMeasurements, 'previous_period_end_date'));

                if ($key !== false && $key2 !== false && $key3 !== false && $key4 !== false) {
                    $result[] = array_merge($item, $magnetMeasurements[$key], $magnetMeasurements[$key2], $magnetMeasurements[$key3], $magnetMeasurements[$key4]);
                } else {
                    $result[] = $item;
                }

                return $result;
            }, []);


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
                        'developers_total' => $measurement['developers_total'],
                        'developers_new' => $measurement['developers_new'],
                        'developers_current' => $measurement['developers_current'],
                        'developers_with_contributions_previous_period' => $measurement['developers_with_contributions_previous_period'],
                        'developers_with_contributions_previous_and_current_period' => $measurement['developers_with_contributions_previous_and_current_period'],
                        /* 'issues_count_new',
                         'issues_count_total',
                         'stargazers_count_new',
                         'stargazers_count_total',
                         'pull_requests_count_new',
                         'pull_requests_count_total',
                         'forks_count_new',
                         'forks_count_total'*/
                    ]


            );
                $projectState->save();




          /*  $projectState->uuid = $this->uuid;
            $projectState->repository_id = $repository->id;
            $projectState->start_date = $startDate;
            $projectState->end_date = $endDate;
            $projectState->interval = $interval;*/






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
            'run_uuid',
            'repository_id',
            'period_start_date',
            'period_end_date',
            'previous_period_start_date',
            'previous_period_end_date',
            'interval_weeks',
            'sticky_metric_score',
            'magnet_metric_score',
            'developers_total',
            'developers_new',
            'developers_current',
            'developers_with_contributions_previous_period',
            'developers_with_contributions_previous_and_current_period',
            'issues_count_new',
            'issues_count_total',
            'stargazers_count_new',
            'stargazers_count_total',
            'pull_requests_count_new',
            'pull_requests_count_total',
            'forks_count_new',
            'forks_count_total'
        ]);

        foreach ($measurements as $measurement) {
var_dump($measurement);
            $table->addRow([
                $measurement->run_uuid,
                $measurement->repository_id,
                $measurement->period_start_date->format('Y-m-d'),
                $measurement->period_end_date,
                $measurement->previous_period_start_date,
                $measurement->previous_period_end_date,
                $measurement->interval_weeks,
                $measurement->sticky_metric_score,
                $measurement->magnet_metric_score,
                $measurement->developers_total,
                $measurement->developers_new,
                $measurement->developers_current,
                $measurement->developers_with_contributions_previous_period,
                $measurement->developers_with_contributions_previous_and_current_period,
                $measurement->issues_count_new,
                $measurement->issues_count_total,
                $measurement->stargazers_count_new,
                $measurement->stargazers_count_total,
                $measurement->pull_requests_count_new,
                $measurement->pull_requests_count_total,
                $measurement->forks_count_new,
                $measurement->forks_count_total

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
