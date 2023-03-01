<?php

namespace App\Commands;

use App\Models\Repository;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Helper\Table;
use Illuminate\Database\Eloquent\Collection;

class GetDatasetStats extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'get:dataset-stats {--repository= : Full name of ID of the repository}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Get the stats of a specific repository or all repositories from the dataset';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $repository = $this->option('repository', null);
        if ($repository === null) {
            $this->info('Get stats for all repositories (not forks)');
            $repo = Repository::where('is_fork', '=', false)->get();
            if ($repo->count() === 0) {
                $this->error('No repositories found');
                exit(1);
            }



        }
        else {

            if (is_numeric($repository)) {
                $repo = Repository::find($repository);
            }
            else {
                $repo = Repository::where('full_name', $repository)->first();
            }

            if (!$repo instanceof Repository) {
                $this->error('Repository not found');
                exit(1);
            }
            else {
                $this->info(sprintf('Get stats for repository %s', $repository));
            }

        }

        $table = new Table(new ConsoleOutput);

        $table->setHeaders(['Owner', 'Name', 'Repository', 'ID', 'default branch', 'created_at', 'commits', 'pull_requests', 'stargazers', 'issues', 'forks', 'watchers']);

        if ($repo instanceof Collection) {

            foreach ($repo as $r) {
                $table->addRow([
                    $r->owner->login,
                    $r->name,
                    $r->full_name,
                    $r->id,
                    $r->default_branch,
                    $r->created_at,
                    $r->commits->count(),
                    $r->pullRequests->count(),
                    $r->stargazers->count(),
                    $r->issues->count(),
                    $r->forks->count(),
                    $r->subscribers_count
                ]);
            }

            $table->render();
        }
        else {
            $table->addRow([
                $repo->owner->login,
                $repo->name,
                $repo->full_name,
                $repo->id,
                $repo->default_branch,
                $repo->created_at,
                $repo->commits->count(),
                $repo->pullRequests->count(),
                $repo->stargazers->count(),
                $repo->issues->count(),
                $repo->fork->count(),
                $repo->subscribers_count
            ]);

            $table->render();
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
}
