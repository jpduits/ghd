<?php

namespace App\Commands;

use Github\Client;
use Github\AuthMethod;
use Github\ResultPager;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class SearchRepositories extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'search:repositories {language} {stars} {count} {dateFrom} {public=true} {archived=false}';

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
        // https://github.com/KnpLabs/php-github-api/tree/master/doc

        $language = $this->argument('language');
        $stars = $this->argument('stars');
        $dateFrom = $this->argument('dateFrom');
        $public = $this->argument('public');
        $archived = $this->argument('archived');
        $count = $this->argument('count');

        if ($count > 100) {
            $this->error('Count cannot exceed 100');
            return 1;
        }

        // https://docs.github.com/en/search-github/searching-on-github/searching-for-repositories
        // https://docs.github.com/en/search-github/getting-started-with-searching-on-github/understanding-the-search-syntax
        // filter: 	https://docs.github.com/en/search-github/searching-on-github/searching-for-repositories
        // per page 	https://docs.github.com/en/rest/repos/repos?apiVersion=2022-11-28



        $client = new Client();
        $client->authenticate(env('GITHUB_TOKEN'), null, AuthMethod::ACCESS_TOKEN);

        $searchLimit = $client->api('rate_limit')->getResource('search')->getLimit();
        $this->info('Search-limit: '.$searchLimit);

        $coreLimit = $client->api('rate_limit')->getResource('core')->getLimit();
        $remaining = $client->api('rate_limit')->getResource('core')->getRemaining();
        $reset = $client->api('rate_limit')->getResource('core')->getReset();
        $this->info('Core-limit: '.$coreLimit);
        $this->info('Remaining: '.$remaining);
        $this->info('Reset: '.$reset);

        $q = "language:{$language} created:<={$dateFrom} archived:{$archived} stars:{$stars}";
        $q .= ($public) ? ' is:public' : '';
        $q .= '&sort=id';

        $search = $client->api('search');

        $paginator = new ResultPager($client, $count);
        $params = [
            $q,

        ];
        $result = $paginator->fetch($search, 'repositories', $params);

        if (isset($result['items'])) {

            foreach ($result['items'] as $repo) {

                // get all details from specific repo
                $repoDetails = $client->api('repo')->showById($repo['id']);
                // get the issues from the current repo (to get the total count)
                $q = "repo:{$repoDetails['owner']['login']}/{$repoDetails['name']} is:issue";

                $repoIssues = $client->api('search')->issues($q);

                $this->line(
                    $stars.
                    "\t".$repoDetails['html_url'].
                    "\t".$repoDetails['owner']['login'].
                    "\t".$repoDetails['name'].
                    "\t".$repoDetails['full_name'].
                    "\t".$repoDetails['id'].
                    "\t".$repoDetails['created_at'].
                    "\t".$repoDetails['stargazers_count']. //stars
                    "\t".$repoDetails['subscribers_count']. //watch
                    "\t".$repoDetails['forks_count']. // forks
                    "\t".$repoIssues['total_count']. // total issues
                    "\t".$repoDetails['open_issues']. // open issues
                    "\t".$repoDetails['default_branch'] );

            }

        }

        return 0;
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
