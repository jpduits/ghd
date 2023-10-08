<?php

require('find_multiple_strings.php');

if ($argc > 1) { // $argc is the argument count

    // Controleer of de --train optie aan het einde van de argumentenlijst staat
    if (end($argv) == "--train") {
        echo "Training mode\n";
        array_pop($argv);  // Verwijder de --train optie uit de argumentenlijst
        $train = true;
    } else {
        $train = false;
    }


    $tempFile = $argv[1];

    if (file_exists($tempFile)) {

        $content = file_get_contents($tempFile);
        // explode multi line items
        $multiLines = explode("###===###", $content);
        $parsedLines = [];

        foreach ($multiLines as $multiLine) {

            $parsedLine = '';
            $multiLine = explode("\n", $multiLine);
            foreach ($multiLine as $key => $line) {
                // trim
                $tempLine = trim(strtolower($line));
                // remove /* and */
                $tempLine = str_replace("/*", "", $tempLine);
                $tempLine = str_replace("*/", "", $tempLine);
                // remove first char if *
                if (substr($tempLine, 0, 1) == '*') {
                    $tempLine = trim(substr($tempLine, 1));
                }
                // remove newlines
                //$tempLine = str_replace("\n", ' ', $tempLine);
                // escape "
                $tempLine = str_replace('"', '`', $tempLine);

                $parsedLine .= $tempLine . ' ';
            }

            if (!empty(trim($parsedLine))) {


                if ($train) {

                    $classification = 'RELEVANT';
                    if (strposa(strtoupper($parsedLine), ['LICENSE', 'COPYRIGHT', 'AUTHOR'])) {
                        $classification = 'COPYRIGHT';
                    }

                    $parsedLines[] = 'multi_line,"' . trim($parsedLine) . '",' . $classification;

                }
                else {

                    $parsedLines[] = 'multi_line,"' . trim($parsedLine) . '"';

                }

            }


        }

        // save
        if (count($parsedLines)) {
            file_put_contents($tempFile, implode("\n", $parsedLines) . "\n");
        }
        else {
            file_put_contents($tempFile, '');
        }


    } // file_exists

    else {
        echo 'File does not exist: ' . $tempFile . "\n";
    }
}



