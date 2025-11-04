#!/usr/bin/env bash
# This file provides a method to install a Drupal and Drush installation that
# allow the other executables to run the translation extraction.

###########################################################################
# Set-up and requirements checking                                        #
###########################################################################
# Store a reference to the current working directory to restore this later
START=`pwd`

# Store a reference to the directory of this script so we can resolve paths from it.
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null && pwd )"

# Include our utility functions.
. $DIR/../lib/util.sh

# Check that the tools we need are present
require_composer

if [ $# -eq 0 ]; then
  echo "Invalid arguments" 1>&2
  echo "" 1>&2
  echo "usage: ${BASH_SOURCE[0]} extractor_path" 1>&2
  echo "" 1>&2
  echo "  extractor_path    The path POTX should be installed to" 1>&2
  echo "" 1>&2
  exit 1
fi

INSTALL_DIR=$1
if [ ! -w "$INSTALL_DIR" ]; then
  echo "Can not write to '$INSTALL_DIR'" 1>&2
  exit 2
fi

###########################################################################
# Installation                                                            #
###########################################################################
cp $DIR/../data/composer.{json,lock} $INSTALL_DIR
mkdir -p $INSTALL_DIR/html/sites/default
cp $DIR/../data/drupal/{services.yml,settings.php,.ht.sqlite} $INSTALL_DIR/html/sites/default
cd $INSTALL_DIR
composer install
