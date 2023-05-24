<?php

namespace App\Metrics;

use Carbon\Carbon;
use App\Models\Repository;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Output\Output;

class MagnetMetric extends BaseMetric
{

    public function __construct(Output $output)
    {
        parent::__construct($output);
    }


    public function get(Repository $repository, Carbon $startDate, int $periodInterval = 26, Carbon $endDate = null): array
    {
/*        $info = 'Magnet metric for ' . $repository->full_name . ' from ' . $startDate->format('Y-m-d');
        $info .= ' (period interval: ' . $periodInterval . ' weeks)';
        if ($endDate instanceof Carbon) {
            $info .= ' loop interval until ' . $endDate->format('Y-m-d');
        }
        $this->writeToTerminal($info);*/
        // calculate magnet value for a range of periods

        $measurements = []; // loop results

        while (true) {

            $measurements[] = $this->calculate($repository, $startDate, $periodInterval);
            //$this->writeToTerminal('Sticky value: ' . $sticky);

            if ($endDate) { // when endDate is set, loop until endDate is reached

                $startDate->addWeeks($periodInterval); // add interval to start date
                if (($startDate->gt($endDate)) || ($startDate->gt(Carbon::now()))) {
                    break;
                }
            }
            else {
                break;
            }

        }

        return $measurements;
    }

    private function calculate(Repository $repository, Carbon $startDate, int $periodInterval = null) : array
    {
        $periodStartDate = $startDate->copy(); // start period Pi
        $periodEndDate = $startDate->copy()->addWeeks($periodInterval); // end period Pi
        $periodPreviousStartDate = $periodStartDate->copy()->subWeeks($periodInterval); // start period Pi-1
        // get values for period Pi
        $developersTotal = $this->getDevelopersInPeriod($repository, null, $startDate);
        $developersCurrentPeriod = $this->getDevelopersInPeriod($repository, $startDate, $periodEndDate);

        // check how many developers from Pi are not in totalDevelopers
        $developersNewCurrentPeriod = array_diff($developersCurrentPeriod, $developersTotal);

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


    private function getDevelopersInPeriod(Repository $repository, Carbon $startDate = null, Carbon $endDate) : array
    {
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
