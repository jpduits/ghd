<?php

require('find_multiple_strings.php');

if ($argc > 1) {

    // Controleer of de --train optie aan het einde van de argumentenlijst staat
    if (end($argv) == "--train") {
        array_pop($argv);  // Verwijder de --train optie uit de argumentenlijst
        $train = true;
    } else {
        $train = false;
    }

    $tempFile = $argv[1];

    if (file_exists($tempFile)) {

        $content = file_get_contents($tempFile);

        $lines = explode("\n", $content);

        $classifiedLines = [];

        foreach ($lines as $line) {

            $line = trim(strtolower($line));

            if (strpos($line, ',""') === false) {

                if ($train) {

                    $classification = 'RELEVANT';
                    if (strposa(strtoupper($line), ['LICENSE', 'COPYRIGHT', 'AUTHOR'])) {
                        $classification = 'COPYRIGHT';
                    }

                    $classifiedLines[] = $line . $classification;

                }
                else {
                    $classifiedLines[] = $line;
                }


            }


        }

        if (count($classifiedLines)) {
            file_put_contents($tempFile, implode("\n", $classifiedLines) . "\n");
        }

    }

}
