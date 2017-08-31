#!/usr/bin/env bash

# this script must be run by root or sudo
if [[ "$UID" -ne "0" ]] ; then
    echo "ERROR: This script must be run by root or sudo"
    exit
fi

DISTRO=$(lsb_release -i | sed -e "s/Distributor\ ID\:\t//g" | tr '[:upper:]' '[:lower:]')
SUBMITTY_INSTALL_DIR=/usr/local/submitty
SUBMITTY_DATA_DIR=/var/local/submitty
SUBMITTY_REPOSITORY=/usr/local/submitty/GIT_CHECKOUT_Submitty

apt-get update
apt-get install -qqy python python-dev python3 python3-dev
PY3_VERSION=$(python3 -V 2>&1 | sed -e "s/Python\ \([0-9].[0-9]\)\(.*\)/\1/")
apt-get install libpython${PY3_VERSION}

# Check to see if pip is installed on this system, and if not, install it
# from bootstrap.pypi.io so that we have the latest version (installing from
# the repo will give us something out-of-date and hard to install/manage)
if [ ! -x "$(command -v pip)" ]; then
    wget --tries=5 https://bootstrap.pypa.io/get-pip.py -O /tmp/get-pip.py
    python3 /tmp/get-pip.py
    rm -rf /tmp/get-pip.py
else
    pip3 install -U pip
fi

pip3 install -U PyYAML

python3 ${SUBMITTY_REPOSITORY}/.setup/bin/reset_system.py

sudo ${SUBMITTY_REPOSITORY}/.setup/install_system.sh --vagrant ${@}

# Disable OPCache for development purposes as we don't care about the efficiency as much
echo "opcache.enable=0" >> /etc/php/7.0/fpm/conf.d/10-opcache.ini

DISTRO=$(lsb_release -i | sed -e "s/Distributor\ ID\:\t//g")

rm -rf ${SUBMITTY_DATA_DIR}/logs/*
rm -rf ${SUBMITTY_REPOSITORY}/.vagrant/${DISTRO}/logs/submitty
mkdir -p ${SUBMITTY_REPOSITORY}/.vagrant/${DISTRO}/logs/submitty
mkdir -p ${SUBMITTY_REPOSITORY}/.vagrant/${DISTRO}/logs/submitty/autograding
ln -s ${SUBMITTY_REPOSITORY}/.vagrant/${DISTRO}/logs/submitty/autograding ${SUBMITTY_DATA_DIR}/logs/autograding
chown hwcron:course_builders ${SUBMITTY_DATA_DIR}/logs/autograding
chmod 770 ${SUBMITTY_DATA_DIR}/logs/autograding

mkdir -p ${SUBMITTY_REPOSITORY}/.vagrant/${DISTRO}/logs/submitty/access
mkdir -p ${SUBMITTY_REPOSITORY}/.vagrant/${DISTRO}/logs/submitty/site_errors
ln -s ${SUBMITTY_REPOSITORY}/.vagrant/${DISTRO}/logs/submitty/access ${SUBMITTY_DATA_DIR}/logs/access
ln -s ${SUBMITTY_REPOSITORY}/.vagrant/${DISTRO}/logs/submitty/site_errors ${SUBMITTY_DATA_DIR}/logs/site_errors
chown -R hwphp:course_builders ${SUBMITTY_DATA_DIR}/logs/access
chmod -R 770 ${SUBMITTY_DATA_DIR}/logs/access
chown -R hwphp:course_builders ${SUBMITTY_DATA_DIR}/logs/site_errors
chmod -R 770 ${SUBMITTY_DATA_DIR}/logs/site_errors

# Call helper script that makes the courses and refreshes the database
${SUBMITTY_REPOSITORY}/.setup/bin/setup_sample_courses.py --submission_url ${SUBMISSION_URL}

#################################################################
# SET CSV FIELDS (for classlist upload data)
#################
# Vagrant auto-settings are based on Rensselaer Polytechnic Institute School
# of Science 2015-2016.

# Other Universities will need to rerun /bin/setcsvfields to match their
# classlist csv data.  See wiki for details.
${SUBMITTY_INSTALL_DIR}/bin/setcsvfields.py 13 12 15 7
