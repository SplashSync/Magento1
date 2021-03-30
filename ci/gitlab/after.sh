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

echo
echo "---------------------"
echo "- System Info       -"
echo "---------------------"
echo

cd /var/ww/html
./n98-magerun.phar --version
./n98-magerun.phar sys:info
./n98-magerun.phar sys:store:list
./n98-magerun.phar sys:modules:list
./n98-magerun.phar eav:attribute:list
./n98-magerun.phar config:get general/store_information/*
./n98-magerun.phar config:get general/locale/*