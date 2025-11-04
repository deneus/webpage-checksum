#!/usr/bin/env bash

# Asserts that drush is available and exits otherwise
function require_drush() {
	command -v drush >/dev/null 2>&1 || { echo >&2 "drush is required for translation extraction. Did you run composer install? You may have to add vendor/bin/drush to your path. \nAborting."; exit 1; }
}

# Asserts that composer is available and exits otherwise
function require_composer() {
  command -v composer >/dev/null 2>&1 || { echo >&2 "composer is required for translation extraction. \nAborting."; exit 1; }
}

# Asserts that the needed gettext tools are available
function require_gettext_tooling() {
  command -v msgmerge >/dev/null 2>&1 || { echo >&2 "msgmerge is required for translation extraction. \nAborting."; exit 1; }
  # We use a custom msguniq implementation because Drupal's output isn't
  # compatible with newer gettext versions and we require msguniq in some
  # compatible form to make things work with msgmerge.
  command -v msguniq.php >/dev/null 2>&1 || { echo >&2 "msgmerge.php is required for translation extraction. \nAborting."; exit 1; }
}

# Assert that the gettext msginit tool is available
function require_msginit() {
  command -v msginit >/dev/null 2>&1 || { echo >&2 "msginit is required. \nAborting."; exit 1; }
}

# Require NodeJS. This is used by some tools for language adding.
function require_node() {
  command -v node >/dev/null 2>&1 || { echo >&2 "An installation of NodeJS is required. \nAborting."; exit 1; }
  command -v yarn >/dev/null 2>&1 || { echo >&2 "An installation of yarn is required. \nAborting."; exit 1; }
}

# Require jq. This is used for some JSON querying and manipulation.
function require_jq() {
  command -v jq >/dev/null 2>&1 || { echo >&2 "The jq utility is required. Install it with \`brew install jq\`. \nAborting."; exit 1; }
}

# Exports the translations for a project, formats them and moves them to the target directory.
#
# Assumes that it's being run from the root of the oste repository and that the potx
# drush command outputs the translation file at html/general.pot
#
# Takes the following parameters
#
# $1 The name of the project to export
# $2 The folder of the project to export (ending in a trailing slash)
# $3 The target directory for the source strings
# $4 The target prefix for the source string file (default '')
# $5 The path to replace in the general.pot file (default $2)
#
function extract_strings() {
  DIR=`pwd`
  NAME="$1"
  PROJECT="$2/"
  TARGET_FOLDER="$3/"
  TARGET_FILE="${TARGET_FOLDER}en.pot"
  REPLACEMENT_PATH="${5:-$PROJECT}"

  # Run potx to create the extractions in our html folder
  drush potx single --api 8 --folder "$PROJECT" 2>/dev/null

  # In case of error we require help from a developer and must abort.
  if [[ $? -ne 0 ]]; then
    echo >&2 "There was an error running POTX."
    echo >&2 "During extraction for $NAME"
    exit 1;
  fi

  # In case there's no general.pot file
  # If we're invoked from an incorrect location or potx had an error we can't continue
  if [[ ! -f "${DIR}/html/general.pot" ]]; then
    echo >&2 "No strings extracted for $NAME"
    return 1;
  fi

  mkdir -p "$TARGET_FOLDER"
  mv "${DIR}/html/general.pot" "$TARGET_FILE"

  sed -i.bak -e "s|${REPLACEMENT_PATH}||g" "$TARGET_FILE"
  sed -i.bak -e "s/LANGUAGE translation of Drupal (general)/English translation of ${NAME}/g" "$TARGET_FILE"
  sed -i.bak -e "s/Copyright YEAR NAME <EMAIL@ADDRESS>/Copyright 2021 Open Social <devel@getopensocial\.com>/g" "$TARGET_FILE"
  sed -i.bak -e "s/Project-Id-Version: PROJECT VERSION/Project-Id-Version: ${NAME}/g" "$TARGET_FILE"
  sed -i.bak -e "s/Plural-Forms: nplurals=INTEGER; plural=EXPRESSION;/Plural-Forms: nplurals=2; plural=(n != 1);/g" "$TARGET_FILE"

  rm "${TARGET_FILE}.bak"

  #
  # In order for new extracted strings to be detected in Weblate we must also
  # update the *.po files with the new strings. To do this we can use some of
  # the gettext tools available to us.
  #

  # Make the actual target file itself unique.
  # We use a custom implementation of msguniq because newer gettext msguniq
  # versions reject the output that Drupal produces. However, we must make
  # Drupal's output processable by the other gettext tooling somehow.
  msguniq.php --sort-output --output-file "$TARGET_FILE" "$TARGET_FILE"

  # If msguniq returns any errors then we exit with an error code and a
  # developer should resolve the issue before we can complete the string
  # extraction.
  if [[ $? -ne 0 ]]; then
    echo >&2 "msguniq encountered an error in the translation source. This must be resolved for successful translation extraction."
    exit 1;
  fi

  # We loop through all translation files and update them with the new source
  # strings.
  TRANSLATION_FILES=`find "${TARGET_FOLDER}" -type f -name "*.po" -print`

  # It could be that no translations exist yet and find returns an empty list.
  if [[ ! -z "$TRANSLATION_FILES" ]]; then
    while read -r file; do
      # Update the found PO file in place.
      msgmerge --no-fuzzy-matching --sort-output --update --backup=off "$file" "$TARGET_FILE"
    done <<< "$TRANSLATION_FILES"
  fi
}

