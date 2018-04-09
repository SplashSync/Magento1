cd /tmp/magetest

echo "Insert Dummy data in Store"
./n98-magerun.phar dev:module:disable SplashSync_Splash
./n98-magerun.phar customer:create:dummy 5 en_US

echo "Enable Splash Module"
./n98-magerun.phar dev:module:enable SplashSync_Splash

echo "Configure Store Main Options"

./n98-magerun.phar --root-dir=htdocs config:set general/store_information/name                      "Magento 1" 
./n98-magerun.phar --root-dir=htdocs config:set general/store_information/address                   "Store Address"
./n98-magerun.phar --root-dir=htdocs config:set general/store_information/merchant_country          "France"
# ./n98-magerun.phar --root-dir=htdocs config:set web/secure/base_url                               "www.splashsync.com"
# ./n98-magerun.phar --root-dir=htdocs config:set trans_email/ident_general/email                   "contact@store"
./n98-magerun.phar --root-dir=htdocs config:set general/store_information/phone                     "0123456789"

./n98-magerun.phar --root-dir=htdocs config:get general/store_information/* 

echo "Configure Splash Module Options"

./n98-magerun.phar --root-dir=htdocs config:set splashsync_splash_options/advanced/expert             0 

./n98-magerun.phar --root-dir=htdocs config:set splashsync_splash_options/core/id                     DoNotUseThisId 
./n98-magerun.phar --root-dir=htdocs config:set splashsync_splash_options/core/key                    DoNotUseThisKey 
    
./n98-magerun.phar --root-dir=htdocs config:set splashsync_splash_options/user/login                  admin 
./n98-magerun.phar --root-dir=htdocs config:set splashsync_splash_options/user/pwd                    password123 

./n98-magerun.phar --root-dir=htdocs config:set splashsync_splash_options/products/attribute_set      4 
./n98-magerun.phar --root-dir=htdocs config:set splashsync_splash_options/products/default_stock      1 
./n98-magerun.phar --root-dir=htdocs config:set splashsync_splash_options/thirdparty/store            1 

./n98-magerun.phar --root-dir=htdocs config:get splashsync_splash_options/* 
