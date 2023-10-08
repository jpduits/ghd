#!/bin/bash

base_dir="/home/jp/tmp_checkouts/"
checkout_directories=("Digital" "fastjson" "imagej-troubleshooting" "nacos" "gson" "javapoet")

# clear output file
output_file="processed/comments.csv"
echo -n "" > "$output_file"

current_dir="$(dirname "$0")"



for dir in "${checkout_directories[@]}"; do
    echo "Process: $base_dir$dir"

    $current_dir/get_comments_project.sh --train "$base_dir$dir"
done
