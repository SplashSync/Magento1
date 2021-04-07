<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2021 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

// phpcs:disable PSR1.Files.SideEffects
// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
// phpcs:disable Squiz.Classes.ValidClassName

require_once dirname(__DIR__)."/app/Mage.php";
require_once dirname(__DIR__)."/app/code/local/SplashSync/Splash/vendor/autoload.php";

define("COMPILER_INCLUDE_PATH", dirname(__DIR__));

//====================================================================//
// Init Splash for Local Includes
Splash\Client\Splash::core();

//====================================================================//
// FIX - Store Config Class doesn't Exists

/**
 * Class Mage_Core_Store_Config
 *
 * @SuppressWarnings(PHPMD.CamelCaseClassName)
 */
class Mage_Core_Store_Config extends Mage_Core_Model_Config
{
}
