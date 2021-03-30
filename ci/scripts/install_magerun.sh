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
echo "- Magerun Install   -"
echo "---------------------"
echo

if [ ! -f ./n98-magerun.phar ]; then
  wget https://raw.githubusercontent.com/netz98/n98-magerun/master/n98-magerun.phar
  chmod +x ./n98-magerun.phar
fi

./n98-magerun.phar --version
