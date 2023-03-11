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

LOGFILE="./logs/logfile_$(date +%Y-%m-%d_%H-%M-%S).log"

# Loop file
while IFS= read -r line; do
    echo "Get repository: $line"
    echo "Current time: $(date +%Y-%m-%d_%H-%M-%S)"
    php ../ghdataset get:repository $line >> "$LOGFILE"
done < "$1"
