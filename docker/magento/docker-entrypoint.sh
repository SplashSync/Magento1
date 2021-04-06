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

# Configure Apache
bash /var/www/html/ci/scripts/config_apache.sh

# Install N89 Mage Run
bash /var/www/html/ci/scripts/install_magerun.sh


if [ ! -f installed.txt ]; then
  # Install Magento
  bash /var/www/html/ci/scripts/install_magento.sh

  # Configure Magento
  bash /var/www/html/ci/scripts/config_magento.sh
fi

echo ${MAGENTO_VERSION} > installed.txt

exec apache2-foreground
