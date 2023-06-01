<?php

namespace App\QualityModel\Metrics\Community;

use Carbon\Carbon;
use App\Models\Repository;
use Illuminate\Support\Str;
use App\QualityModel\Metrics\BaseMetric;
use Symfony\Component\Console\Output\Output;

class GithubMeta extends BaseMetric
{

    public function __construct(Output $output)
    {
        parent::__construct($output);
    }

    public function get(Repository $repository, Carbon $startDate, $periodInterval = 26, Carbon $endDate = null): array
    {
        $metaData = []; // loop results

        while (true) {

            $metaData[] = $this->getRelation($repository, $startDate, $periodInterval);

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

        return $metaData;
    }

    private function getRelation(Repository $repository, Carbon $startDate, int $periodInterval) : array
    {
        $relationNames = [
            'stargazers' => 'starred_at',
            'forks' => 'created_at',
            'issues' => 'created_at',
            'pullRequests' => 'created_at'
        ];
        $metaData = [];

        foreach ($relationNames as $relationName => $startFieldName) {

            $periodStartDate = $startDate->copy(); // start period Pi
            $periodEndDate = $startDate->copy()->addWeeks($periodInterval); // end period Pi
            $periodPreviousStartDate = $periodStartDate->copy()->subWeeks($periodInterval); // start period Pi-1

            $snakeRelationName = Str::snake($relationName);

            $metaData[$snakeRelationName.'_count_current_period'] = $repository->$relationName()->where($startFieldName, '>=', $startDate->format('Y-m-d'))
                              ->where($startFieldName, '<', $startDate->copy()->addWeeks($periodInterval)->format('Y-m-d'))
                              ->count();

            $metaData[$snakeRelationName.'_count_total'] = $repository->$relationName()->where($startFieldName, '<', $startDate->copy()->addWeeks($periodInterval)->format('Y-m-d'))
                                ->count();

        }

        return array_merge($metaData, [
            'period_start_date' => $periodStartDate->format('Y-m-d'),
            'period_end_date' => $periodEndDate->format('Y-m-d'),
            'previous_period_start_date' => $periodPreviousStartDate->format('Y-m-d'),
            'previous_period_end_date' => $periodStartDate->format('Y-m-d'),
        ]);

    }


   /* private function getStarGazers(Repository $repository, Carbon $startDate, mixed $periodInterval)
    {
        $new = $repository->stargazers()->where('created_at', '>=', $startDate->format('Y-m-d'))
            ->where('created_at', '<', $startDate->copy()->addWeeks($periodInterval)->format('Y-m-d'))
            ->count();

        $total = $repository->stargazers()->where('created_at', '<', $startDate->copy()->addWeeks($periodInterval)->format('Y-m-d'))
            ->count();

        return [
            'stargazers_count_new' => $new,
            'stargazers_count_total' => $total,
        ];

    }

    private function getForks(Repository $repository, Carbon $startDate, mixed $periodInterval)
    {
        $new = $repository->forks()->where('created_at', '>=', $startDate->format('Y-m-d'))
            ->where('created_at', '<', $startDate->copy()->addWeeks($periodInterval)->format('Y-m-d'))
            ->count();

        $total = $repository->forks()->where('created_at', '<', $startDate->copy()->addWeeks($periodInterval)->format('Y-m-d'))
            ->count();

        return [
            'forks_count_new' => $new,
            'forks_count_total' => $total,
        ];
    }

    private function getIssues(Repository $repository, Carbon $startDate, mixed $periodInterval)
    {
        $new = $repository->issues()->where('created_at', '>=', $startDate->format('Y-m-d'))
            ->where('created_at', '<', $startDate->copy()->addWeeks($periodInterval)->format('Y-m-d'))
            ->count();

        $total = $repository->issues()->where('created_at', '<', $startDate->copy()->addWeeks($periodInterval)->format('Y-m-d'))
            ->count();

        return [
            'issues_count_new' => $new,
            'issues_count_total' => $total,
        ];

    }

    private function getPullRequests(Repository $repository, Carbon $startDate, mixed $periodInterval)
    {
        $new = $repository->pullRequests()->where('created_at', '>=', $startDate->format('Y-m-d'))
            ->where('created_at', '<', $startDate->copy()->addWeeks($periodInterval)->format('Y-m-d'))
            ->count();

        $total = $repository->pullRequests()->where('created_at', '<', $startDate->copy()->addWeeks($periodInterval)->format('Y-m-d'))
            ->count();

        return [
            'pull_requests_count_new' => $new,
            'pull_requests_count_total' => $total,
        ];
    }*/

}
