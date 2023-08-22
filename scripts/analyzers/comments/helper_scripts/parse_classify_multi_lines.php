<?php

require('find_multiple_strings.php');

if ($argc > 1) {
    $tempFile = $argv[1];
    $content = file_get_contents($tempFile);
    // explode multi line items
    $multiLines = explode("###===###", $content);
    $parsedLines = [];

    foreach ($multiLines as $multiLine) {

        $parsedLine = '';
        $multiLine = explode("\n", $multiLine);
        foreach ($multiLine as $key => $line) {
            // trim
            $tempLine = trim($line);
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
            $tempLine = str_replace('"', '\"', $tempLine);

            $parsedLine .= $tempLine.' ';
        }

        if (!empty(trim($parsedLine))) {

            $classification = 'RELEVANT';
            if (strposa(strtoupper($parsedLine), ['LICENSE', 'COPYRIGHT', 'AUTHOR'])) {
                $classification = 'COPYRIGHT';
            }

            $parsedLines[] = 'multi_line,"' . trim($parsedLine) . '",'.$classification;
        }

    }

    // save
    if (count($parsedLines)) {
        file_put_contents($tempFile, implode("\n", $parsedLines) . "\n");
    }
    else {
        file_put_contents($tempFile, '');
    }

}



