<?php

require('find_multiple_strings.php');

if ($argc > 1) {
    $tempFile = $argv[1];
    $content = file_get_contents($tempFile);

    $lines = explode("\n", $content);

    $classifiedLines = [];

    foreach ($lines as $line) {

        $classification = 'RELEVANT';
        if (strposa(strtoupper($line), ['LICENSE', 'COPYRIGHT', 'AUTHOR'])) {
            $classification = 'COPYRIGHT';
        }
        $classifiedLines[] = $line . $classification;


    }

    if (count($classifiedLines)) {
       file_put_contents($tempFile, implode("\n", $classifiedLines)."\n");
    }
}
