#!/bin/bash
#===============================================================================
#
#          FILE: install.sh
#
#   DESCRIPTION: Attempt to install Vigilare API
#
#  REQUIREMENTS: grep sed awk route
#        AUTHOR: Christopher Hubbard (CSH), chubbard@iwillfearnoevil.com
#  ORGANIZATION: Minimal
#       CREATED: 02/06/2024 11:09
#      REVISION: Amanda
#       VERSION: 0.0.1
#===============================================================================

# In theory this can be deployed on Mac, so disable parts that make Mac choke
#canonicalpath=`readlink -f $0`
#canonicaldirname=`dirname ${canonicalpath}`/..
#samedirname=`dirname ${canonicalpath}`

#==============================================================================
# Define a base useage case for a -h option
#==============================================================================
usage(){
cat<<EOF
Usage: $0 options

This script will attempt to install the Vigilare NMS APIs onto the given host.
It will ask several questions in order to complete the installation and configuration.
In the event of mistakes, the app directory will be where most of the settings
will reside.

This script is expected to run as root user (sudo) to keep issues to a minimum.

Before you start, you will need to know:

* FQDN of the API server
* IP address of MariaDB/MySql instance
* User and Pass for MySQL
* Local location of your SSL certificates

Email: Test your email settings to answer here OR
       manually edit your app/config.php if you test them
       after installation.  Either way, TEST them with the
       email test script.

Options:
-h  show this help system
-x  enable debugger mode
-b  bypass apt install of packages. (implies you have done it manually)

Example:
./$0
EOF
}

logger() {
# Make a single output and logging utility
# This can be used in ALL scripts.  Only catch
# is that you MUST define the destination file

# This is the ONLY place that should exit out of our
# script with an error as the -e is not set in shopt values
SEV=$1
STR=$2
OUT=$3

# Honor exit codes HERE only
set -e
if [[ -z $2 ]]; then
  STR="No details given"
fi
if [[ -z $3 ]]; then
  OUT=''
else
  OUT="\n${3}"
fi

# Make sure our output is uniform.  Why look like a sloppy coder?
SEV=$(echo "${SEV}" | tr [:lower:] [:upper:])
case ${SEV} in
  *DEBUG*) echo -e "\033[0;32mStatus ${SEV} - ${STR} ${OUT}\033[0m" ;;
  *INFO*) echo -e "\033[0;32mStatus ${SEV} - ${STR} ${OUT}\033[0m"  ;;
  *ERR*) echo -e "\033[0;35mStatus ${SEV} - ${STR} ${OUT}\033[0m" ;  exit 1 ;;
  FATAL*) echo -e "\033[0;31mStatus ${SEV} - ${STR} ${OUT}\033[0m";  exit 2 ;;
  *WARN*) echo -e "\033[0;33mStatus ${SEV} - ${STR} ${OUT}\033[0m"  ;;
  *) echo -e "\033[0;36mStatus ${SEV} - ${STR} ${OUT}\033[0m" ;;
esac

# Unset the death by exit codes now
set +e
}

verify_deps() {
needed="awk grep sed uuidgen"
for i in `echo $needed`; do
  type $i >/dev/null 2>&1
  if [[ $? -eq 1 ]]; then
    logger "ERROR" "missing manditory component from path: $i"
  fi
done
logger "INFO" "Confirmed all OS level binary files necessary have been found to begin work"
}

confirmDeb() {
  DEB=$(which apt)
  if [[ $? -ne 0 ]]; then
    logger "WARNING" "This system was butil and tested on Debian based hosts."
    logger "WARNING" "Non-fatal, but manual installation of packages will be required for PHP"
    DEBIAN='false'
  else
    logger "INFO" "Found executable apt.  Confirmed Debian based system"
    DEBIAN='true'
  fi
}

got_root() {
if [ "$(id -u)" = "0" ]; then
  logger "INFO" "Confirmed running as root user";
else
  logger "FATAL" "This script is intended to be run as root or sudoed"
fi
}

