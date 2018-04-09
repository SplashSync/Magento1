cd /tmp/magetest

echo "Insert Dummy data in Store"
tools/n98-magerun.phar dev:module:disable SplashSync_Splash
tools/n98-magerun.phar customer:create:dummy 5 en_US

echo "Enable Splash Module"
tools/n98-magerun.phar dev:module:enable SplashSync_Splash

echo "Configure Store Main Options"

tools/n98-magerun.phar --root-dir=htdocs config:set general/store_information/name                      "Magento 1" 
tools/n98-magerun.phar --root-dir=htdocs config:set general/store_information/address                   "Store Address"
tools/n98-magerun.phar --root-dir=htdocs config:set general/store_information/merchant_country          "France"
# tools/n98-magerun.phar --root-dir=htdocs config:set web/secure/base_url                               "www.splashsync.com"
# tools/n98-magerun.phar --root-dir=htdocs config:set trans_email/ident_general/email                   "contact@store"
tools/n98-magerun.phar --root-dir=htdocs config:set general/store_information/phone                     "0123456789"

echo "Configure Splash Module Options"

tools/n98-magerun.phar --root-dir=htdocs config:set splashsync_splash_options/advanced/expert             0 

tools/n98-magerun.phar --root-dir=htdocs config:set splashsync_splash_options/core/id                     DoNotUseThisId 
tools/n98-magerun.phar --root-dir=htdocs config:set splashsync_splash_options/core/key                    DoNotUseThisKey 
    
tools/n98-magerun.phar --root-dir=htdocs config:set splashsync_splash_options/user/login                  admin 
tools/n98-magerun.phar --root-dir=htdocs config:set splashsync_splash_options/user/pwd                    password123 

tools/n98-magerun.phar --root-dir=htdocs config:set splashsync_splash_options/products/attribute_set      1 
tools/n98-magerun.phar --root-dir=htdocs config:set splashsync_splash_options/products/default_stock      1 
tools/n98-magerun.phar --root-dir=htdocs config:set splashsync_splash_options/thirdparty/store            1 

tools/n98-magerun.phar --root-dir=htdocs config:get splashsync_splash_options/* 
