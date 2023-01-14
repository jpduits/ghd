<?php

namespace App\Commands;

use Github\Client;
use Github\AuthMethod;
use Illuminate\Support\Facades\Http;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class GetRepository extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'get:repository {owner} {repository}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Get all data from a specific repository from GitHub';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $owner = $this->argument('owner');
        $repository = $this->argument('repository');

/*        $baseUrl = 'https://api.github.com';

        $response = Http::withHeaders([
            'Authorization' => env('GITHUB_TOKEN'),
            'Accept' => 'application/vnd.github+json'
        ])->get($baseUrl.'/repos/konmik/nucleus');*/


        $client = new Client();
        var_dump(env('GITHUB_TOKEN'));
        $client->authenticate(env('GITHUB_TOKEN'), null, AuthMethod::ACCESS_TOKEN);

        $repo = $client->api('repo')->show($owner, $repository);
        //print_r($repo);
        $this->info('Getting all commits from branch: '. $repo['default_branch']);

        $commits = $client->api('repo')->commits()->all($owner, $repository, ['sha' => $repo['default_branch']]);


        foreach ($commits as $commit) {
            $this->info($commit['sha']);
            $this->info($commit['commit']['author']['email']);
            $this->info($commit['commit']['message']);
            $this->info($commit['author']['id'] ?? '') ;
            $this->info($commit['author']['login'] ?? '');
            $this->line('+++');


        }


        print_r($commits);


//        var_dump(json_decode($response->body()));

        //var_dump($response->headers());

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
