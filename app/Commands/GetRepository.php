<?php

namespace App\Commands;

use App\User;
use App\Commit;
use Github\Client;
use Carbon\Carbon;
use App\Repository;
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

        // load or store repository if not exists
        $repository = $this->repositoryExistsOrCreate($_owner, $_repository);

        // now get commits from selected repository
        $params = [
            'sha' => $repository['default_branch']
        ];

        if ($repository->last_check !== null) {
            $params['since'] = $repository->last_check;
        }

        $commits = $this->client->api('repo')->commits()->all($_owner, $_repository, $params);
        foreach ($commits as $commit) {

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
                $author =  $this->userExistsOrCreate(0); // user deleted
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

    protected function userExistsOrCreate(int $id) : User
    {
        $user = User::where('id', '=', $id)->first();
        if (!$user instanceof User) {
            // get user and save
            $userFromRequest = $this->client->api('user')->showById($id);
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
            $this->info('User ('.$user->name.') created');
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
}