installPackages() {
  UPDATE=$(apt update 2>&1)
  if [[ $? -ne 0 ]]; then
    logger "FATAL" "Unable to run apt update successfully.  Please fix your repos.d or internet connection" "${UPDATE}"
  else
    logger "INFO" "Apt update was successful"
  fi

  INST0=$(apt install -y snmp snmpd snmptrapd nagios-nrpe-server rrdtool python3-pysmi snmp-mibs-downloader apache2 mysql-client 2>&1)
  if [[ $? -ne 0 ]]; then
    logger "FATAL" "Unable to install early base packages.  Please install manually" "${INST0}"
  else
    logger "INFO" "Installed all manditory packages successfully"
  fi

  INST=$(apt install -y libapache2-mod-php8.2 php8.2 php8.2-bcmath php8.2-cli php8.2-common php8.2-curl php8.2-fpm php8.2-gd php8.2-gmp php8.2-intl php8.2-mbstring php8.2-mysql php8.2-opcache php8.2-phpdbg php8.2-readline php8.2-rrd php8.2-snmp php8.2-xml php8.2-zip composer 2>&1)
  if [[ $? -ne 0 ]]; then
    logger "FATAL" "Unable to install packages.  Please install manually" "${INST}"
  else
    logger "INFO" "Installed all manditory packages successfully"
  fi
}

installComposer() {
  pushd ../ 2>&1 >/dev/null
  INST_COMP=$(composer install)
  if [[ $? -ne 0 ]]; then
    logger "FATAL" "Composer failed to install the necessary sources.  Please run again, or run composer install from root of repository" "${INST_COMP}"
  else
    logger "INFO" "Composer install of vendor packages successsful"
  fi
  popd 2>&1 >/dev/null
}

runTemplate() {
  local FILE=${1}
  logger "INFO" "Generating file ${FILE}.php in apps/"
  (echo "cat << EOF >> ../app/${FILE}.php"; cat templates/${FILE}.tpl ; echo "EOF"; ) > ./temp
}

getInfoDomain() {
  echo "Need the domain we are working with"
  read -p "Domain:" ${DOMAIN}
}

getInfoApi() {
  echo "Need the API server hostname only"
  read -p "Hostname: " ${API_HOSTNAME}
  echo "Need the Port the API server will be listening on default: 8002"
  read -p "Port: " ${API_PORT}
  read -p "SSL Cert path: " ${SSL_PATH}
}

getInfoGui() {
  echo "Need either the hostname or proxy name for the GUI"
  read -p "Gui hostname: " ${GUI_HOSTNAME}
  echo "Need GUI port that will be used.  Usually 443"
  read -p "Gui port: " ${GUI_PORT}
  GUI_URL=${GUI_HOSTNAME}.${DOMAIN}
}
getInfoDb() {
  echo "Need MySQL databae IP address"
  read -p "IP Address: " ${DB_IP}
  echo "Need MySQL port to connect to.  Usually 3306"
  read -p "Port used: " ${DB_PORT}
  echo "Need MySQL user"
  read -p "User: " ${DB_USER}
  echo "Need MySQL password"
  read -p "Password: " ${DB_PASS}
}

generateSecret() {
  local GEN=${1}
  case ${GEN} in
    PEPPER)     PEPPER=$(strings /dev/urandom | grep -o '[[:alnum:]]' | head -n 10 | tr -d '\n') ;;
    API_KEY)    API_KEY=$(uuidgen) ;;
    JWT_SECRET) JWT_SECRET=$(strings /dev/urandom | grep -o '[[:alnum:]]' | head -n 15 | tr -d '\n') ;;
    *)          logger "FATAL" "Unsupported password generation requested" "Tried ${1}" ;; # Adding for future passwords :)
  esac
  logger "INFO" "Secret generated for ${GEN}"
}

configureApache() {
  logger "INFO" "Generating file 002-VigilareNms.cfg in /etc/apache2/sites-available/"
  (echo "cat << EOF >> /etc/apache2/sites-available/002-VigilareApi.cfg"; cat templates/002-VigilareApi.tpl ; echo "EOF"; ) > ./temp
  rm -f ./temp
  logger "WARNING" "You will likely have to adjust the 002-VigilareApi.cfg file to reflect which SSL certificates to use"
  A2ENMOD=$( a2enmod php_module mpm_prefork )
  A2ENSITE$( a2ensite 002-VigilareApi )
  APACHETEST=$( apachectl configtest )
  if [[ $? -ne 0 ]]; then
    logger "WARNING" "Errors were found with apache configtest.  Please manually repair until no more issues found via apachectl configtest"
    logger "WARNING" "Will not restart apache due to unresolved issues" "${APACHETEST}"
    logger "WARNING" "Do not re-run installer after fixing file.  It will clobber your fixes.  Restart services manually"
  else
    logger "INFO" "apachectl configtest not showing issues"
    RESTART=$(service apache2 restart 2>&1)
    logger "INFO" "Restarted Apache2" "${RESTART}"
  fi
}

