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
echo "-  Magento Config   -"
echo "---------------------"
echo

echo "Insert Dummy data in Store"
./n98-magerun.phar dev:module:disable SplashSync_Splash
./n98-magerun.phar customer:create:dummy 5 en_US

echo "Enable Splash Module"
./n98-magerun.phar dev:module:enable SplashSync_Splash

echo "Configure Languages Options"

./n98-magerun.phar config:set splashsync_splash_options/langs/multilang           0 
./n98-magerun.phar config:set splashsync_splash_options/langs/default_lang        "en_US" 
./n98-magerun.phar config:set splashsync_splash_options/langs/store_lang          "en_US"  --scope='stores' --scope-id=1  
./n98-magerun.phar config:set splashsync_splash_options/langs/store_lang          "fr_FR"  --scope='stores' --scope-id=2  
./n98-magerun.phar config:set splashsync_splash_options/langs/store_lang          "de_DE"  --scope='stores' --scope-id=3  

echo "Configure Store Main Options"

./n98-magerun.phar config:set general/store_information/name                      "Magento 1" 
./n98-magerun.phar config:set general/store_information/address                   "Store Address"
./n98-magerun.phar config:set general/store_information/merchant_country          "France"
./n98-magerun.phar config:set general/store_information/phone                     "0123456789"

./n98-magerun.phar config:get general/store_information/* 

echo "Configure Splash Module Options"

./n98-magerun.phar config:set splashsync_splash_options/advanced/expert             0 
./n98-magerun.phar config:set splashsync_splash_options/advanced/website            1 

./n98-magerun.phar config:set splashsync_splash_options/core/id                     ThisIsMageLatestKey
./n98-magerun.phar config:set splashsync_splash_options/core/key                    ThisTokenIsNotSoSecretChangeIt
    
./n98-magerun.phar config:set splashsync_splash_options/user/login                  admin 
./n98-magerun.phar config:set splashsync_splash_options/user/pwd                    password123 

./n98-magerun.phar config:set splashsync_splash_options/products/attribute_set      4 
./n98-magerun.phar config:set splashsync_splash_options/products/default_stock      1 

./n98-magerun.phar config:get splashsync_splash_options/*

echo "Clean Magento Cache"

./n98-magerun.phar cache:clean

echo "Admins List"

./n98-magerun.phar admin:user:list

echo "Change Admin Pwd Store"
./n98-magerun.phar admin:user:change-password admin "password123"

