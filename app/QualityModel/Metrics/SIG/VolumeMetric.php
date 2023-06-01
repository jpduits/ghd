<?php

namespace App\QualityModel\Metrics\SIG;

use App\Models\Repository;
use App\QualityModel\Metrics\BaseMetric;
use Symfony\Component\Console\Output\Output;

class VolumeMetric extends BaseMetric
{
    const KLOC_RANKING = [
        ['min' => 0, 'max' => 66, 'ranking' => '++', 'value' => 5],
        ['min' => 66, 'max' => 246, 'ranking' => '+', 'value' => 4],
        ['min' => 246, 'max' => 665, 'ranking' => 'o', 'value' => 3],
        ['min' => 665, 'max' => 1310, 'ranking' => '-', 'value' => 2],
        ['min' => 1310, 'max' => 90000000, 'ranking' => '--', 'value' => 1],
    ];


    public function __construct(Output $output)
    {
        parent::__construct($output);
    }


    public function calculate(Repository $repository): array
    {
        // calculate volume (loc) for Java files
        $command = 'cloc --json --include-lang=Java ' . $this->checkoutDir . '/' . $repository->name;

        $this->writeToTerminal('Executing command: ' . $command);
        exec($command, $output);

        $jsonOutput = implode("\n", $output);
        $volume = json_decode($jsonOutput, true);

        if ($volume !== null && json_last_error() === JSON_ERROR_NONE) { // valid JSON

            $loc = $volume['SUM']['code'] ?? 0;
            $kloc = round($loc / 1000) ?? 0;
            $ranking = $this->getKlocRanking($kloc);

            return [
                'total_loc' => $loc,
                'total_kloc' => $kloc,
                'sig_volume_ranking' => $ranking['ranking'] ?? 'o',
                'sig_volume_ranking_numeric' => $ranking['value'] ?? 3,
            ];

        }


        return [];
    }


    private function getKlocRanking(int $kLoc) : ?array
    {
        foreach (self::KLOC_RANKING as $ranking) {

            if ($kLoc >= $ranking['min'] && $kLoc <= $ranking['max']) {
                return $ranking;
            }

        }

        return null;
    }

}
