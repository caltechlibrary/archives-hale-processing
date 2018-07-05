#!/bin/bash

# Use the directory supplied and then loop through every file in it to rename
# according to the pattern in the `mv` command. For example, `hale_230_JP2.jp2`
# becomes `hale_230_OBJ.jp2`, etc.

# display message when no arguments are given
if [ $# == 0 ]; then
  printf "\n\e[1;91m😵  Error.\e[0m Supply the path to the directory of the JP2s.\n"
  printf "Example: jp2-to-obj.sh /tmp/jp2_files\n\n"
  exit 1
fi

for file in "$1"/*; do
  if [[ $file == *.jp2 ]]; then
    echo -e "renaming \033[100m${file}\033[0m ..."
    mv "$file" "${file/_JP2/_OBJ}"
    echo "🤖"
  fi
done
