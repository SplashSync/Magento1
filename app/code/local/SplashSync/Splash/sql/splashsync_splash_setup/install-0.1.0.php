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

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
// phpcs:disable PSR1.Files.SideEffects

/** @phpstan-ignore-next-line */
$installer = $this;

$installer->startSetup();

//====================================================================//
// Setup Customer Additional Attributes
//====================================================================//
SplashInstaller::addSplashId("customer");
SplashInstaller::addSplashId("customer_address");
SplashInstaller::addSplashOrigin("customer");
SplashInstaller::addSplashOrigin("catalog_product");

//====================================================================//
// Setup Product Additional Attributes
//====================================================================//
SplashInstaller::addSplashId("catalog_product");

//====================================================================//
// Setup Orders Additional Attributes
//====================================================================//
SplashInstaller::addSplashId("order");

$installer->endSetup();

class SplashInstaller
{
    /**
     * @param string $entityType
     */
    public static function addSplashId($entityType): void
    {
        //====================================================================//
        // Init
        //====================================================================//
        $setup = new Mage_Eav_Model_Entity_Setup('core_setup');

        //====================================================================//
        // Add SplashId Attributes
        //====================================================================//
        $setup->addAttribute($entityType, "splash_id", array(
            "type" => "varchar",
            "backend" => "",
            "label" => "Splash Id",
            "input" => "text",
            "source" => "",
            "visible" => true,
            "required" => false,
            "default" => "",
            "frontend" => false,
            "unique" => true,
            "note" => "This Id is automatically set by Splash."
        ));
    }

    /**
     * @param string $entityType
     */
    public static function addSplashOrigin($entityType): void
    {
        //====================================================================//
        // Init
        //====================================================================//
        $setup = new Mage_Eav_Model_Entity_Setup('core_setup');

        //====================================================================//
        // Add SplashId Attributes
        //====================================================================//
        $setup->addAttribute($entityType, "splash_origin", array(
            "type" => "varchar",
            "backend" => "",
            "label" => "Splash Origin",
            "input" => "text",
            "source" => "",
            "visible" => true,
            "required" => false,
            "default" => "",
            "frontend" => false,
            "unique" => true,
            "note" => "This Field is automatically set by Splash."
        ));
    }
}
