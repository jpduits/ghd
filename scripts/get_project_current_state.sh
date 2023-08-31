#!/bin/bash

# check argument
if [ $# -ne 1 ]; then
  echo "Add a file as argument"
  exit 1
fi

# check if file exists
if [ ! -f "$1" ]; then
  echo "File does not exist"
  exit 1
fi



# Loop file
counter=1

uuid=$(uuidgen)

while IFS= read -r line; do
    echo "($counter) Get repository: $line"
    echo "Current time: $(date +%Y-%m-%d_%H-%M-%S)"
    cd.. && php ghdataset get:project-state $line --start-date=2023-01-01 --run-id=$uuid --output-format=csv
    counter=$((counter+1))
done < "$1"
