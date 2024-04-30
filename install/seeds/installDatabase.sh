#!/bin/bash -
#===============================================================================
#
#          FILE: installDatabase.sh
#
#         USAGE: ./installDatabase.sh
#
#   DESCRIPTION: This is used to install the database for Vigilare
#
#  REQUIREMENTS: mysql, grep, awk, sed, etc...
#          BUGS: Probably
#        AUTHOR: Christopher Hubbard (CSH), chubbard@iwillfearnoevil.com
#  ORGANIZATION: Home
#       CREATED: 04/29/2024 10:59:17 AM
#      REVISION: Amanda
#===============================================================================

#set -o nounset                        # Treat unset variables as an error
#set -o pipefail                       # Any non-zero exits in pipes fail
#set -e                                # Any non-zero exit is a failure
canonicalpath=$(readlink -f $0)                 # Breaks Mac due to readlink differences
samedirname=$(dirname ${canonicalpath})         # Breaks Mac
#canonicaldirname=$(dirname ${canonicalpath}/..) # Breaks Mac

usage() {
cat << EOF
Usage: $0 options

This script will leverage mysql cli and install the database from a given set of SQL files.
By default it will NOT reinstall a successful file.  If you need to redo a file for some
reason, you will have to either force the particular file, or delete the number of the file
from the database directly.

The force switch does work globally.  Use caution, it will destroy the database and recreate
it if the swich is used without a filename.

Please note, this installer currently only supports MySQL/MariaDb (v1)

Options:
-h  show this help screen
-x  enable debug mode
-u  mysql user
-p  mysql password
-P  mysql port ( if not 3306 )
-H  mysql hostname or IP address
-d  mysql database name
-f  specfic file to be inserted
-F  Force switch to ignore if an attempt was done in the past

Examples:
$0 -u blah -p fakePass01 -P 3306 -H 192.168.10.15 -d vigilare
$0 -u blah -p fakePass01 -P 3306 -H 192.168.10.15 -d vigilare -F -f ./003_randomSqlFile.sql

EOF
}
resetTerminal() {
  echo -e "\033[0m"  # dont screw up user terminals
}

logger() {
  local SEV=$1

  if [[ -z $2 ]]; then
    local STR="No details given"
  else
    local STR=$2
  fi
  if [[ -z $3 ]]; then
    local OUT=''
  else
    local OUT="\n$3"
  fi

  # Honor exit codes HERE only
  set -e
  # Make sure our output is uniform.  Why look like a sloppy coder?
  SEV=$(echo "${SEV}" | tr [:lower:] [:upper:])
  case ${SEV} in
    DEBUG*)
     if [[ ${VERBOSE} = 'true' ]]; then
       echo -e "\033[0;36mStatus ${SEV} - ${STR}\033[1;36m${OUT}"
     fi
     ;;
    INFO*) echo -e "\033[1;34mStatus ${SEV} - ${STR} ${OUT}"  ;;
    CAUT*) echo -e "\033[1;35mStatus ${SEV} - ${STR} ${OUT}"  ;;
    ERR*) echo -e "\033[1;31mStatus ${SEV} - ${STR} ${OUT}" ; TMP_EXIT=2 ;;
    FATAL*) echo -e "\033[0;31mStatus ${SEV} - ${STR} ${OUT}"
            resetTerminal
            exit 2 ;;
    CRITICAL*) echo -e "\033[0;31mStatus ${SEV} - ${STR} ${OUT}" ; TMP_EXIT=2 ;;
    WARN*) echo -e "\033[0;33mStatus ${SEV} - ${STR} ${OUT}" ; TMP_EXIT=1 ;;
       *) echo -e "\033[0;32mStatus ${SEV} - ${STR} ${OUT}"  ;;
  esac

  if [[ ${TMP_EXIT} -gt ${EXIT} ]]; then
    EXIT=${TMP_EXIT}
  fi
  # Unset the death by exit codes now
  set +e
}


confirmDir() {
  cd $samedirname
  logger "DEBUG" "Change directory into the seeds directory if not already there"
}

