<?php

namespace App\QualityModel\Metrics\SIG;

use App\Models\Repository;
use App\QualityModel\Metrics\BaseMetric;
use Symfony\Component\Console\Output\Output;

class CC_UnitSizeMetric extends BaseMetric
{
    const COMPLEXITY_RISK_EVALUATION = [
        ['min' => 1, 'max' => 10, 'risk' => 'low'],
        ['min' => 11, 'max' => 20, 'risk' => 'moderate'],
        ['min' => 21, 'max' => 50, 'risk' => 'high'],
        ['min' => 51, 'max' => 99999, 'risk' => 'very_high']
    ];

    const UNIT_SIZE_RISK_EVALUATION = [
        ['min' => 0, 'max' => 30, 'risk' => 'low'],
        ['min' => 30, 'max' => 44, 'risk' => 'moderate'],
        ['min' => 44, 'max' => 74, 'risk' => 'high'],
        ['min' => 74, 'max' => 99999999, 'risk' => 'very_high']
    ];

    const COMPLEXITY_RELATIVE_RANKING = [
/*        ['moderate' => 25, 'high' => 0, 'very_high' => 0, 'ranking' => '++' , 'value' => 5],
        ['moderate' => 30, 'high' => 5, 'very_high' => 0, 'ranking' => '+' , 'value' => 4],
        ['moderate' => 40, 'high' => 10, 'very_high' => 0, 'ranking' => 'o' , 'value' => 3],
        ['moderate' => 50, 'high' => 15, 'very_high' => 0, 'ranking' => '-' , 'value' => 2],
        ['moderate' => 100, 'high' => 100, 'very_high' => 100, 'ranking' => '--' , 'value' => 1],*/

        ['moderate' => 21, 'high' => 0, 'very_high' => 0, 'ranking' => '++' , 'value' => 5],
        ['moderate' => 30, 'high' => 5, 'very_high' => 0, 'ranking' => '+' , 'value' => 4],
        ['moderate' => 40, 'high' => 10, 'very_high' => 0, 'ranking' => 'o' , 'value' => 3],
        ['moderate' => 50, 'high' => 15, 'very_high' => 5, 'ranking' => '-' , 'value' => 2],
        ['moderate' => 100, 'high' => 100, 'very_high' => 100, 'ranking' => '--' , 'value' => 1],

    ];

    const UNIT_SIZE_RELATIVE_RANKING = [
/*        ['moderate' => 25, 'high' => 0, 'very_high' => 0, 'ranking' => '++' , 'value' => 5],
        ['moderate' => 30, 'high' => 5, 'very_high' => 0, 'ranking' => '+' , 'value' => 4],
        ['moderate' => 40, 'high' => 10, 'very_high' => 0, 'ranking' => 'o' , 'value' => 3],
        ['moderate' => 50, 'high' => 15, 'very_high' => 0, 'ranking' => '-' , 'value' => 2],
        ['moderate' => 100, 'high' => 100, 'very_high' => 100, 'ranking' => '--' , 'value' => 1],*/

        ['moderate' => 19.5, 'high' => 10.9, 'very_high' => 3.9, 'ranking' => '++' , 'value' => 5],
        ['moderate' => 26, 'high' => 15.5, 'very_high' => 6.5, 'ranking' => '+' , 'value' => 4],
        ['moderate' => 34.1, 'high' => 22.2, 'very_high' => 11, 'ranking' => 'o' , 'value' => 3],
        ['moderate' => 45.9, 'high' => 31.4, 'very_high' => 18.1, 'ranking' => '-' , 'value' => 2],
        ['moderate' => 100, 'high' => 100, 'very_high' => 100, 'ranking' => '--' , 'value' => 1],
    ];


    const PMD_BIN = '/home/jp/dev/bin/pmd-bin-7.0.0-rc1/bin/pmd';
    // const PMD_BIN = '/home/jp/dev/bin/pmd-bin-6.50.0/bin/run.sh pmd';

    const PMD_RULESET = 'scripts/analyzers/pmd/ruleset.xml';

    const CHECKSTYLE_JAR = './scripts/analyzers/checkstyle/checkstyle-10.12.3-all.jar';

    const CHECKSTYLE_RULES = 'scripts/analyzers/checkstyle/ghdataset_checks.xml';


    public function __construct(Output $output)
    {
        parent::__construct($output);
    }


