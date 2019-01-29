#!/bin/bash

# open html-elements.txt and loop through lines

# # with spaces after tag
# while read -r e; do
#   echo "<${e} "
#   grep -inr "<${e} " /Users/tkeswick/Development/archives/hale/data/page_ocr | wc -l
# done <html-elements.txt

# # without spaces after tag
# while read -r e; do
#   echo "<${e}"
#   grep -inr "<${e}" /Users/tkeswick/Development/archives/hale/data/page_ocr | wc -l
# done <html-elements.txt

# # closing tag
# while read -r e; do
#   echo "${e}>"
#   grep -inr "${e}>" /Users/tkeswick/Development/archives/hale/data/page_ocr | wc -l
# done <html-elements.txt


# # only loop through elements in found list
# # https://stackoverflow.com/a/2664746/4100024
# while read -r e; do
#   echo "<${e} "
#   grep -ilr "<${e}" /Users/tkeswick/Development/archives/hale/data/page_ocr | while read -r f; do
#     echo "${f##*/}" >> /Users/tkeswick/Development/archives/hale/data/moved.txt
#     cp -a "/Users/tkeswick/Development/archives/hale/data/page_ocr/${f##*/}" "/Users/tkeswick/Development/archives/hale/data/html/"
#   done
# done <html-elements-found.txt
# sort -u -o /Users/tkeswick/Development/archives/hale/data/html.txt /Users/tkeswick/Development/archives/hale/data/moved.txt
# rm /Users/tkeswick/Development/archives/hale/data/moved.txt

echo "---"
while read -r f; do
  echo "-"
  echo "    file: ${f}"
  /Users/tkeswick/Development/archives/hale/scripts/ocre/ocre/ocre.py /Users/tkeswick/Development/archives/hale/data/html/"${f}"
done </Users/tkeswick/Development/archives/hale/data/html.txt
