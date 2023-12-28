<?php

namespace App\Commands;

use App\Models\User;
use App\Models\Repository;
use Illuminate\Support\Facades\DB;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Helper\Table;
use Illuminate\Database\Eloquent\Collection;

class FixDataset extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'fix:dataset';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Fix dataset';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        $duplicateUsers = DB::table('users')
                    ->select('email', DB::raw('COUNT(email) as count_email'))
                    ->groupBy('email')
                    ->havingRaw('COUNT(email) > 1')
                    ->whereNotNull('email')
                    ->get();

        foreach ($duplicateUsers as $duplicateUser) {

            $this->line('E-mail ' . $duplicateUser->email . ' has multiple user records ('.$duplicateUser->count_email.')');

            $filteredUser = User::where('email', $duplicateUser->email)
                 ->orderByRaw('LENGTH(name) DESC')
                 ->whereNot('name', '=', '')
                ->whereNot('email', '=', '--global')
                ->whereNot('email', '=', '“')
                ->whereNot('email', '=', '=')
                 ->first();


            if ($filteredUser instanceof User) {

                $this->line('User ' . $filteredUser->id . ' has longest name (' . $filteredUser->name. ')');
                $masterUserId = $filteredUser->id;

                // find user id's to replace
                $replaceUsers = User::where('email', $duplicateUser->email)
                                    ->whereNot('id', '=', $masterUserId)
                                    ->get();


                foreach ($replaceUsers as $replaceUser) {

                    $this->line('User: '.$replaceUser->id.' has name: '.$replaceUser->name);

                    $replaceArray = [
/*                        'stargazers' => 'user_id',
                        'repositories' => 'owner_id',
                        'pull_requests' => 'user_id',
                        'pull_requests' => 'head_user_id',
                        'pull_requests' => 'base_user_id',
                        'issues' => 'user_id',
                        'forks' => 'owner_id',
                        'commits' => 'author_id',
                        'commits' => 'committer_id',*/

                        //'user_id' => 'stargazers',
                        //'owner_id' => 'repositories',
                        //'user_id' => 'pull_requests',
                        'head_user_id' => 'pull_requests',
                        'base_user_id' => 'pull_requests',
                        //'user_id' => 'issues',
                        //'owner_id''forks',
                        'author_id' =>'commits',
                        'committer_id' => 'commits',

                    ];

                    foreach ($replaceArray as $column => $table ) {

                        $this->line('Update '.$column.' in '.$table.' from '.$replaceUser->id.' to '.$masterUserId);

                        DB::table($table)
                          ->where($column, $replaceUser->id)
                          ->update([$column => $masterUserId]);

                    }

                }


            }


            $this->line('==========');


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
