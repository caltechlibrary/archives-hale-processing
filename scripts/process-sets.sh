#!/bin/bash
# continuously read a file that is changing in between loops

path="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

line=1
# infinite loop
while :
do
  # prints contents of line from file
  set_number=$(sed -n "$line p" "$path"/setslist)
  if [ "$set_number" == 'end' ]; then
    echo "...end of set list"
    exit;
  fi
  echo -e "\n\e[106m start set ${set_number} \e[0m\n"
  time drush ibi --user=1 --ingest_set="$set_number"
  # move to the next line for the next iteration
  (( line=line+1 ))
done