installDatabase() {
  logger "INFO" "Creating application user for database vigilare"
  DB_USER_CREATE=$(mysql -h ${DB_IP} -P ${DB_PORT} -u ${DB_INSTALL_USER} -p$"{DB_INSTALL_PASS}" mysql -e "CREATE USER ${DB_USER}@% IDENTIFIED BY \"${DB_PASS}\"")
  if {{ $? -ne 0 ]]; then
    logger "FATAL" "Unable to create user ${DB_USER}" "${DB_USER_CREATE}"
  else
    logger "INFO" "Databae user ${DB_USER} created"
  fi
  DB_GRANT=$(mysql -h ${DB_IP} -P ${DB_PORT} -u ${DB_INSTALL_USER} -p$"{DB_INSTALL_PASS}" mysql -e "GRANT ALL PRIVILEGES on vigilare.* TO \"${DB_USER}\"@\"%\"")
  if {{ $? -ne 0 ]]; then
    logger "FATAL" "Unable to grant privileges for ${DB_USER}" "${DB_GRANT}"
  else 
    logger "INFO" "Databae grant for user ${DB_USER} created"
  fi
  for LIST in $( ls seeds/*.sql); do
    logger "INFO" "Mysql inserting SQL from file ${LIST}"
    INSERT=$(mysql -h ${DB_IP} -P ${DB_PORT} -u ${DB_USER} -p"${DB_PASS}" vigilare < ${LIST})
    if [[ $? -ne 0 ]]; then
      logger "FATAL" "Unable to configure database.  Failed on file ${LIST}.  Please correct and insert manually" "${INSERT}"
    else
      logger "INFO" "Insert of file ${LIST} successful'
    fi
  done
}

# Set DEFAULTS here
DEBIAN='unknown'
API_PORT=''
APT='false'
DOMAIN=''
API_HOSTNAME=''
GUI_HOSTNAME=''
GUI_PORT=''
DB_PORT=''
DB_USER=''
DB_PASS=''
DB_IP=''
SSL_PATH=''
INCLUDE_SET='false'

while getopts "hxbf:" OPTION; do
  case ${OPTION} in
    h) usage; exit 0 ;;
    x) export PS4='+(${BASH_SOURCE}:${LINENO}): ${FUNCNAME[0]:+${FUNCNAME[0]}(): }'; set -x     ;;
    b) APT='true';;
    f) FILE="${OPTARG}" ; INCLUDE_SET='true' ;;
  esac
done

# check user
got_root

# check OS utilities
verify_deps

# check if Debian
confirmDeb

if [[ $DEBIAN == 'true' ]] && [[ ${APT} == 'false' ]] ; then
  installPackages
else
  logger "WARNING" "Either this is not a Debian based OS, or the apt bypass was used."
  logger "WARNING" "This is what gets installed in Debian"
  logger "WARNING" "snmp snmpd snmptrapd nagios-nrpe-server rrdtool python3-pysmi snmp-mibs-downloader apache2 mysql-client"
  logger "WARNING" "libapache2-mod-php8.2 php8.2 php8.2-bcmath php8.2-cli php8.2-common php8.2-curl php8.2-fpm php8.2-gd php8.2-gmp php8.2-intl php8.2-mbstring php8.2-mysql php8.2-opcache php8.2-phpdbg php8.2-readline php8.2-rrd php8.2-snmp php8.2-xml php8.2-zip composer"
  if [[ ${APT} == 'true' ]]; then
    logger "INFO" "Continuing installation"
  else
    logger "INFO" "Bypass not set.  Exiting installation now"
    exit 0
  fi
fi

if [[ $(uname) == "Darwin" ]]; then
  logger "WARNING" "You appear to be attempting this on a Mac.  YMMV"
else
  logger "INFO" "Changing into script directory so all work is relative to here"
  cd "$(dirname "$0")"
  pushd ../ 2>&1 >/dev/null
    INSTALL_PATH=$(pwd)
    logger "INFO" "Setting install path to $(pwd)"
  popd 2>&1 >/dev/null
fi

# run composer install now
#installComposer
# enough boilerplate!  Get our info here
if [[ ${INCLUDE_SET} == 'false' ]]; then
  logger "INFO" "Beginning Q&A to get values needed for installation"
  generateSecret "PEPPER"
  generateSecret "API_KEY"
  generateSecret "JWT_SECRET"
  getInfoDomain
  getInfoApi
  getInfoGui
  getInfoDb
else
  if [[ -e ${FILE} ]]; then
    logger "INFO" "Loaded values from variable file"
    . ${FILE}
  else
    logger "FATAL" "Unable to find file ${FILE}"
  fi
fi

# declare -p

# Our vars are set, now create our config.php, settings.php and Database.php files for app/
#runTemplate "config"
#runTemplate "settings"
#runTemplate "Database"
#installDatabase
#configureApache


