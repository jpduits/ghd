#!/bin/bash

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

# Output file for debugging
output_file="processed/comments.csv"

# Java for testing
#java_file="/home/jp/tmp_checkouts/imagej-troubleshooting/src/main/java/net/imagej/trouble/visible/E2EffectiveExpressions.java"

# Regex for singline line comments, it ignores // if it is preceded by a : (to ignore links)
single_line_comment_regex='(?<!:)\/\/.*'
# Regex to get all multiline comments
multi_line_comment_regex='\/\*([^*]|(\*+([^*\/]|$)))*\*+\/'

#echo "DIRECTORY: $1" > "$output_file"
# first line, if empty file
file_size=$(stat -c %s "$output_file")
echo "File size: $file_size"
if [ $file_size -eq 0 ]; then
    echo "comment_type,comment,classification" > "$output_file"
#else
   # echo "\n" >> "$output_file"
fi

# Loop over all java files in the project directory
# -o is OR
find "$1" -type d -name "test" -prune -o -type d -name "tests" -prune -o -type f -name "*.java" | while IFS= read -r java_file; do

    #echo "Parsing file: $java_file"
    #echo "FILE_NAME: $java_file" >> "$output_file"

    # Grep om enkelregelige commentaren te vinden en in een apart bestand te plaatsen
    # -E extended regex
    # -o only matching
    # first sed: escape double quotes
    single_line=$(grep -Po "$single_line_comment_regex" "$java_file" | sed 's#\/\/\|\/\/ ##g' | sed -e 's/^[[:space:]]*//' -e 's/[[:space:]]*$//' | sed 's/"/\\"/g' | sed 's/^/"/; s/$/",/' | sed 's/.*/single_line,&/')

    #echo "$single_line" >> "$output_file"
    # remove \\ from single line comments
    #single_line=$(echo "$single_line" | sed 's#\/\/\|\/\/ ##g')

    # trim de string
    #single_line=$(echo "$single_line" | sed -e 's/^[[:space:]]*//' -e 's/[[:space:]]*$//')

    if [ -n "$single_line" ]; then
        temp_file=$(mktemp)
        echo -n "$single_line" > "$temp_file"

        php helper_scripts/parse_classify_single_line.php "$temp_file"
        cat $temp_file >> "$output_file"
        rm "$temp_file"

    fi

    # Grep om meerdere regels commentaar te vinden en in een apart bestand te plaatsen
    # -P perl regex
    # -z null byte als line terminator
    multi_line="$(grep -Poz "$multi_line_comment_regex" "$java_file" | sed 's/\*\//&\n###===###\n/')"
    #
#echo $multi_line
  # multi_line=$(echo "$multi_line" | tr -d '\n' | sed 's#/\*#/\n\*#g' | sed 's#\*/#\*/\n#g' | sed '/^\/\*/d' | sed '/^\*\//d')
 #   multi_line=$(echo "$multi_line" | tr -d '\n' | sed 's#/\*#/\n\*#g' | sed 's#\*/#\*/\n#g' | sed '/^\/\*/d' | sed '/^\*\//d' | sed '/^ *\*/d' | sed 's/^\*//g')

    temp_file=$(mktemp)

    #echo $temp_file;
    #| sed 's/.*/MULTI_LINE: &/'
    if [ -n "$multi_line" ]; then
    echo "$multi_line" >> "$temp_file"
    fi

    # parse tempfile to make multiline comments one line
    php helper_scripts/parse_classify_multi_lines.php "$temp_file"

    cat $temp_file >> "$output_file"

    rm "$temp_file"

done

# SED om meerdere regels commentaar te verwijderen uit het oorspronkelijke bestand
#sed -i -E "s/$multi_line_comment_regex//g" "$java_file"
