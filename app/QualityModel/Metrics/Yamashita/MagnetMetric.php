<?php

namespace App\QualityModel\Metrics\Yamashita;

use Carbon\Carbon;
use App\Models\Repository;
use Illuminate\Support\Facades\DB;
use App\QualityModel\Metrics\BaseMetric;
use Symfony\Component\Console\Output\Output;

class MagnetMetric extends BaseMetric
{
    public function __construct(Output $output)
    {
        parent::__construct($output);
    }

    public function calculate(Repository $repository, Carbon $startDate, int $periodInterval = null) : array
    {
        $periodStartDate = $startDate->copy(); // start period Pi
        $periodEndDate = $startDate->copy()->addWeeks($periodInterval); // end period Pi
        $periodPreviousStartDate = $periodStartDate->copy()->subWeeks($periodInterval); // start period Pi-1
        // get values for period Pi
        // get all developers until current period (to prevent double counting, new developer are also in total users)
        $developersTotalBeforeStart = $this->getDevelopersInPeriod($repository, null, $startDate);
        $developersCurrentPeriod = $this->getDevelopersInPeriod($repository, $startDate, $periodEndDate);

        // check how many developers from Pi are not in totalDevelopers
        $developersNewCurrentPeriod = array_diff($developersCurrentPeriod, $developersTotalBeforeStart);

        $developersTotal = $this->getDevelopersInPeriod($repository, null, $periodEndDate);

        if (count($developersTotal) == 0) {
            $magnetValue = 0;
        }
        else {
            $magnetValue = count($developersNewCurrentPeriod) / count($developersTotal);
        }

        return [
            'period_start_date' => $periodStartDate->format('Y-m-d'),
            'period_end_date' => $periodEndDate->format('Y-m-d'),
            'previous_period_start_date' => $periodPreviousStartDate->format('Y-m-d'),
            'previous_period_end_date' => $periodStartDate->format('Y-m-d'),

            'developers_current_period' => count($developersCurrentPeriod),
            'developers_new_current_period' => count($developersNewCurrentPeriod),
            'developers_total' => count($developersTotal),
            'magnet_value' => $magnetValue
        ];

    }


    private function getDevelopersInPeriod(Repository $repository, ?Carbon $startDate, Carbon $endDate) : array
    {
        /** alle users van de commits, waar commits_id voorkomt in de pull_request_commits table (van de betreffende repo)
         * of uit de commits table binnen een bepaalde datum.
         **/
        $developers = DB::table('commits')
                        ->selectRaw('DISTINCT commits.author_id')
                        ->join('users', 'users.id', '=', 'commits.author_id')
                        ->where(function ($q) use ($repository) {

                            // OR
                            $q->whereIn('commits.id', function ($q) use ($repository) {
                                // subquery, to get commits from pull requests (when not merged)
                                $q->select('pull_requests_commits.commit_id')
                                  ->from('pull_requests')
                                  ->join('pull_requests_commits', 'pull_requests_commits.pull_request_id', '=', 'pull_requests.github_id')
                                  ->where('pull_requests.base_repository_id', '=', $repository->id);


                            })->orWhere('commits.repository_id', '=', $repository->id);

                        })->where('commits.created_at', '<', $endDate) // AND
                        ->where('users.name', '<>', 'GitHub'); // AND

        if ($startDate !== null) {
            $developers->where('commits.created_at', '>=', $startDate);
        }

        return $developers->get()->pluck('author_id')->toArray();
    }


}
