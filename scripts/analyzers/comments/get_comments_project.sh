#!/bin/bash

# get data to train ML model
train=false

# Check if the --train option is provided
if [[ "$1" == "--train" ]]; then
  train=true
  shift  # Shift to remove the --train option from arguments
  echo "Parse data for model training"
fi


# check argument
if [ $# -ne 1 ]; then
  echo "Add project directory as argument"
  exit 1
fi

# check if directory exists
if [ ! -d"$1" ]; then
  echo "Directory does not exist"
  exit 1
fi


current_dir="$(dirname "$0")"
echo "current dir=$current_dir"

if $train; then

    output_file="$current_dir/processed/comments_training_data.csv"

    file_size=$(stat -c %s "$output_file")
    echo "File size: $file_size"
    if [ $file_size -eq 0 ]; then
        echo "comment_type,comment,classification" > "$output_file"
    fi

else

    # get hash of GIT commit
    suffix=($(cd $1 && git rev-parse HEAD))
    output_file="$current_dir/processed/comments_$suffix.csv"
    # clear file
    echo -n "" > "$output_file"

fi


# Regex for singline line comments, it ignores // if it is preceded by a : (to ignore links)
single_line_comment_regex='(?<!:)\/\/.*'

# Regex to get all multiline comments
multi_line_comment_regex='\/\*([^*]|(\*+([^*\/]|$)))*\*+\/'

# Loop over all java files in the project directory
#echo "Process: $1"
# -o is OR
find "$1" -type d -name "test" -prune -o -type d -name "tests" -prune -o -type f -name "*.java" | while IFS= read -r java_file; do

    # check it is a file, not a directory
    if [[ -f "$java_file" ]]; then


        # -E extended regex
        # -o only matching
        # first sed: escape double quotes
#                                                                                                                                                                         sed 's/^/"/; s/$/",/'
        single_line=$(grep -aPo "$single_line_comment_regex" "$java_file" | sed 's#\/\/\|\/\/ ##g' | sed -e 's/^[[:space:]]*//' -e 's/[[:space:]]*$//' | sed 's/"/\`/g' | sed 's/^/"/; s/$/"/' | sed 's/.*/single_line,&/')

        if [ -n "$single_line" ]; then

            temp_file=$(mktemp)
            echo -n "$single_line" > "$temp_file"

            # parse tempfile
            if $train; then
                php $current_dir/helper_scripts/parse_classify_single_line.php "$temp_file" --train
            else
                php $current_dir/helper_scripts/parse_classify_single_line.php "$temp_file"
            fi

            cat $temp_file >> "$output_file"
            rm "$temp_file"

        fi


        # -P perl regex
        # -z null byte als line terminator
        # -a behandel als tekst
        # tr -d '\0' removes null bytes
        multi_line="$(grep -Poz "$multi_line_comment_regex" "$java_file" | tr -d '\0' | sed 's/\*\//&\n###===###\n/')"


    #    multi_line="$(grep -aPoz "$multi_line_comment_regex" "$java_file" | sed 's/\*\//&\n###===###\n/')"

        temp_file=$(mktemp)

        if [ -n "$multi_line" ]; then
            echo "$multi_line" >> "$temp_file"
        fi

        # parse tempfile to make multiline comments one line
        if $train; then
            php $current_dir/helper_scripts/parse_classify_multi_lines.php "$temp_file" --train
        else
            php $current_dir/helper_scripts/parse_classify_multi_lines.php "$temp_file"
        fi



        cat $temp_file >> "$output_file"

        rm "$temp_file"


    fi

done

echo $output_file
