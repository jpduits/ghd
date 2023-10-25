<?php

$content = file_get_contents('exchange-core_until_2019_01_01.txt');

$content = json_decode($content, true);


foreach ($content as $commit) {
    echo $commit['commit']['author']['name']. ' ' . $commit['commit']['author']['email'] . ' ' .$commit['commit']['author']['email'].PHP_EOL;


}
