#!/bin/bash
################################################################################
#
#  This file is part of SplashSync Project.
#
#  Copyright (C) Splash Sync <www.splashsync.com>
#
#  This program is distributed in the hope that it will be useful,
#  but WITHOUT ANY WARRANTY; without even the implied warranty of
#  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
#
#  For the full copyright and license information, please view the LICENSE
#  file that was distributed with this source code.
#
#  @author Bernard Paquier <contact@splashsync.com>
#
################################################################################

set -e

if [ -z $MAGENTO_DB_HOST ]; then MAGENTO_DB_HOST="mysql"; fi
if [ -z $MAGENTO_DB_PORT ]; then MAGENTO_DB_PORT="3306"; fi
if [ -z $MAGENTO_DB_USER ]; then MAGENTO_DB_USER="root"; fi
if [ -z $MAGENTO_DB_PASS ]; then MAGENTO_DB_PASS="magento"; fi
if [ -z $MAGENTO_DB_NAME ]; then MAGENTO_DB_NAME="magento"; fi

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

echo "Create Database"
mysql -u${MAGENTO_DB_USER} -p${MAGENTO_DB_PASS} -h${MAGENTO_DB_HOST} -P${MAGENTO_DB_PORT} -e "CREATE DATABASE IF NOT EXISTS \`${MAGENTO_DB_NAME}\`;"

echo "Install Magento"
./n98-magerun.phar install \
  --dbHost="${MAGENTO_DB_HOST}" --dbUser="${MAGENTO_DB_USER}" --dbPass="${MAGENTO_DB_PASS}" --dbName="${MAGENTO_DB_NAME}" --dbPort="${MAGENTO_DB_PORT}" \
  --installSampleData=yes --useDefaultConfigParams=yes \
  --magentoVersionByName="${MAGENTO_VERSION}" \
  --installationFolder="/var/www/html" \
  --baseUrl="http://latest.magento.local/" || { echo "Installing Magento failed"; exit 1; }


