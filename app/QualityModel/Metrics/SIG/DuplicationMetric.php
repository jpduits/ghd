<?php

namespace App\QualityModel\Metrics\SIG;

use App\Traits\Terminal;
use App\Models\Repository;
use App\QualityModel\Metrics\BaseMetric;
use Symfony\Component\Console\Output\Output;

class DuplicationMetric extends BaseMetric
{

    const SIMIAN_JAR = './scripts/analyzers/simian/simian-2.5.10.jar -formatter=xml';

    const DUPLICATION_RANKING = [
        ['min' => 0, 'max' => 3, 'ranking' => '++', 'value' => 5],
        ['min' => 3, 'max' => 5, 'ranking' => '+', 'value' => 4],
        ['min' => 5, 'max' => 10, 'ranking' => 'o', 'value' => 3],
        ['min' => 10, 'max' => 20, 'ranking' => '-', 'value' => 2],
        ['min' => 20, 'max' => 100, 'ranking' => '--', 'value' => 1],
    ];


    public function __construct(Output $output)
    {
        parent::__construct($output);
    }

    public function calculate(Repository $repository, int $loc) : array
    {
        // check repository is cloned
        if (file_exists($this->checkoutDir.'/'.$repository->name)) {

            // calculate volume (loc) for Java files (min 6 lines), no json output available
            $tmpFile = tempnam(sys_get_temp_dir(), 'simian'.time());

            $command = 'java -jar '.self::SIMIAN_JAR.' -threshold=6 -formatter=xml:'.$tmpFile.' -defaultLanguage=java '.$this->checkoutDir.'/'.$repository->name.'/**/*.java';

            $this->writeToTerminal('Executing command: ' . $command);
            exec($command, $output);

            if ($this->verbose) {
                $this->writeToTerminal('Tmp-file: '.$tmpFile);

                foreach ($output as $line) {
                    $this->writeToTerminal($line);
                }
            }


            if (file_exists($tmpFile)) {
                $xmlOutput = file_get_contents($tmpFile);
                $duplication = simplexml_load_string($xmlOutput);

                if ($duplication !== false) { // valid XML

                    $duplicateLineCount = (int)$duplication->check->summary['duplicateLineCount'] ?? 0;
                    $duplicateBlockCount = (int)$duplication->check->summary['duplicateBlockCount'] ?? 0;

                    // calculate duplication percentage
                    $duplicationPercentage = round(($duplicateLineCount / $loc) * 100);

                    // calculate duplication ranking
                    $ranking = $this->getDuplicationRanking($duplicationPercentage);

                    return [
                        'duplication_line_count' => $duplicateLineCount,
                        'duplication_block_count' => $duplicateBlockCount,
                        'duplication_percentage' => $duplicationPercentage,
                        'sig_duplication_ranking' => $ranking['ranking'] ?? 'o',
                        'sig_duplication_ranking_numeric' => $ranking['value'] ?? 3,
                    ];

                }

                unlink($tmpFile);
            }


        }

        return [];
    }

    private function getDuplicationRanking(float $duplicationPercentage) : ?array
    {
        foreach (self::DUPLICATION_RANKING as $ranking) {
            if ($duplicationPercentage >= $ranking['min'] && $duplicationPercentage < $ranking['max']) {
                return $ranking;
            }
        }
        return null;
    }

}