    public function calculate(Repository $repository, int $loc = 0)
    {
        $locUnitSizeRisk = $locComplexityRisk = [
            'low' => 0,
            'moderate' => 0,
            'high' => 0,
            'very_high' => 0,
        ];

        $percentageUnitSizeRisk = $percentageComplexityRisk = [
            'low' => 0,
            'moderate' => 0,
            'high' => 0,
            'very_high' => 0,
        ];

        $cc = [];

        $time = time();
        $tempFile = tempnam(sys_get_temp_dir(), 'pmd'.$time);

        // save the file list to a temp file
        $tempFileList = tempnam(sys_get_temp_dir(), 'pmd_filelist_'.$time);
        $this->createFileListForRepository($this->checkoutDir . '/' . $repository->name, $tempFileList);
/*        $command = 'find ' . $this->checkoutDir . '/' . $repository->name . ' -type d \( -name "*test*" -o -name "*tests*" \) -prune -false -o -type f -name "*.java" -not -name "*Test*.java" > '.$tempFileList;
        $this->writeToTerminal('Executing command: '.$command); // generate filelist
        exec($command);*/

        $command = 'cat '.$tempFileList;
        $this->writeToTerminal('Executing command: '.$command); // read filelist to array
        exec($command, $filelistArray);

        $ruleset = base_path(self::PMD_RULESET);
        // $(find ' . $this->checkoutDir . '/' . $repository->name . ' -type f -name "*.java" -not -name "*Test.java")
        $command = self::PMD_BIN . ' check --file-list '.$tempFileList.' -f json -R ' . $ruleset . ' -r ' . $tempFile;
        // version 6.5
        // $command = self::PMD_BIN . ' --file-list '.$tempFileList.' -f json -R ' . $ruleset . ' -r ' . $tempFile;

        $this->writeToTerminal('Executing command: '.$command);
        exec($command, $output);

        unlink($tempFileList); // remove temp filelist

        if ($this->verbose) {
            $this->writeToTerminal('Tmp-file: '.$tempFile);

            foreach ($output as $line) {
                $this->writeToTerminal($line);
            }
        }

        // tmpFile contains the JSON PMD report
        if (file_exists($tempFile)) {
            $report = file_get_contents($tempFile);

            $pmdReportJsonResponse = json_decode($report, true);

            if (is_array($pmdReportJsonResponse) && array_key_exists('files', $pmdReportJsonResponse)) {

                // patterns to match the violations
                $ccPattern = "/The (method|constructor) '(.+)' has a cyclomatic complexity of (\d+)/";
                $ncssMethodPattern = "/The (method|constructor) '(.+)' has a NCSS line count of (\d+)/";
                $ncssClassPattern = "/The (class) '(.+)' has a NCSS line count of (\d+)/";

                // save the lines per unit with checkstyle tool
                $tempFileCheckStyle = tempnam(sys_get_temp_dir(), 'checkstyle_'.$time);
                $command = 'java -jar '.self::CHECKSTYLE_JAR.' -c '.self::CHECKSTYLE_RULES.' '.$this->checkoutDir.'/' . $repository->name . ' -o '.$tempFileCheckStyle.' /**/*.java';
                // -x ".*(/test/|/tests/|test\.java)"
                $this->writeToTerminal('Executing command: '.$command);
                $checkStylePattern =  '/\[(\w+)\] (.+):(\d+):\d+: (\w+)=(\d+) \[MethodLength\]/';

                exec($command, $output, $resultCode);

                $tempFileCheckStyleData = file_get_contents($tempFileCheckStyle);
                $checkStyleResponse = explode("\n", $tempFileCheckStyleData);
                $checkStyleResponse = array_filter($checkStyleResponse, function($auditLine) {
                    return ((str_contains($auditLine, '[ERROR]') !== false) && (str_contains($auditLine, 'Got an exception') === false));
                });


                $this->writeToTerminal('Result code Checkstyle: '.$resultCode);

                $methodsFound = 0;

                $methodCounter = 0;
                $currentFilePath = '';

                $checkStyleResults = [];

                foreach ($checkStyleResponse as $auditLine) {

                    // check line starts with [ERROR] $file['filename']:
                    if (preg_match($checkStylePattern, $auditLine, $matches)) {
                        //$errorType = $matches[1];
                        $filePath = $matches[2];
                        //$lineNumber = $matches[3];
                        $methodName = $matches[4];
                        $methodLength = $matches[5];

                        // check file is in filelist for PMD, so it uses the same set
                       // $this->writeToTerminal($filePath);

                        if (in_array($filePath, $filelistArray)) {

                            if ($filePath !== $currentFilePath) {
                                $methodCounter = 1;
                            }
                            else {
                                $methodCounter++;
                            }
                            $currentFilePath = $filePath;

                            $checkStyleResults[$filePath][$methodName . '_' . $methodCounter] = $methodLength;
                            $methodsFound++;
                        }

                    }

                }

                $this->writeToTerminal('Checkstyle results for: '.$methodsFound.' methods');

                $methodCounter = 0;
                $locUnitFileTotal = 0;
                $locUnitRepositoryTotal = 0;
                $currentFilePath = '';
                $currentMethodName = '';

                foreach ($pmdReportJsonResponse['files'] as $currentFile) {

                    // save for every file the complexity and loc_unit per method
                    $pmdResultsCurrentFile = [];

                    if ($currentFile['filename'] !== $currentFilePath) {
                        $methodCounter = 0;
                        $currentMethodName = '';
                        $locUnitFileTotal = 0;
                    }

                    // violations per file
                    foreach ($currentFile['violations'] as $violation) {

                        $line = $violation['beginline'];

                        switch ($violation['rule']) {

                            // if violation is a complexity violation, save the complexity value
                            case 'CyclomaticComplexity':

                                if (preg_match($ccPattern, $violation['description'], $matches)) {
                                    $methodName = $matches[2];

                                    if ($currentMethodName !== $methodName) {
                                        $methodCounter++;
                                    }
                                    $currentMethodName = $methodName;

                                    $complexityValue = $matches[3];
                                    $pmdResultsCurrentFile[$methodName.'_'.$methodCounter]['complexity'] = $complexityValue;

                                }
                                break;

                            // if violation is a loc violation, save the loc value
                            case 'NcssCount': // Non-Commenting Source Statements

                                if (preg_match($ncssMethodPattern, $violation['description'], $matches)) {
                                    $methodName = $matches[2];

                                    if ($currentMethodName !== $methodName) {
                                        $methodCounter++;
                                    }
                                    $currentMethodName = $methodName;

                                    $ncssValue = $matches[3];
                                    $pmdResultsCurrentFile[$methodName.'_'.$methodCounter]['ncss_unit'] = $ncssValue;

                                    // get value from Checkstyle array, for loc value (is different from ncss)
                                    // first get plain method name (without parameters)
                                    $methodNamePlain = strstr($methodName, '(', true);
                                    if ($methodNamePlain === false) {
                                        $methodNamePlain = strstr($methodName, '<', true);
                                    }

                                    // per file there is an array element with an array with method elements
                                    // so check in the array by current filename -> current_method
                                    if (($methodNamePlain !== false) && (array_key_exists($currentFile['filename'], $checkStyleResults)) && (array_key_exists($methodNamePlain.'_'.$methodCounter, $checkStyleResults[$currentFile['filename']]))) {
                                        $pmdResultsCurrentFile[$methodName.'_'.$methodCounter]['loc_unit'] = $checkStyleResults[$currentFile['filename']][$methodNamePlain.'_'.$methodCounter];
                                    }
                                    else {
                                        // if Checkstyle has an exception, use ncss value
                                        $pmdResultsCurrentFile[$methodName.'_'.$methodCounter]['loc_unit'] = $ncssValue;
                                        $pmdResultsCurrentFile[$methodName.'_'.$methodCounter]['exception'] = true;
                                    }

                                    $pmdResultsCurrentFile[$methodName.'_'.$methodCounter]['file'] = $currentFile['filename'];


                                }
                                else if (preg_match($ncssClassPattern, $violation['description'], $matches)) {
                                    $ncssLineCount =+ $matches[3];
                                }
                                break;

                        }


                    }

                    // now we have an array (results) with the complexity and loc_unit per method (unit) of the current file
                    // for every file loop, pmdResults is empty
                    foreach ($pmdResultsCurrentFile as $method => $measure) {

                        if (isset($measure['loc_unit']) && isset($measure['complexity'])) {

                            $complexity = (int)$measure['complexity'];
                            $locUnit = (int)$measure['loc_unit'];

                            echo '* '.$method . ' has loc_unit of ' . $locUnit . ' and complexity of ' . $complexity . PHP_EOL;

                            // get the risk of the complexity
                            $complexityRisk = $this->getComplexityRisk($complexity);
                            if ($complexityRisk) {
                                // add the loc_unit to the total loc of the complexity risk
                                $locComplexityRisk[$complexityRisk] += $locUnit;
                            }

                            // get the risk of the unit size
                            $unitSizeRisk = $this->getUnitSizeRisk($locUnit);
                            if ($unitSizeRisk) {
                                // add the loc_unit to the total loc of the unit size risk
                                $locUnitSizeRisk[$unitSizeRisk] += $locUnit;
                            }

                            $locUnitFileTotal += $locUnit;
                            $locUnitRepositoryTotal += $locUnit;

                        }
                        else {
                            $this->writeToTerminal('No loc unit or complexity measure result found', 'warning');
                        }

                    }

                    // store alle the results per file
                    // $cc[$file['filename']] = $results;

                    echo '# ' . basename($currentFile['filename']).' has total loc_unit (all methods) of '.$locUnitFileTotal.PHP_EOL;

                } // PMD files loop


                echo '% Repository has total loc_unit (all methods) of '.$locUnitRepositoryTotal.PHP_EOL;

                // based on the loc of each risk, calculate the percentage of the total loc

                // complexity
                foreach ($locComplexityRisk as $complexityRisk => $loc_risk) {
                    $percentageComplexityRisk[$complexityRisk] = round(($loc_risk * 100) / $loc, 3);
                }
                $cc['loc_complexity_per_risk'] = $this->arrayToString($locComplexityRisk);
                $cc['percentage_complexity_per_risk'] = $this->arrayToString($percentageComplexityRisk);

                // unit sizes
                foreach ($locUnitSizeRisk as $unitSizeRisk => $loc_risk) {
                    $percentageUnitSizeRisk[$unitSizeRisk] = round(($loc_risk * 100) / $loc, 3);
                }
                $cc['loc_unit_size_per_risk'] = $this->arrayToString($locUnitSizeRisk);
                $cc['percentage_unit_size_per_risk'] = $this->arrayToString($percentageUnitSizeRisk);

                // lines of code total
                $cc['loc_total'] = $loc;

                $complexityRanking = $this->getRanking(self::COMPLEXITY_RELATIVE_RANKING, $percentageComplexityRisk);
                $unitSizeRanking = $this->getRanking(self::UNIT_SIZE_RELATIVE_RANKING, $percentageUnitSizeRisk);

                $cc['sig_complexity_ranking'] = $complexityRanking['ranking'];
                $cc['sig_complexity_ranking_value'] = $complexityRanking['value'];

                $cc['sig_unit_size_ranking'] = $unitSizeRanking['ranking'];
                $cc['sig_unit_size_ranking_value'] = $unitSizeRanking['value'];



                // calculate the relative ranking of the complexity
/*                foreach (self::COMPLEXITY_RELATIVE_RANKING as $ranking) {

                    if (
                        ($percentageComplexityRisk['moderate'] <= $ranking['moderate']) &&
                        ($percentageComplexityRisk['high'] <= $ranking['high']) &&
                        ($percentageComplexityRisk['very_high'] <= $ranking['very_high'])
                    ) {

                        $cc['sig_complexity_ranking'] = $ranking['ranking'];
                        $cc['sig_complexity_ranking_value'] = $ranking['value'];
                        break;
                    }


                }*/

            }

            //unlink($tmpFile);
        }

//        $cc['total_loc'] = $ncssLineCount;

        return $cc;
    }

    private function arrayToString($array) : string
    {
        $string = '';
        foreach ($array as $key => $value) {
            $string .= $key.': '.$value." *** ";
        }
        return $string;
    }

    private function getComplexityRisk(int $complexity) : ?string
    {
        foreach (self::COMPLEXITY_RISK_EVALUATION as $risk) {
            if ($complexity >= $risk['min'] && $complexity <= $risk['max']) {
                return $risk['risk'];
            }
        }
        return null;
    }

    private function getUnitSizeRisk(int $locUnitValue) : ?string
    {
        foreach (self::UNIT_SIZE_RISK_EVALUATION as $risk) {
            if ($locUnitValue >= $risk['min'] && $locUnitValue <= $risk['max']) {
                return $risk['risk'];
            }
        }
        return null;
    }

    private function getRanking($relativeRankings, $percentages)
    {
        foreach ($relativeRankings as $ranking) {

            if (
                ($percentages['moderate'] <= $ranking['moderate']) &&
                ($percentages['high'] <= $ranking['high']) &&
                ($percentages['very_high'] <= $ranking['very_high'])
            ) {

                return $ranking;
            }
        }

    }

}
