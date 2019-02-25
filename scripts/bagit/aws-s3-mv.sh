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

echo "🐞 begin script"
touch "$1"/aws-s3-mv.running

while [[ -f "$1"/aws-s3-mv.running ]]; do
echo "🐞 begin while"

    # move any validated files into a unique processing folder
    processing_directory=${1}/processing_${RANDOM}
echo "$processing_directory"
    mkdir -p "$processing_directory"
    mv "$1"/validated/HaleGE_* "$processing_directory"/

    for file in "$processing_directory"/HaleGE_*; do
echo "🐞 begin for"

        echo ${file}

        # get the filename only, without the full path
        filename=${file##*/}

        # the structure to move is located at /path/to/directory/${filename}/HaleGE/data
        #
        # @TODO we could technically parse the beginning of the ${filename} for the
        # 'HaleGE' part, but that seems unnecessary right now
        directory_to_move="${1}/${filename}/HaleGE/data"

        # move into collection structure
        mkdir -p "${1}/HaleGE/data"
        cp -arl "${directory_to_move}" "${1}/HaleGE/"

        # move structure to S3
echo '🐞 aws s3 cp "${directory_to_move}" s3://archives-bagit-tmp/HaleGE/data --recursive --exclude "*.DS_Store*" --no-progress'
        aws s3 cp "${directory_to_move}" s3://archives-bagit-tmp/HaleGE/data --recursive --exclude '*.DS_Store*' --no-progress

        if [[ $? -eq 0 ]]; then
echo "🐞 rm ${file}"
echo "🐞 rm -r ${1}/${filename}"
            rm ${file}
            rm -r "${1}/${filename}"
        fi
echo "🐞 end for"
    done

    rm -r "$processing_directory"

    if [[ -f "$1"/aws-s3-mv.running ]]; then
echo "🐞 rm ${1}/aws-s3-mv.running"
        rm "$1"/aws-s3-mv.running
    fi

echo "🐞 end while"
done

echo "🐞 end script"
