#!/usr/bin/env bash

###########################################################################
# Set-up and requirements checking                                        #
###########################################################################
# Store a reference to the current working directory to restore this later
START=`pwd`

# Store a reference to the directory of this script so we can resolve paths from it.
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null && pwd )"

# Tell the gettext tools where it can find more localisation information, such
# as plurality rules for new translations.
export GETTEXTCLDRDIR="${SCRIPT_DIR}/../data/cldr"

if [[ $# -ne 2 && $# -ne 3 ]]; then
  echo "Invalid arguments" 1>&2
  echo "" 1>&2
  echo "usage: ${BASH_SOURCE[0]} extractor_path project [translations]" 1>&2
  echo "" 1>&2
  echo "  extractor_path    The path to the Drupal installation with POTX" 1>&2
  echo "                      (result of setup-extractor.sh)" 1>&2
  echo "" 1>&2
  echo "  project           The module/theme/profile to extract translations from" 1>&2
  echo "" 1>&2
  echo "  translations      The folder to store the resulting translations in" 1>&2
  echo "                       defaults to \$project/translations" 1>&2
  echo "" 1>&2
  exit 1
fi

# Make all our paths absolute
if [[ "$1" == /* ]]; then
  DIR=$1
else
  DIR=$START/$1
fi
if [[ "$2" == /* ]]; then
  PROJECT=$2
else
  PROJECT=$START/$2
fi

# Default `translations` argument to "translations"
if [[ -z $3 ]]; then
  TARGET_FOLDER="$PROJECT/translations"
else
  TARGET_FOLDER=$3
fi
if [[ "$TARGET_FOLDER" != /* ]]; then
  TARGET_FOLDER="$START/$TARGET_FOLDER"
fi

COMPOSER_BIN=$DIR/vendor/bin

if [[ "$DIR" == */html || "$DIR" == */vendor ]]; then
  echo "'extractor_path' should point to the path passed to 'setup-extractor.sh' not a subfolder." 1>&2
  exit 2
fi

if [[ ! -d "$DIR" ]]
then
    echo "extractor_path '$DIR' not found."
fi

if [[ ! -d "$PROJECT" ]]
then
    echo "extract_from '$PROJECT' not found."
fi

# We must modify the PATH for this script to include the installed version of
# drush. Additionally we add our own bin folder for any tooling we may need.
export PATH=$COMPOSER_BIN:$SCRIPT_DIR:$PATH

# Include our utility functions.
. $SCRIPT_DIR/../lib/util.sh

# Check that the tools we need are present
require_drush
require_gettext_tooling
require_jq
require_msginit

###########################################################################
# Extract the requested project                                           #
###########################################################################
# Go into our base dir to ensure drush works.
cd "$DIR"

# Extract the actual project name
NAME="$(basename $PROJECT)"

# TODO: Replace `msguniq` with a PHP based solution so we can use a modern gettext version
extract_strings "$NAME" "$PROJECT" "$TARGET_FOLDER"

# Extract strings will update all translations that are present. However,
# in case a Dutch translation is not yet present, start it. We know we'll
# always support Dutch and having a single language available helps with
# other automated tools such as the Weblate Component Discovery Add-on.
if [[ -f "${TARGET_FOLDER}/en.pot" && ! -f "${TARGET_FOLDER}/nl.po" ]]; then
  msginit -i "${TARGET_FOLDER}/en.pot" -l "nl" --no-translator -o "${TARGET_FOLDER}/nl.po"
fi
