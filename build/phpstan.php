<?php
/**
 * Bootstrap Dolibarr for Pḧpstan
 */

require_once dirname(__DIR__) . "/app/code/local/SplashSync/Splash/vendor/autoload.php";
//require_once dirname(__DIR__) . "/lib/Varien/Autoload.php";
require_once dirname(__DIR__) . "/app/Mage.php";

define("COMPILER_INCLUDE_PATH", dirname(__DIR__));

//====================================================================//
// Init Splash for Local Includes 
Splash\Client\Splash::core();

//// Initialize Magento ...
//Mage::app();
//====================================================================//
// Include Splash Constants Definitions
//require_once(dirname(__DIR__) . "/app/code/local/SplashSync/Splash/vendor/splash/phpcore/inc/Splash.Inc.php");
//require_once(dirname(__DIR__) . "/app/code/local/SplashSync/Splash/vendor/splash/phpcore/inc/defines.inc.php");
//define("SPLASH_SERVER_MODE", 0);


