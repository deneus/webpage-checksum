#!/usr/bin/env bash
# This only commits PO files that actually have word changes which avoids
# commits that update the timestamp, which updates for every action we
# take.
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

if [[ -z "$1" || $1 != "--yes" ]]; then
  export SKIP_GIT=1
  MESSAGE="${@:1}"
  echo "This would commit all new translations in:"
  echo "  $START"
  echo "Run again with '--yes' as first argument to force"
else
  MESSAGE="${@:2}"
fi

###########################################################################
# Commit the changes in the current working directory                     #
###########################################################################
GIT_DIR=`git rev-parse --show-toplevel`
COMMIT_DIR=${START/$GIT_DIR\//}

# This must be run from the git root to work properly.
cd $GIT_DIR

# Commit everything in the $COMMIT_DIR
commit_changes $COMMIT_DIR "$MESSAGE"

# Restore the working directory.
cd $START
