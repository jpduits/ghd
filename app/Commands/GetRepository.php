<?php

namespace App\Commands;

use Github\Client;
use App\Parsers\IssueParser;
use App\Parsers\CommitParser;
use App\Parsers\StargazerParser;
use App\Parsers\RepositoryParser;
use App\Parsers\PullRequestParser;
use App\Parsers\ContributorParser;
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

    private RepositoryParser $repositoryParser;
    private CommitParser $commitParser;
    private StargazerParser $stargazerParser;
    private IssueParser $issueParser;
    private PullRequestParser $pullRequestParser;
    private ContributorParser $contributorParser;


    public function __construct(Client $client, RepositoryParser $repositoryParser, CommitParser $commitParser,
                                StargazerParser $stargazerParser, IssueParser $issueParser,
                                PullRequestParser $pullRequestParser, ContributorParser $contributorParser)
    {
        parent::__construct();
        $this->repositoryParser = $repositoryParser;
        $this->commitParser = $commitParser;
        $this->stargazerParser = $stargazerParser;
        $this->issueParser = $issueParser;
        $this->pullRequestParser = $pullRequestParser;
        $this->contributorParser = $contributorParser;
    }
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $_owner = $this->argument('owner');
        $_repository = $this->argument('repository');

        // load or store repository if not exists
        $repository = $this->repositoryParser->repositoryExistsOrCreate($_owner, $_repository);

        // get users from selected repository
        $this->contributorParser->getContributors($repository);

        // get commits from selected repository
        $this->commitParser->getCommits($repository);

        // stars
        $this->stargazerParser->getStargazers($repository);

        // issues
        $this->issueParser->getIssues($repository);

        // pull requests
        $this->pullRequestParser->getPullRequests($repository);

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


}
