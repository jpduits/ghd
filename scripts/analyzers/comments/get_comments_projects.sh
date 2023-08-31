#!/bin/bash

base_dir="/home/jp/tmp_checkouts/"
checkout_directories=("Digital" "fastjson" "imagej-troubleshooting" "nacos" "gson" "javapoet" "jsoup" "Smack" "Paper" "maven-mvnd" "sofa-bolt" "brave" "rsocket-java" "jimfs" "lite-rx-api-hands-on" "jfreechart" "github-api" "cron-utils" "LyricViewDemo" "swiftp")

# clear output file
output_file="processed/comments.csv"
echo -n "" > "$output_file"

for dir in "${checkout_directories[@]}"; do
    echo "Process: $base_dir$dir"

    ./get_comments_project.sh --train "$base_dir$dir"
done
