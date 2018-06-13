#!/bin/bash

# display message when no arguments are given
if [ $# == 0 ]; then
  printf "\n\e[1;91m😵  Error.\e[0m Supply the path to the parent directory of the books.\n"
  printf "Example: mv-directories-nas.sh /mnt/s3/boxes-001-012\n\n"
  exit 1
fi

for path in "$1"/*; do
  [ -d "${path}" ] || continue # if not a directory, skip
  folder="$(basename "${path}")"
  parent="$(dirname "${path}")"
  if [[ $folder = *"__SET" ]]; then
    echo -e "🚫  skipping \033[100m${folder}\033[0m"
  else
    echo -e "nesting \033[100m${folder}\033[0m ..."
    mv -v "${parent}/${folder}" "${parent}/${folder}_tmp"
    mkdir -p "${parent}/${folder}__SET"
    mv -v "${parent}/${folder}_tmp" "${parent}/${folder}__SET/${folder}"
    echo "🤖"
  fi
done
