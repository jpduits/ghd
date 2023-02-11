<?php

namespace App\Commands;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Repository;
use Illuminate\Support\Facades\Validator;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
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
    private Carbon $endDate;
    private int $interval = 26; // default half a year
    private string $owner = '';
    private string $repository = '';
    private string $fullName = '';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $input = array_merge($this->arguments(), $this->options());
        $this->validate($input);


        $this->outputFormat = $input['output-format'];
        $this->startDate = Carbon::createFromFormat( 'Y-m-d', $input['start-date'])->startOfDay();
        $this->endDate = (Carbon::createFromFormat( 'Y-m-d', $input['end-date'])->startOfDay()) ?: Carbon::now()->startOfDay();
        $this->interval = $input['interval'];
        $this->owner = $input['owner'];
        $this->repository = $input['repository'];
        $this->fullName = $input['owner'].'/'.$input['repository'];

        $repository = Repository::where('full_name', '=', $this->fullName)->first();
        if ($repository instanceof Repository) {
            // start state check
            $this->line($this->fullName.' found in the dataset');
            // start calculation, first magnet

            $this->getMagnet($repository);

            $this->getSticky($repository);

        }
        else {
            $this->error($this->fullName.' does not exist in the dataset');
            exit(1);
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

    public function getSticky(Repository $repository)
    {
        $startRangeDate = clone($this->startDate); // period Pi


        if ($this->interval) {
            $endRangeDate = $startRangeDate->copy()->addWeeks($this->interval);
        }
        else {
            $endRangeDate = $this->endDate;
        }


        while (true) {

            $beforeStartRange = $startRangeDate->copy()->subWeeks($this->interval); // period Pi-1
            // get all unique users from the commits of the repository within the Pi-1 date range
            $contributingDevelopersIdsPreviousPeriod = $repository->commits()
                                           ->where('created_at', '>=', $beforeStartRange)
                                           ->where('created_at', '<', $startRangeDate)
                                           ->select('author_id')
                                           ->distinct()
                                           ->get();

            // get all unique developers from the Pi period that also contributed in Pi-1
            $contributingDevelopersIdsPeriod = $repository->commits()
                                           ->where('created_at', '>=', $startRangeDate)
                                           ->where('created_at', '<', $endRangeDate)
                                           ->whereIn('author_id', $contributingDevelopersIdsPreviousPeriod)
                                           ->select('author_id')
                                           ->distinct()
                                           ->get();

            $sticky = $contributingDevelopersIdsPeriod->count() / $contributingDevelopersIdsPreviousPeriod->count();

            $this->line('Sticky value between '.$startRangeDate->format('Y-m-d').' and '.$endRangeDate->format('Y-m-d').': '.$contributingDevelopersIdsPeriod->count().' / '.$contributingDevelopersIdsPreviousPeriod->count().' = '.$sticky);

            if ($this->interval) {
                if (($endRangeDate->gt($this->endDate)) || ($endRangeDate->gt(Carbon::now()))) {
                    break;
                }

                $startRangeDate->addWeeks($this->interval);
                $endRangeDate->addWeeks($this->interval);
            }
            else {
                break;
            }

        }


    }


    public function getMagnet(Repository $repository)
    {

        $startRangeDate = clone($this->startDate);

        if ($this->interval) {
            $endRangeDate = $startRangeDate->copy()->addWeeks($this->interval);
        }
        else {
            $endRangeDate = $this->endDate;
        }


        while (true) {

            // get all unique users before the current range
            $beforeUniqueUserIds = $repository->commits()
                                        ->where('created_at', '<', $startRangeDate)
                                        ->select('author_id')
                                        ->distinct()
                                        ->get();

            // get all unique users from the commits of the repository within the date range
            $uniqueNewUserIds = $repository->commits()
                                        ->where('created_at', '>=', $startRangeDate)
                                        ->where('created_at', '<', $endRangeDate)
                                        ->select('author_id')
                                        ->whereNotIn('author_id', $beforeUniqueUserIds)
                                        ->distinct()
                                        ->get();

           // $this->line(implode(', ', $uniqueNewUserIds->pluck('author_id')->toArray()));

            //$this->line('Number of unique users: '.$uniqueUserIds->count());


            $totalUniqueUserIds = $repository->commits()
                                             ->where('created_at', '<', $endRangeDate)
                                             ->select('author_id')
                                             ->distinct()
                                             ->get();

            //$this->line('Number of total unique users until '.$this->endDate.': '.$totalUniqueUserIds->count());

            // calculate the magnet value of Yamashita
            $magnet = $uniqueNewUserIds->count() / $totalUniqueUserIds->count();

            $this->line('Magnet value between '.$startRangeDate->format('Y-m-d').' and '.$endRangeDate->format('Y-m-d').': '.$uniqueNewUserIds->count().' / '.$totalUniqueUserIds->count().' = '.$magnet);


            if ($this->interval) {
                if (($endRangeDate->gt($this->endDate)) || ($endRangeDate->gt(Carbon::now()))) {
                    break;
                }

                $startRangeDate->addWeeks($this->interval);
                $endRangeDate->addWeeks($this->interval);
            }
            else {
                break;
            }
        }

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
