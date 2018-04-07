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
 
BUILDENV="/tmp/magetest"
mkdir /tmp/magetest 
 
echo "Using build directory ${BUILDENV}"

git clone https://github.com/SplashSync/MageTestStand.git "${BUILDENV}" -b travis

# cp -rf "${WORKSPACE}" "${BUILDENV}/.modman/"
cp -rf "${WORKSPACE}" "${BUILDENV}/htdocs/"
cp -rf "${WORKSPACE}/build/composer.json" "${BUILDENV}/composer.json"

${BUILDENV}/install.sh
if [ -d "${WORKSPACE}/vendor" ] ; then
  cp -rf ${WORKSPACE}/vendor/* "${BUILDENV}/vendor/"
fi

cp -rf "${WORKSPACE}/build/phpunit.xml.dist" "${BUILDENV}/htdocs/phpunit.xml.dist"

cd ${BUILDENV}
