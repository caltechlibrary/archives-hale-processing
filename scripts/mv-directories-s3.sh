#!/bin/bash

# to be run from cls-image

# display message when no arguments are given
if [ $# == 0 ]; then
  printf "\n\e[1;91m😵  Error.\e[0m Supply the path to the parent directory of the books.\n"
  printf "Example: mv-directories-s3.sh /home/tkeswick/Workspace/Hale/3folders\n\n"
  exit 1
fi

# @TODO parse `aws s3 ls` command in case the NAS folder structure is not the same as S3
for path in "$1"/*; do
  [ -d "${path}" ] || continue # if not a directory, skip
  folder="$(basename "${path}")"
  parent_path="$(dirname "${path}")"
  parent_folder="$(basename "${parent_path}")"
  if [[ $folder = *"_SET" ]]; then
    echo -e "🚫  skipping \033[100m${folder}\033[0m"
  else
    echo -e "nesting \033[100m${folder}\033[0m ..."
    # exclude destination path or aws will find new items and nest infinitely
    aws s3 mv \
      s3://stage-hale-archives-caltech-edu/"${parent_folder}/${folder}" \
      s3://stage-hale-archives-caltech-edu/"${parent_folder}/${folder}_SET/${folder}" \
      --recursive \
      --exclude '*.DS_Store*' \
      --grants full=emailaddress=tkeswick@caltech.edu
    echo "🤖"
  fi
done
