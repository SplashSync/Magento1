cd /tmp/magetest

echo "Insert Dummy data in Store"
./n98-magerun.phar dev:module:disable SplashSync_Splash
./n98-magerun.phar customer:create:dummy 5 en_US

echo "Enable Splash Module"
./n98-magerun.phar dev:module:enable SplashSync_Splash

echo "Configure Languages Options"

./n98-magerun.phar config:set splashsync_splash_options/langs/multilang           0 
./n98-magerun.phar config:set splashsync_splash_options/langs/default_lang        "en_US" 
./n98-magerun.phar config:set splashsync_splash_options/langs/store_lang          "en_US"  --scope-id=1  
./n98-magerun.phar config:set splashsync_splash_options/langs/store_lang          "fr_FR"  --scope-id=2  
./n98-magerun.phar config:set splashsync_splash_options/langs/store_lang          "de_DE"  --scope-id=3  

echo "Configure Store Main Options"

./n98-magerun.phar config:set general/store_information/name                      "Magento 1" 
./n98-magerun.phar config:set general/store_information/address                   "Store Address"
./n98-magerun.phar config:set general/store_information/merchant_country          "France"
./n98-magerun.phar config:set general/store_information/phone                     "0123456789"

./n98-magerun.phar config:get general/store_information/* 

echo "Configure Splash Module Options"

./n98-magerun.phar config:set splashsync_splash_options/advanced/expert             0 

./n98-magerun.phar config:set splashsync_splash_options/core/id                     DoNotUseThisId 
./n98-magerun.phar config:set splashsync_splash_options/core/key                    DoNotUseThisKey 
    
./n98-magerun.phar config:set splashsync_splash_options/user/login                  admin 
./n98-magerun.phar config:set splashsync_splash_options/user/pwd                    password123 

./n98-magerun.phar config:set splashsync_splash_options/products/attribute_set      4 
./n98-magerun.phar config:set splashsync_splash_options/products/default_stock      1 
./n98-magerun.phar config:set splashsync_splash_options/thirdparty/store            1 

./n98-magerun.phar config:get splashsync_splash_options/* 

echo "Clean Magento Cache"

./n98-magerun.phar cache:clean
