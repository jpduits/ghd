<?php

namespace App\QualityModel\Metrics\Community;

use Carbon\Carbon;
use App\Models\Repository;
use Illuminate\Support\Facades\DB;
use App\QualityModel\Metrics\BaseMetric;
use Symfony\Component\Console\Output\Output;

class IssueMetric extends BaseMetric
{

    public function __construct(Output $output)
    {
        parent::__construct($output);
    }

    public function calculate(Repository $repository, Carbon $startDate, int $periodInterval) : array
    {
        $periodStartDate = $startDate->copy(); // start period Pi
        $periodEndDate = $periodStartDate->copy()->addWeeks($periodInterval); // end period Pi

        $bugsCurrentPeriod = $this->getIssuesInPeriod($repository, $periodStartDate, $periodEndDate, ['bug', 'kind/bug', 'kind / bug']);
        $bugsTotal = $this->getIssuesInPeriod($repository, null, $periodEndDate, ['bug', 'kind/bug', 'kind / bug']);

        $bugsClosedCurrentPeriod = $this->getIssuesInPeriod($repository, $periodStartDate, $periodEndDate, ['bug', 'kind/bug', 'kind / bug', 'Status: fixed', 'fixed'], ['closed']);
        $bugsClosedTotal = $this->getIssuesInPeriod($repository, null, $periodEndDate, ['bug', 'kind/bug', 'kind / bug', 'Status: fixed', 'fixed'], ['closed']);

        $supportCurrentPeriod =  $this->getIssuesInPeriod($repository, $periodStartDate, $periodEndDate, ['help wanted', 'kind/question', 'kind / question', 'question', 'support']);
        $supportTotal =  $this->getIssuesInPeriod($repository, null, $periodEndDate, ['help wanted', 'kind/question', 'kind / question', 'question', 'support']);

        $supportClosedCurrentPeriod =  $this->getIssuesInPeriod($repository, $periodStartDate, $periodEndDate, ['help wanted', 'kind/question', 'kind / question', 'question', 'support'], ['closed']);
        $supportClosedTotal =  $this->getIssuesInPeriod($repository, null, $periodEndDate, ['help wanted', 'kind/question', 'kind / question', 'question', 'support'], ['closed']);



        return [
            'bugs_current_period' => count($bugsCurrentPeriod),
            'bugs_total' => count($bugsTotal),
            'bugs_closed_current_period' => count($bugsClosedCurrentPeriod),
            'bugs_closed_total' => count($bugsClosedTotal),
            'support_current_period' => count($supportCurrentPeriod),
            'support_total' => count($supportTotal),
            'support_closed_current_period' => count($supportClosedCurrentPeriod),
            'support_closed_total' => count($supportClosedTotal),
            'period_start_date' => $periodStartDate->format('Y-m-d'),
            'period_end_date' => $periodEndDate->format('Y-m-d'),
        ];

    }


    private function getIssuesInPeriod(Repository $repository, ?Carbon $startDate, Carbon $endDate, $labels = [], $state = [])
    {

        $bugs = DB::table('issues')
                        ->join('issues_labels', 'issues.id', '=', 'issues_labels.issue_id')
                        ->join('labels', 'issues_labels.label_id', '=', 'labels.id')
                        ->selectRaw('DISTINCT issues.github_id')
                        ->where('issues.repository_id', '=', $repository->id)
                        ->where('issues.created_at', '<', $endDate);

                        if (count($labels) > 0) {
                            $bugs->whereIn('labels.name', $labels);
                        }

                        if (count($state) > 0) {
                            $bugs->whereIn('issues.state', $state);
                        }




        if ($startDate !== null) {
            $bugs->where('issues.created_at', '>=', $startDate);
        }

        return $bugs->get()->pluck('github_id')->toArray();
    }



}
