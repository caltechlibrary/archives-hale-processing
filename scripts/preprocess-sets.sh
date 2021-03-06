#!/bin/bash

# display message when no arguments are given
if [ $# == 0 ]; then
  printf "\n\e[0;31m😵 Error.\e[0m Supply the path to the parent directory of the books.\n"
  printf "Example: preprocess-sets.sh /mnt/s3/boxes-001-012\n\n"
  exit 1
fi

for path in "${1}"/*; do
  [ -d "${path}" ] || continue # if not a directory, skip
  folder="$(basename "${path}")"
  # make sure $folder is a nested set
  if [[ $folder = *"__SET" ]]; then
    time drush ibbp --user=1 --type=directory --output_set_id --parent=caltech:hale --scan_target="${1}/${folder}"
    echo -e "\n${folder} 🤖\n"
  fi
done
