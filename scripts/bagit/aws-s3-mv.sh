#!/usr/bin/env bash

# We loop over a directory and process validated folders, indicated by the
# presence of a file in the validated directory.

# display message when no arguments are given
if [[ $# == 0 ]]; then
    printf "\n\e[1;91m😵 error:\e[0m supply the path of the processing output directory\n"
    printf "➡️  example: bash aws-s3-mv.sh /path/to/directory\n\n"
    exit 1
fi

# @TODO we could capture more command line input to abstract paths

for file in "$1"/validated/HaleGE_*; do

    echo ${file}

    # move structure to S3

    #
    filename=${file##*/}
    echo ${filename}
    # the structure to move is located at /path/to/directory/${filename}/HaleGE/data
    #
    # @TODO we could technically parse the beginning of the ${filename} for the
    # 'HaleGE' part, but that seems unnecessary right now
    directory_to_move="${1}/${filename}/HaleGE/data"

    aws s3 mv "${directory_to_move}" s3://archives-bagit-tmp/HaleGE/data --recursive --exclude '*.DS_Store*'

    if [[ $? -eq 0 ]]; then
        rm ${file}
    fi

done
