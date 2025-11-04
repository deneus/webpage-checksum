#!/bin/sh
##
## This script converts the .po files into sorted files of msgid, msgstr pairs
## which are easier for git diff to parse. This results in an easier to analyse
## git diff output.
##

# Retrieve the file
cat $1 |
# Ensure that multiline msgstr or msgid values are on a single line
  perl -p0e 's/"\n"//g' |
# Retrieve all strings with their translation
  grep -E 'msgid|msgstr' |
# Join those two lines to a single line
  paste -d "\t\t\t"  - - |
# Then sort this so it's sorted by msgid
  sort |
# Then move them back to two lines
  tr "\t\t\t" "\n"
