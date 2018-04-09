#!/bin/bash
set -e
set -x
 
# check if this is a travis environment
if [ ! -z $TRAVIS_BUILD_DIR ] ; then
  WORKSPACE=$TRAVIS_BUILD_DIR
fi

if [ -z $WORKSPACE ] ; then
  echo "No workspace configured, please set your WORKSPACE environment"
  exit
fi
 
if [ -z $MAGENTO_DB_HOST ]; then MAGENTO_DB_HOST="localhost"; fi
if [ -z $MAGENTO_DB_PORT ]; then MAGENTO_DB_PORT="3306"; fi
if [ -z $MAGENTO_DB_USER ]; then MAGENTO_DB_USER="root"; fi
if [ -z $MAGENTO_DB_PASS ]; then MAGENTO_DB_PASS=""; fi
if [ -z $MAGENTO_DB_NAME ]; then MAGENTO_DB_NAME="mageteststand"; fi



BUILDENV="/tmp/magetest"
mkdir /tmp/magetest 

echo "Using build directory ${BUILDENV}"
cd ${BUILDENV}

echo "Download MageRun ToolKit"
wget https://raw.githubusercontent.com/netz98/n98-magerun/master/n98-magerun.phar
chmod +x ./n98-magerun.phar
./n98-magerun.phar --version

echo
echo "---------------------"
echo "- Magento Install   -"
echo "---------------------"
echo
echo "Installing ${MAGENTO_VERSION} in ${BUILDENV}/htdocs"
echo "using Database Credentials:"
echo "    Host: ${MAGENTO_DB_HOST}"
echo "    Port: ${MAGENTO_DB_PORT}"
echo "    User: ${MAGENTO_DB_USER}"
echo "    Pass: [hidden]"
echo "    Main DB: ${MAGENTO_DB_NAME}"
echo

cp -rf "${WORKSPACE}" "${BUILDENV}/htdocs/"

echo "Create Database"
mysql -u${MAGENTO_DB_USER} ${MYSQLPASS} -h${MAGENTO_DB_HOST} -P${MAGENTO_DB_PORT} -e "DROP DATABASE IF EXISTS \`${MAGENTO_DB_NAME}\`; CREATE DATABASE \`${MAGENTO_DB_NAME}\`;"

./n98-magerun.phar install \
  --dbHost="${MAGENTO_DB_HOST}" --dbUser="${MAGENTO_DB_USER}" --dbPass="${MAGENTO_DB_PASS}" --dbName="${MAGENTO_DB_NAME}" --dbPort="${MAGENTO_DB_PORT}" \
  --installSampleData=yes \
  --useDefaultConfigParams=yes \
  --magentoVersionByName="${MAGENTO_VERSION}" \
  --installationFolder="${BUILDENV}/htdocs" \
  --baseUrl="http://magento.local/" || { echo "Installing Magento failed"; exit 1; }

cp -rf "${WORKSPACE}/build/phpunit.xml.dist" "${BUILDENV}/htdocs/phpunit.xml.dist"

cd ${BUILDENV}
