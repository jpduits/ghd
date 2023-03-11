<?php

namespace App\Metrics;

use Carbon\Carbon;
use App\Models\Repository;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Output\Output;

class StickyMetric extends BaseMetric
{

    public function __construct(Output $output)
    {
        parent::__construct($output);
    }


    public function get(Repository $repository, Carbon $startDate, int $periodInterval = 26, Carbon $endDate = null): array
    {
        // if endDate is null, set it to now
        // default interval is 26 means a period is a half year (26 weeks)

/*        $info = 'Sticky metric for ' . $repository->full_name . ' from ' . $startDate->format('Y-m-d');
        $info .= ' (period interval: ' . $periodInterval . ' weeks)';
        if ($endDate instanceof Carbon) {
            $info .= ' loop interval until ' . $endDate->format('Y-m-d');
        }
        $this->writeToTerminal($info);*/


        $measurements = []; // loop results

        // calculate sticky value for a range of periods
        while (true) {


            $measurements[] = $this->calculate($repository, $startDate, $periodInterval);


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






    private function calculate(Repository $repository, Carbon $startDate, int $periodInterval) : array
    {
        $periodStartDate = $startDate->copy(); // start period Pi
        $periodEndDate = $periodStartDate->copy()->addWeeks($periodInterval); // end period Pi
        $periodPreviousStartDate = $periodStartDate->copy()->subWeeks($periodInterval); // start period Pi-1



/*        $this->writeToTerminal('(Pi-1) start date:  ' . $periodPreviousStartDate->format('Y-m-d'));
        $this->writeToTerminal('(Pi-1) end date:    ' . $periodStartDate->format('Y-m-d'));
        $this->writeToTerminal('(Pi)   start date:  ' . $periodStartDate->format('Y-m-d'));
        $this->writeToTerminal('(Pi)   end date:    ' . $periodEndDate->format('Y-m-d'));*/

        // get values for period Pi
        $contributorsPeriod = $this->getContributorsInPeriod($repository, $periodStartDate, $periodEndDate);
        $contributorsPreviousPeriod = $this->getContributorsInPeriod($repository, $periodPreviousStartDate, $periodStartDate);

        // check how many contributors from Pi are als in Pi-1
        $matches = array_intersect($contributorsPeriod, $contributorsPreviousPeriod);

/*        $this->writeToTerminal('Contributors in period Pi: ' . count($contributorsPeriod).' ('.implode(',', $contributorsPeriod).')');
        $this->writeToTerminal('Contributors in period Pi-1: ' . count($contributorsPreviousPeriod).' ('.implode(',', $contributorsPreviousPeriod).')');
        $this->writeToTerminal('Contributors in in Pi and in Pi-1: ' .count($matches). ' ('. implode(',', $matches).')');*/


        if (count($contributorsPreviousPeriod) == 0) {
            $stickValue = 0;

        }
        else {
            $stickValue = count($matches) / count($contributorsPreviousPeriod);
/*            $this->writeToTerminal('Sticky value: ' . count($matches) . ' / ' . count($contributorsPreviousPeriod) . ' = ' . count($matches) / count($contributorsPreviousPeriod));

            $this->writeHorizontalLineToTerminal();*/
        }

        return [
            'period_start_date' => $periodStartDate->format('Y-m-d'),
            'period_end_date' => $periodEndDate->format('Y-m-d'),
            'previous_period_start_date' => $periodPreviousStartDate->format('Y-m-d'),
            'previous_period_end_date' => $periodStartDate->format('Y-m-d'),
            'developers_with_contributions_previous_period' => count($contributorsPreviousPeriod),
            'developers_with_contributions_current_period' => count($contributorsPeriod),
            'developers_with_contributions_previous_and_current_period' => count($matches),
            'sticky_value' => $stickValue
        ];

    }


    private function getContributorsInPeriod(Repository $repository, Carbon $startDate, Carbon $endDate): array
    {
        $contributors = DB::table('commits')
                          //->selectRaw('COUNT(DISTINCT commits.author_id) AS total_contributors')
                          //->selectRaw('commits.id, commits.author_id, commits.created_at, commits.repository_id')
                          ->selectRaw('DISTINCT commits.author_id')
                          ->join('users', 'users.github_id', '=', 'commits.author_id')
                          ->where(function ($q) use ($repository) {

                              // OR
                              $q->whereIn('commits.id', function ($q) use ($repository) {
                                  // subquery, to get commits from pull requests (when not merged)
                                  $q->select('pull_requests_commits.commit_id')
                                    ->from('pull_requests')
                                    ->join('pull_requests_commits', 'pull_requests_commits.pull_request_id', '=', 'github_id')
                                    ->where('pull_requests.base_repository_id', '=', $repository->id);


                              })->orWhere('repository_id', '=', $repository->id);

                          })
                          // AND
                          ->where('commits.created_at', '>=', $startDate)
                          ->where('commits.created_at', '<', $endDate)
                          ->where('users.name', '<>', 'GitHub')
                          ->get();

        return $contributors->pluck('author_id')->toArray();
    }
}
