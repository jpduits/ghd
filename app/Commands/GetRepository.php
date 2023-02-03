<?php

namespace App\Commands;

use Github\Client;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Issue;
use App\Models\Commit;
use Github\AuthMethod;
use App\Models\Stargazer;
use App\Models\Repository;
use App\Models\PullRequest;
use TiagoHillebrandt\ParseLinkHeader;
use Github\Exception\RuntimeException;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use Github\HttpClient\Message\ResponseMediator;

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
    private Client $client;


    public function __construct()
    {
        parent::__construct();
        $this->client = new Client();
        $this->client->authenticate(env('GITHUB_TOKEN'), null, AuthMethod::ACCESS_TOKEN);

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
        $project = $_owner.'/'.$_repository;

        // load or store repository if not exists
        $repository = $this->repositoryExistsOrCreate($_owner, $_repository);

        // now get commits from selected repository
        //$paginator = new ResultPager($this->client, 5);
        $commitCount = $this->getCommits($repository);
        $this->info($commitCount.' commits saved.');

        // stars + users
        $stargazerCount = $this->getStargazers($repository);
        $this->info($stargazerCount.' stargazers saved.');

        // issues
     //   $issuesCount = $this->getIssues($repository);
//        $this->info($issuesCount.' issues saved.');

        // pull requests
        $pullRequestCount = $this->getPullRequests($repository);
        $this->info($pullRequestCount.' pull requests saved.');

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

    protected function userExistsOrCreate(int $id) : User
    {
        $user = User::where('id', '=', $id)->first();
        if (!$user instanceof User) {
            // get user and save
            try {
                $userFromRequest = $this->client->api('user')->showById($id);
            }
            catch (RuntimeException $e) {
                $this->warn('User '.$id.' does not exists!');
                return User::find(0);
            }
            // save to DB
            $user = new User();
            $user->id = $userFromRequest['id'];
            $user->login = $userFromRequest['login'];
            $user->node_id = $userFromRequest['node_id'];
            $user->name = $userFromRequest['name'];
            $user->company = $userFromRequest['company'];
            $user->location = $userFromRequest['location'];
            $user->email = $userFromRequest['email'];
            $user->html_url = $userFromRequest['html_url'];
            $user->save();
//            $this->info('User ('.$user->login.') created');
        }
        return $user;
    }

    protected function repositoryExistsOrCreate(string $ownerName, string $repositoryName) : Repository
    {
        $repository = Repository::where('full_name', '=', $ownerName.'/'.$repositoryName)->first();
        if (!$repository instanceof Repository) {

            $repositoryFromRequest = $this->client->api('repo')->show($ownerName, $repositoryName);
            $owner = $this->userExistsOrCreate($repositoryFromRequest['owner']['id']);
            $repository = new Repository();
            $repository->id = $repositoryFromRequest['id'];
            $repository->node_id = $repositoryFromRequest['node_id'];
            $repository->name = $repositoryFromRequest['name'];
            $repository->full_name = $repositoryFromRequest['full_name'];
            $repository->owner_id = $owner->id;
            $repository->description = $repositoryFromRequest['description'];
            $repository->html_url = $repositoryFromRequest['html_url'];
            $repository->default_branch = $repositoryFromRequest['default_branch'];
            $repository->is_fork = $repositoryFromRequest['fork'] ?? false;
            $repository->save();
        }
        return $repository;
    }

    protected function getCommits(Repository $repository, int $pullRequestNumber = null)
    {
        $commitCounter = 0;

        $page = 1;
        $lastPage = 1;

        $suffix = '';
        if ($pullRequestNumber) {
            $uri = 'repos/' . $repository->full_name . '/pulls/'.$pullRequestNumber.'/commits?per_page=100';
            $suffix = ' (for not merged PullRequest '.$pullRequestNumber.')';
        }
        else {
            $uri = 'repos/' . $repository->full_name . '/commits?per_page=100';
        }
        while (true) {

            $this->line('Get commits'.$suffix.' for '.$repository->full_name.' page '.$page);
            $response = $this->client->getHttpClient()->get($uri);
            $headers = $response->getHeaders();

            if (isset($headers['X-RateLimit-Remaining'][0])) {
                $this->info('Remaining requests: '.$headers['X-RateLimit-Remaining'][0].'/'.$headers['X-RateLimit-Limit'][0]);
            }


            if (isset($headers['Link'][0] )) {
                $links = (new ParseLinkHeader($headers['Link'][0]))->toArray();
                $lastPage = $links['last']['page'] ?? 1;
            }

            $commits = ResponseMediator::getContent($response);

            // save the commits
            $this->line('Saving commits'.$suffix.' for '.$repository->full_name.' page '.$page.'/'.$lastPage);
            foreach ($commits as $commit) {


                // first check commit exists
                $commitRecord = Commit::where('sha', '=', $commit['sha'])->first();
                if (!$commitRecord instanceof Commit) {
                    // Commit does not exist, create
                    $commitRecord = new Commit();
                    $commitRecord->sha = $commit['sha'];

                    $commitRecord->repository_id = $repository->id;

                    $commitDate = $commit['commit']['author']['date'];
                    $commitRecord->created_at = Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $commitDate)->toDateTimeString();

                    if (isset($commit['author']['id'])) {
                        $authorId = $commit['author']['id'];
                        $author = $this->userExistsOrCreate($authorId);
                    }
                    else {
                        $author = $this->userExistsOrCreate(0); // user deleted
                    }

                    if (isset($commit['committer']['id'])) {
                        $committerId = $commit['committer']['id'];
                        $committer = $this->userExistsOrCreate($committerId);
                    }
                    else {
                        $committer = $this->userExistsOrCreate(0); // user deleted
                    }

                    $commitRecord->author_id = $author->id ?? null;
                    $commitRecord->committer_id = $committer->id ?? null;

                    $commitRecord->message = $commit['commit']['message'] ?? '';
                    $commitRecord->node_id = $commit['node_id'];
                    $commitRecord->html_url = $commit['html_url'];
                    $commitRecord->save();
                    $commitCounter++;
                }
                else {
                    $this->line('Commit: '.$commit['sha'].' already exists, skipping.');
                }

            }

            // no next page, break from while
            //if (!isset($links['next'])) {
            if (true) { //debug
                break;
            }

            // else get next page
            $page++;
            $uri = $links['next']['link'];

        }

        return $commitCounter;
    }

    protected function getStargazers(Repository $repository)
    {
        $stargazerCounter = 0;

        $page = 1;
        $uri = 'repos/'.$repository->full_name.'/stargazers?per_page=100';
        $httpClient = $this->client->getHttpClient();

        $lastPage = 1;

        while (true) {

            $this->line('Get stargazers for ' . $repository->full_name . ' page ' . $page);

            $response = $httpClient->get($uri, ['Accept' => 'application/vnd.github.star+json']);
            $headers = $response->getHeaders();

            if (isset($headers['X-RateLimit-Remaining'][0])) {
                $this->info('Remaining requests: ' . $headers['X-RateLimit-Remaining'][0] . '/' . $headers['X-RateLimit-Limit'][0]);
            }

            if (isset($headers['Link'][0] )) {
                $links = (new ParseLinkHeader($headers['Link'][0]))->toArray();
                $lastPage = $links['last']['page'] ?? $lastPage;
            }

            $stargazers = ResponseMediator::getContent($response);

            // save the commits
            $this->line('Saving stargazers for '.$repository->full_name.' page '.$page.'/'.$lastPage);

            foreach ($stargazers as $stargazer) {
                // first check stargazer exists
                $stargazerRecord = Stargazer::where('user_id', '=', $stargazer['user']['id'])->where('repository_id', '=', $repository->id)->first();
                if (!$stargazerRecord instanceof Stargazer) {
                    // Commit does not exist, create
                    $stargazerRecord = new Stargazer();
                    $user = $this->userExistsOrCreate($stargazer['user']['id']);
                    $stargazerRecord->user_id = $user->id;
                    $stargazerRecord->repository_id = $repository->id;
                    $starredAtDate = $stargazer['starred_at'];
                    $stargazerRecord->starred_at = Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $starredAtDate)->toDateTimeString();
                    $stargazerRecord->save();
                    $stargazerCounter++;
                }
                else {
                    $this->line('Startgazer: '.$stargazer['user']['id'].' already exists, skipping.');
                }

            }

            // no next page, break from while
            if (!isset($links['next'])) {
                //if (true) { //debug
                break;
            }

            // else get next page
            $page++;
            $uri = $links['next']['link'];

        }

        return $stargazerCounter;
    }


    protected function getIssues(Repository $repository)
    {

        $issuesCounter = 0;

        $page = 1;
        $uri = 'repos/'.$repository->full_name.'/issues?per_page=100&state=all';
        $httpClient = $this->client->getHttpClient();

        $lastPage = 1;

        while (true) {

            $this->line('Get issues for ' . $repository->full_name . ' page ' . $page);

            $response = $httpClient->get($uri, ['Accept' => 'application/vnd.github+json']);
            $headers = $response->getHeaders();

            if (isset($headers['X-RateLimit-Remaining'][0])) {
                $this->info('Remaining requests: ' . $headers['X-RateLimit-Remaining'][0] . '/' . $headers['X-RateLimit-Limit'][0]);
            }

            if (isset($headers['Link'][0] )) {
                $links = (new ParseLinkHeader($headers['Link'][0]))->toArray();
                $lastPage = $links['last']['page'] ?? $lastPage;
            }

            $issues = ResponseMediator::getContent($response);

            // save the commits
            $this->line('Saving issues for '.$repository->full_name.' page '.$page.'/'.$lastPage);

            foreach ($issues as $issue) {
                // first check stargazer exists
                $issueRecord = Issue::find($issue['id']);
                if (!$issueRecord instanceof Issue) {
                    // Commit does not exist, create
                    $issueRecord = new Issue();
                    $issueRecord->id = $issue['id'];
                    $user = $this->userExistsOrCreate($issue['user']['id']);
                    $issueRecord->user_id = $user->id;
                    $issueRecord->repository_id = $repository->id;
                    $closedAtDate = $issue['closed_at'];
                    if ($closedAtDate !== null) {
                        $issueRecord->closed_at = Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $closedAtDate)->toDateTimeString();
                    }

                    $createdAtDate = $issue['created_at'];
                    if ($createdAtDate !== null) {
                        $issueRecord->created_at = Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $createdAtDate)->toDateTimeString();
                    }

                    $updatedAtDate = $issue['updated_at'];
                    if ($updatedAtDate !== null) {
                        $issueRecord->updated_at = Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $updatedAtDate)->toDateTimeString();
                    }

                    $issueRecord->title = $issue['title'];
                    $issueRecord->description = $issue['body'] ?? '';
                    $issueRecord->state = $issue['state'];
                    $issueRecord->node_id = $issue['node_id'];
                    $issueRecord->number = $issue['number'] ?? null;
                    $issueRecord->comments = $issue['comments'] ?? 0;
                    $issueRecord->html_url = $issue['html_url'];

                    $issueRecord->save();
                    $issuesCounter++;
                }
                else {
                    $this->line('Issue: '.$issue['id'].' already exists, skipping.');
                }

            }

            // no next page, break from while
            if (!isset($links['next'])) {
                //if (true) { //debug
                break;
            }

            // else get next page
            $page++;
            $uri = $links['next']['link'];

        }

        return $issuesCounter;

    }

    protected function getPullRequests(Repository $repository)
    {
        // head = refers to the branch that contains the changes to be pulled or merged.
        // base = refers to the branch to which the changes will be pulled or merged.

        // The "merge_commit_sha" is the SHA (secure hash algorithm) value of the commit that merges the changes from the "head" branch into the "base" branch. It is a unique identifier for the merge commit in the Git repository.

        $pullRequestCounter = 0;

        $page = 1;
        $uri = 'repos/'.$repository->full_name.'/pulls?per_page=100&state=all';
        $httpClient = $this->client->getHttpClient();

        $lastPage = 1;

        while (true) {

            $this->line('Get pull requests for ' . $repository->full_name . ' page ' . $page);

            $response = $httpClient->get($uri, ['Accept' => 'application/vnd.github+json']);
            $headers = $response->getHeaders();

            if (isset($headers['X-RateLimit-Remaining'][0])) {
                $this->info('Remaining requests: ' . $headers['X-RateLimit-Remaining'][0] . '/' . $headers['X-RateLimit-Limit'][0]);
            }

            if (isset($headers['Link'][0] )) {
                $links = (new ParseLinkHeader($headers['Link'][0]))->toArray();
                $lastPage = $links['last']['page'] ?? $lastPage;
            }

            $pullRequests = ResponseMediator::getContent($response);

            // save the commits
            $this->line('Saving pull requests for '.$repository->full_name.' page '.$page.'/'.$lastPage);

            foreach ($pullRequests as $pullRequest) {
                // first check stargazer exists
                $pullRequestRecord = PullRequest::find($pullRequest['id']);
                if (!$pullRequestRecord instanceof PullRequest) {
                    // Commit does not exist, create
                    $pullRequestRecord = new PullRequest();

                    $pullRequestRecord->id = $pullRequest['id'];
                    $pullRequestRecord->node_id = $pullRequest['node_id'];
                    $pullRequestRecord->url = $pullRequest['url'];
                    $pullRequestRecord->state = $pullRequest['state'];
                    $pullRequestRecord->number = $pullRequest['number'];
                    $pullRequestRecord->title = $pullRequest['title'];
                    $pullRequestRecord->body = $pullRequest['body'];
                    $pullRequestRecord->merge_commit_sha = $pullRequest['merge_commit_sha'];
                    $mergeCommit = Commit::where('sha', '=', $pullRequest['merge_commit_sha'])->first();
                    if ($mergeCommit instanceof Commit) {
                        // there is a merge commit, so the commits are saved under the selected branch
                        $pullRequestRecord->merge_commit_id = $mergeCommit->id;
                    }

                    $user = $this->userExistsOrCreate($pullRequest['user']['id']);
                    $pullRequestRecord->user_id = $user->id;

                    $closedAtDate = $pullRequest['closed_at'];
                    if ($closedAtDate !== null) {
                        $pullRequestRecord->closed_at = Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $closedAtDate)->toDateTimeString();
                    }

                    $createdAtDate = $pullRequest['created_at'];
                    if ($createdAtDate !== null) {
                        $pullRequestRecord->created_at = Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $createdAtDate)->toDateTimeString();
                    }

                    $updatedAtDate = $pullRequest['updated_at'];
                    if ($updatedAtDate !== null) {
                        $pullRequestRecord->updated_at = Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $updatedAtDate)->toDateTimeString();
                    }

                    $mergedAtDate = $pullRequest['merged_at'];
                    if ($mergedAtDate !== null) {
                        $pullRequestRecord->merged_at = Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $mergedAtDate)->toDateTimeString();
                    }

                    if (isset($pullRequest['head']['user']['id'])) {
                        $user = $this->userExistsOrCreate($pullRequest['head']['user']['id']);
                        $pullRequestRecord->head_user_id = $user->id;
                    }

                    if (isset($pullRequest['head']['repo']['full_name'])) {

                        try {

                            $mainRepository = $this->repositoryExistsOrCreate($pullRequest['head']['repo']['owner']['login'], $pullRequest['head']['repo']['name']);
                            $pullRequestRecord->head_repository_id = $mainRepository->id;
                            $pullRequestRecord->head_ref = $pullRequest['head']['ref'];

                            // save commits of not merged pull request
                            if ($pullRequest['merge_commit_sha'] == null) {
                                // Pull request not merged, try to get the commits and users.
                                $commitCount = $this->getCommits($repository, $pullRequest['number']);
                                $this->info($commitCount.' commits saved for not merged pull request.');
                            }

                        } catch (RuntimeException $e) {
                            $this->warn('Repository '.$pullRequest['head']['repo']['full_name'].' does not exist anymore, commits not saved');
                        }

                    }

                    $user = $this->userExistsOrCreate($pullRequest['base']['user']['id']);
                    $pullRequestRecord->base_user_id = $user->id;

                    $baseRepository = $this->repositoryExistsOrCreate($pullRequest['base']['repo']['owner']['login'], $pullRequest['base']['repo']['name']);
                    $pullRequestRecord->base_repository_id = $baseRepository->id;
                    $pullRequestRecord->base_ref = $pullRequest['base']['ref'];


                    $pullRequestRecord->save();
                    $pullRequestCounter++;
                }
                else {
                    $this->line('Pull request: '.$pullRequest['id'].' already exists, skipping.');
                }

            }

            // no next page, break from while
            if (!isset($links['next'])) {
                //if (true) { //debug
                break;
            }

            // else get next page
            $page++;
            $uri = $links['next']['link'];

        }

        return $pullRequestCounter;



    }

}
