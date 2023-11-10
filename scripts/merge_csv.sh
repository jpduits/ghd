#!/bin/bash

# check argument
if [ $# -ne 1 ]; then
  echo "Add a directory as argument"
  exit 1
fi

# check if file exists
if [ ! -d "$1" ]; then
  echo "Directory does not exist"
  exit 1
fi


output="$1/merged/merged_$(date +%Y-%m-%d_%H-%M).csv"
touch $output

lineStart=1 # first item add headers

for file in $1/*.csv; do
    echo "Merge file: $file"
    cat $file | tail -n +$lineStart >> $output
    lineStart=2
done

echo "File saved as: $output"
