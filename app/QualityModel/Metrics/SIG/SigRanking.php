<?php

namespace App\QualityModel\Metrics\SIG;

use App\QualityModel\Metrics\BaseMetric;
use Symfony\Component\Console\Output\Output;

class SigRanking extends BaseMetric
{
    const SYSTEM_LEVEL_RANKING = [
        5 => '++',
        4 => '+',
        3 => 'o',
        2 => '-',
        1 => '--'
    ];

    public function __construct(Output $output)
    {
        parent::__construct($output);
    }

    public function calculate(array $results) : array
    {
        $volume = $results['sig_volume_ranking_numeric'] ?? 3;
        $unitComplexity = $results['sig_complexity_ranking_value'] ?? 3;
        $unitSize = $results['sig_unit_size_ranking_value'] ?? 3;
        $duplication = $results['sig_duplication_ranking_numeric'] ?? 3;
        $unitTesting = 3;

        // calculate scores
        $analysability = round(($volume + $duplication + $unitSize + $unitTesting) / 4);
        $changeability = round(($unitComplexity + $duplication) / 2);
        $testability = round(($unitComplexity + $unitSize + $unitTesting) / 3);
        //$stability = $unitTesting;

        return [
            'sig_analysability_ranking' => self::SYSTEM_LEVEL_RANKING[$analysability] ?? 'Unknown',
            'sig_analysability_ranking_numeric' => $analysability,
            'sig_changeability_ranking' => self::SYSTEM_LEVEL_RANKING[$changeability] ?? 'Unknown',
            'sig_changeability_ranking_numeric' => $changeability,
            'sig_testability_ranking' => self::SYSTEM_LEVEL_RANKING[$testability] ?? 'Unknown',
            'sig_testability_ranking_numeric' => $testability,
        ];

    }


}
