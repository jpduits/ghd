<?php

if ($argc > 1) {

    $tempFile = $argv[1];

    if (file_exists($tempFile)) {

        $csv = [];
        $jsonData = json_decode(file_get_contents($argv[1]), true);

        echo 'Last error: ', json_last_error_msg(), PHP_EOL, PHP_EOL;

        foreach ($jsonData as $item) {
            $columns = [];


            /*
             * FORKS
            $columns[] = $item['id'];
            $columns[] = $item['name'];
            $columns[] = $item['full_name'];
            $columns[] = $item['created_at'];
            */

/*            // ISSUES
            $columns[] = $item['id'];
            $columns[] = $item['number'];
            $columns[] = $item['title'];
            $columns[] = $item['state'];
            $columns[] = $item['created_at'];*/

/*            // Commits
            $columns[] = $item['sha'];
            $columns[] = $item['commit']['author']['name'];
            $columns[] = $item['commit']['author']['date'];*/

            // pull requests
/*            $columns[] = $item['id'];
            $columns[] = $item['title'];
            $columns[] = $item['created_at'];
            $columns[] = $item['state'];*/

            // stargazers
            $columns[] = $item['starred_at'];
            $columns[] = $item['user']['id'];



            $csv[] = implode("\t", $columns);

        }

        $csvContent = implode(PHP_EOL, $csv);

        $path = pathinfo($argv[1], PATHINFO_DIRNAME);
        $filename = pathinfo($argv[1], PATHINFO_FILENAME);
        $filename = $path.'/'.$filename . '.csv';

        file_put_contents($filename, $csvContent);

        if (file_exists($filename)) {
            echo 'File '.$filename.' saved';
            return 0;
        }



    }
}
