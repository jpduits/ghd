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

# check second argument is uuid else generate
if [ ! -f "$2" ]; then
    uuid=$(uuidgen)
else
    uuid=$2
fi


# Loop import file
counter=1

while IFS= read -r line; do
    echo "($counter) Get repository: $line"
    echo "Current time: $(date +%Y-%m-%d_%H-%M-%S)"
    php ghdataset get:project-state $line --start-date=2020-01-01 --run-uuid=$uuid --output-format=csv
    counter=$((counter+1))
done < "$1"
