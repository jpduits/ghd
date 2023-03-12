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

while IFS= read -r line; do
    LOGFILE="./logs/logfile_$(date +%Y-%m-%d_%H-%M-%S).log"
    echo "($counter) Get repository: $line"
    echo "Current time: $(date +%Y-%m-%d_%H-%M-%S)"
    php ../ghdataset get:repository $line >> "$LOGFILE"
    counter=$((counter+1))
done < "$1"