# Outputs the added/changed strings in a given repository.
#
# It uses a modified output of the `git diff` command to add all the additions
# to the list-changes file.
#
# Must be in the right git repository at root before calling this function.
#
# Takes the following parameters
# $1 The path to the OSTE repository this script lives in.
# $2 The name of the project for which we're listing changes
# $3 The dir for which to list the changes relative to git root.
function list_changes() {
  SCRIPT_DIR=$1
  TARGET_DIR=${@:2}

  git config diff.prettypo.textconv "${SCRIPT_DIR}/pretty-po-diff.sh"
  echo "*.pot diff=prettypo" > .gitattributes

  # Tell git that we intend to add files. This ensures that git diff works as
  # expected, even when we didn't previously have any translations.

  git add --intent-to-add --all

  CHANGES=$(
    # Get the differences for the target file (preferrably a .pot file).
    git diff $TARGET_DIR |
    # Filter out any changes in actual messages.
    grep "[\+\-]msgid" |
    # Replace the message keyword.
    sed 's/\([\+\-]\)msgid\(_plural\)\{0,1\} "\(.*\)"/\1 \3/'
  )

  if [[ ! -z "$CHANGES" ]]; then
    echo "$CHANGES"
  fi

  # Restore any .gitattributes file that may have been previously present.
  git reset -- .gitattributes >/dev/null
  rm ".gitattributes"
  git checkout -- .gitattributes 2>/dev/null || true
}

# Commits any updated translation files
#
# Takes the following parameters
# $1 A valid git path to limit the scope of changes to be commited (e.g. core/)
# $2 A message to use when committing (default "Updating translation source strings")
function commit_changes() {
  # Allow git to be skipped when testing.
  if [[ ! -z "${SKIP_GIT}" ]]; then
    return
  fi

  SCOPE="$1"
  MESSAGE="${2:-Updating translation source strings}"

  # Tell git that we intend to add files. This ensures that git diff works as
  # expected, even when we didn't previously have any translations.
  git add --intent-to-add "$SCOPE"

  CHANGED_FILES=`git diff --name-only -- $SCOPE`

  # Iterate over all the changed files if there were any
  if [[ ! -z "${CHANGED_FILES}" ]]; then
    while read -r file; do
      # Check if there were any actual changes in source strings.
      CHANGED_LINES=`git diff --no-textconv ${file} | grep -E "[\+\-]msgid" | wc -l`

      # If only the timestamp changed then we find the old file and continue.
      if [[ $CHANGED_LINES -eq 0 ]]; then
        git checkout -- "$file"
        continue
      fi

      # Stage the file for commit
      git add "$file"
    done <<< "$CHANGED_FILES"
  fi

  # Only commit if there are any staged files.
  if [[ $(git diff --staged --name-only | wc -l) -gt 0 ]]; then
     git commit -m "$MESSAGE"
  fi
}

# Ensures that a git directory is in the same state as its remote mirror.
#
# Performs a hard reset on the local branch after fetching the remote repository
# to ensure that the branches are exactly the same.
#
# Takes the following parameters
# $1 The path to a git directory
# $2 The name of the branch to checkout
function update_git_dir() {
  GIT_DIR=$1
  BRANCH=$2

  cd "$GIT_DIR"
  git fetch
  git checkout "$BRANCH"
  git reset --hard "origin/${BRANCH}"
}