checkBinary() {
  needed="mysql awk grep sed tr"
  for i in ${needed} ; do
    type $i >/dev/null 2>&1
    if [[ $? -eq 1 ]]; then
      logger "FATAL" "Missing manditory component: $i from my path or not installed"
    else
      logger "DEBUG" "Found component ${i} in path"
    fi
  done
}

checkExist() {
  local INT=$(echo ${1} | awk -F '_' '{print $1}')
  logger "DEBUG" "Attempting to check if ${INT} already set for file ${1}"
  if [[ "${INT}" = "001" ]]; then
    logger "WARNING" "File 001 will always be run, it IS the database schema"
    EXIST=0
  else
    RESULT=$(export MYSQL_PWD="${PASS}" ; mysql -u ${USER} -h ${HOST} -P ${PORT} --skip-column-names -D ${DB} -se "select count(*) as count from versionUpdates WHERE sequence = ${INT}" 2>&1)
    if [[ $? -ne 0 ]]; then
      logger "FATAL" "Error connecting to database or running query" "${RESULT}"
    else
      logger "DEBUG" "Query for ${1} returned ${RESULT}"
      EXIST="${RESULT}"
    fi
  fi
}

insertFile() {
  local FILE=${1}
  logger "DEBUG" "Attempting to insert data from ${FILE} into database ${DB}"
  RESULT=$(export MYSQL_PWD="${PASS}" ; mysql -u ${USER} -h ${HOST} -P ${PORT} -D ${DB} < ${FILE} 2>&1)
  #RESULT=$(echo mysql -u ${USER} -p"${PASS}" -h ${HOST} -P ${PORT} -D ${DB} < ${FILE})
  if [[ $? -ne 0 ]]; then
    logger "FATAL" "Error connecting to database or inserting data" "${RESULT}"
    exit 2
  else
    logger "INFO" "File ${FILE} inserted into MySQL error free"
  fi
}

VERBOSE='false'
FORCE='false'
PORT=3306

while getopts "hxFVu:p:P:H:d:f:" OPTION; do
  case ${OPTION} in
    h) usage ; exit 0 ;;
    x) export PS4='+(${BASH_SOURCE}:${LINENO}): ${FUNCNAME[0]:+${FUNCNAME[0]}(): }'; set -x ;;
    u) USER=${OPTARG} ;;
    p) PASS=${OPTARG} ;;
    P) PORT=${OPTARG} ;;
    H) HOST=${OPTARG} ;;
    d) DB=${OPTARG}   ;;
    f) FILE=${OPTARG} ;;
    F) FORCE='true'   ;;
    V) VERBOSE='true' ;;
    *) echo "Unexpected argument given.  Try -h.  Used: $@" ; exit 2 ;;
  esac    # --- end of case ---
done

logger "INFO" "Beginning Database SQL installation"

if [[ -z ${USER} ]] || [[ -z ${PASS} ]] || [[ -z ${HOST} ]]; then
  logger "FATAL" "User, password and host are manditory parameters"
fi

if [[ -z ${DB} ]]; then
  logger "FATAL" "The database must be set in the command args"
fi

# Make sure we are in the correct dir
confirmDir

# Do we have all our files we need?
checkBinary

if [[ -z ${FILE} ]]; then
  # We are going through the entire list of SQL files within this directory
  for list in $( ls *.sql ); do
    unset EXIST
    logger "DEBUG" "Check if file already added"
    checkExist ${list}
    if [[ ${FORCE} = 'true' ]]; then
      logger "DEBUG" "Force switch set.  No validation or safety done"
      insertFile ${list}
    elif [[ ${EXIST} = 0 ]]; then
      insertFile ${list}
    else
      logger "DEBUG" "File ${list} was not installed against database.  Either already installed, or another error seen."
    fi
  done
else
  checkExist ${FILE}
  if [[ ${FORCE} = 'true' ]]; then
    logger "DEBUG" "Force switch set.  No validation or safety done"
    insertFile ${FILE}
  elif [[ ${EXIST} = 0 ]]; then
    insertFile ${FILE}
  else
    logger "CAUTION" "Single file given, but has been inserted in the past.  Will not overwrite without force switch set"
  fi
fi

logger "INFO" "Installation complete"
resetTerminal
exit ${EXIT}
