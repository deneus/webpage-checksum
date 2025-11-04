#!/usr/bin/env bash
# This lists the changes in translations files in the current working directory.
set -e

###########################################################################
# Set-up and requirements checking                                        #
###########################################################################
# Store a reference to the current working directory to restore this later
START=`pwd`

# Store a reference to the directory of this script so we can resolve paths from it.
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null && pwd )"

# Include our utility functions.
. $SCRIPT_DIR/../lib/util.sh

if [[ $# -lt 1 ]]; then
  echo "Invalid arguments" 1>&2
  echo "" 1>&2
  echo "usage: ${BASH_SOURCE[0]} diff_spec" 1>&2
  echo "" 1>&2
  echo "  diff_spec       accepts the same arguments as 'git diff'." 1>&2
  exit 1
fi

# Accept relative paths for our changes.
DIFF_SPEC=${@:1}

###########################################################################
# Commit the changes in the current working directory                     #
###########################################################################
GIT_DIR=`git rev-parse --show-toplevel`

list_changes $SCRIPT_DIR $DIFF_SPEC

# Restore the working directory.
cd $START
