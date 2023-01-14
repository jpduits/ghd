<?php

// https://github.com/KnpLabs/php-github-api/tree/master/doc

namespace App\Commands;

use Github\Client;
use Github\AuthMethod;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class SearchRepositories extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'search:repositories {language=java} {stars} {count} {dateFrom} {public=true} {archived=false}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Search filtered repositories from GitHub for composing a dataset';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $language = $this->argument('language');
        $stars = $this->argument('stars');
        $dateFrom = $this->argument('dateFrom');

        $client = new Client();
        $client->authenticate(env('GITHUB_TOKEN'), null, AuthMethod::ACCESS_TOKEN);

        $repos = $client->api('search')->repositories('language:java created:<'.$dateFrom.' is:public archived:false stars:'.$stars);
        print_r($repos);
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
}
