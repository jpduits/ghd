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

    public function calculate(Repository $repository, Carbon $startDate, int $periodInterval) : array
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

}
