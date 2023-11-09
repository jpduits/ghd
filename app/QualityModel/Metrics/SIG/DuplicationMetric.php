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

            $time = time();
            $tempFileList = tempnam(sys_get_temp_dir(), 'simian_filelist_'.$time);
            $this->createFileListForRepository($this->checkoutDir . '/' . $repository->name, $tempFileList);
/*            $command = 'find ' . $this->checkoutDir . '/' . $repository->name . ' -type d \( -name "*test*" -o -name "*tests*" \) -prune -false -o -type f -name "*.java" -not -name "*Test*.java" > '.$tempFileList;
            $this->writeToTerminal('Executing command: '.$command); // generate filelist to filter Simian result
            exec($command);*/
            $fileList = explode("\n", file_get_contents($tempFileList));


            // calculate volume (loc) for Java files (min 6 lines), no json output available
            $simianResponseFile = tempnam(sys_get_temp_dir(), 'simian'.time());

            // simian does not support filelists, so we analyze alle java files, and filter them later
            $command = 'java -jar '.self::SIMIAN_JAR.' -threshold=6 -formatter=xml:'.$simianResponseFile.' -defaultLanguage=java '.$this->checkoutDir.'/'.$repository->name.'/**/*.java';

            $this->writeToTerminal('Executing command: ' . $command);
            exec($command, $output);

            if ($this->verbose) {
                $this->writeToTerminal('Tmp-file: '.$simianResponseFile);

                foreach ($output as $line) {
                    $this->writeToTerminal($line);
                }
            }


            if (file_exists($simianResponseFile)) {
                $xmlOutput = file_get_contents($simianResponseFile);
                $duplication = simplexml_load_string($xmlOutput);

                if ($duplication !== false) { // valid XML

                    $duplicateLineCount = 0;
                    $duplicateBlockCount = 0;

                    $this->writeHorizontalLineToTerminal();

                    foreach ($duplication->check->set as $set) {

                        // true if current file is in de filelist (so tests will not includes etc.)
                        $foundSourceFiles = 0;
                        $message = [];
                        foreach ($set->block as $block) {

                            if (!in_array($block['sourceFile'], $fileList))  {
                                // source file not in array
                                $this->writeToTerminal('* skipped file ' . $block['sourceFile'].' for duplication count');
                            }
                            else {
                                $message[] = '# use file (startline: '.$block['startLineNumber'].') ' . $block['sourceFile'].' for duplication count';
                                $foundSourceFiles++;
                            }

                        }

//                        $countDuplicate = count($set->block);

                        if ($foundSourceFiles > 1) {

                            $this->writeToTerminal(implode(PHP_EOL, $message));
                            $duplicateLineCount += (int)$set['lineCount'] ?? 0;
                            $duplicateBlockCount+= ($foundSourceFiles);
                            $this->writeToTerminal('% Total duplication line count: '.$duplicateLineCount);
                            $this->writeToTerminal('% Total duplication block count: '.$duplicateBlockCount);
                            $this->writeToTerminal('% Files with duplications: '.count($message) ?? 0);
                        }

                        $this->writeHorizontalLineToTerminal();


                    }
                    $this->writeHorizontalLineToTerminal();

                    // calculate duplication percentage
                    $duplicationPercentage = round((($duplicateLineCount / $loc) * 100), 2);

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

                //unlink($tmpFile);
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
