<?php

namespace App\Commands;

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
                            {--output=cli : Output format (json, csv or cli)}
                        ";

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Get the state of a GitHub project in the dataset';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $input = array_merge($this->arguments(), $this->options());
        $this->validate($input);

        $fullName = $input['owner'].'/'.$input['repository'];
        $repository = Repository::where('full_name', '=', $fullName)->first();
        if ($repository instanceof Repository) {
            // start state check
            $this->line($fullName.' found in the dataset');
            // start calculation, first magnet


        }
        else {
            $this->error($fullName.' does not exist in the dataset');
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

    public function getSticky()
    {

    }

    public function getMagnet()
    {

    }




    public function validate(array $input)
    {


        $rules = [
            'start-date' => 'required|date_format:Y-m-d',
            'end-date' => 'nullable|date_format:Y-m-d',
            'interval' => 'nullable|integer',
            'output' => 'required|in:csv,json,cli',
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
