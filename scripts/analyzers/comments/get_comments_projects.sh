#!/bin/bash

base_dir="/home/jp/tmp_checkouts/"
checkout_directories=("Digital" "fastjson" "imagej-troubleshooting")

# clear output file
output_file="processed/comments.csv"
echo -n "" > "$output_file"

for dir in "${checkout_directories[@]}"; do
    echo "Process: $base_dir$dir"

    ./get_comments_project.sh "$base_dir$dir"
done
