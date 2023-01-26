<?php

namespace App\Commands;

use App\User;
use App\Commit;
use Github\Client;
use Carbon\Carbon;
use App\Repository;
use Github\AuthMethod;
use Github\ResultPager;
use Illuminate\Support\Facades\Http;
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


        // issues opslaan



        // watchers
        $page = 1;
        $uri = 'repos/'.$project.'/commits?per_page=100';
/*        while (true) {


        }*/

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
            $this->info('User ('.$user->login.') created');
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
            $repository->save();
        }
        return $repository;
    }

    protected function getCommits(Repository $repository)
    {
        $commitCounter = 0;

        $page = 1;
        $uri = 'repos/'.$repository->full_name.'/commits?per_page=100';
        while (true) {

            $this->line('Get commits for '.$repository->full_name.' page '.$page);
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
            $this->line('Saving commits for '.$repository->full_name.' page '.$page.'/'.$lastPage);
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
            if (!isset($links['next'])) {
            //if (true) { //debug
                break;
            }

            // else get next page
            $page++;
            $uri = $links['next']['link'];

        }

        return $commitCounter;
    }
}
