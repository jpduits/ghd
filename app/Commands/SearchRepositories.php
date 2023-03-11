<?php

namespace App\Commands;

use Github\Client;
use Carbon\Carbon;
use Github\AuthMethod;
use Github\ResultPager;
use TiagoHillebrandt\ParseLinkHeader;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use Github\HttpClient\Message\ResponseMediator;

class SearchRepositories extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'search:repositories {language} {stars} {dateFrom} {count=-1} {public=true} {archived=false}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Search filtered repositories from GitHub for composing a dataset';
    private Client $client;


    public function __construct(Client $client)
    {
        parent::__construct();
        $this->client = $client;
    }

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

/*        if ($count > 30) {
            $this->error('Search cannot exceed 30');
            return 1;
        }*/

        // searching: https://docs.github.com/en/search-github/getting-started-with-searching-on-github/understanding-the-search-syntax
        // filter: https://docs.github.com/en/search-github/searching-on-github/searching-for-repositories
        // per page: https://docs.github.com/en/rest/repos/repos?apiVersion=2022-11-28
        // sorting: https://docs.github.com/en/search-github/getting-started-with-searching-on-github/sorting-search-results

        $page = 1;
        $lastPage = 1;

        $this->info('Search '.$count.' '.$language.' repositories with  '.$stars.' stars, from: '.$dateFrom);

        $lastYear = Carbon::now()->subYear()->format('Y-m-d');
        $q  = "language:{$language} created:<={$dateFrom} pushed:>={$lastYear} archived:{$archived} stars:{$stars}";
        //$q .= " size:<=102400"; // max 100MB
        $q .= " size:<=51200"; // max 50MB
        $q .= ($public) ? ' is:public' : '';

        $this->info('Query: '.$q);

        $uri = 'https://api.github.com/search/repositories?q=' . $q . '&s=stars&o=desc&page='.$page;

        $repoCounter = 0;
        $done = false;

        while (true) {


            $response = $this->client->getHttpClient()->get($uri);

            $headers = $response->getHeaders();
            if (isset($headers['Link'][0] )) {
                $links = (new ParseLinkHeader($headers['Link'][0]))->toArray();
                $lastPage = $links['last']['page'] ?? $lastPage;
            }

            $repositories = ResponseMediator::getContent($response);

            if ($page == 1) {
                $this->info('Total count: ' . $repositories['total_count']);
            }

            $this->info('Get page ' . $page . ' of ' . $lastPage);

            if (isset($repositories['items'])) {

                foreach ($repositories['items'] as $repoDetails) {

                    if (($count != -1) && ($repoCounter >= $count)) { // -1 is no count limit
                        $done = true;
                        break;
                    }

                    $this->line(
                        $stars .
                        "\t" . $repoDetails['size'] . ' = ' . round($repoDetails['size'] / 1024) . 'MB' .
                        "\t" . $repoDetails['id'] .

                        "\t" . $repoDetails['full_name'] .
                        "\t" . $repoDetails['owner']['login'] .
                        "\t" . $repoDetails['name'] .
                        "\t" . $repoDetails['default_branch'].
                        "\t" . $repoDetails['html_url'] .

                        "\t" .Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $repoDetails['created_at'])->toDateTimeString().
                        "\t" .Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $repoDetails['updated_at'])->toDateTimeString().
                        "\t" . $repoDetails['stargazers_count']. //stargazers
                        "\t" . $repoDetails['forks_count'] . // forks
                        "\t" . $repoDetails['open_issues']  // open issues
                    );
                    $repoCounter++;
                }


            }

            // no next page, break from while
            if ((!isset($links['next'])) || ($done)) {
                break;
            }

            // else get next page
            $page++;
            $uri = $links['next']['link'];


        } // while

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
