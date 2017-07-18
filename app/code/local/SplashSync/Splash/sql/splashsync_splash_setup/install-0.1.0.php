<?php

/*
 * This file is part of SplashSync Project.
 *
 * Copyright (C) Splash Sync <www.splashsync.com>
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * @abstract    Splash PHP Module For Magento 1
 * @author      B. Paquier <contact@splashsync.com>
 */

$installer = $this;

$installer->startSetup();

//====================================================================//
// Setup Customer Additionnal Attributes
//====================================================================//
AddSplashId("customer");
AddSplashId("customer_address");
AddSplashOrigin("customer");

//====================================================================//
// Setup Product Additionnal Attributes
//====================================================================//
AddSplashId("catalog_product");

//====================================================================//
// Setup Orders Additionnal Attributes
//====================================================================//
AddSplashId("order");
        
$installer->endSetup();

function AddSplashId( $EntityType ) {

    //====================================================================//
    // Init
    //====================================================================//
    $setup              = new Mage_Eav_Model_Entity_Setup('core_setup');
    
    //====================================================================//
    // Add SplashId Attributes
    //====================================================================//
    $setup->addAttribute($EntityType, "splash_id",  array(
        "type"          => "varchar",
        "backend"       => "",
        "label"         => "Splash Id",
        "input"         => "text",
        "source"        => "",
        "visible"       => true,
        "required"      => false,
        "default"       => "",
        "frontend"      => false,
        "unique"        => true,
        "note"          => "This Id is automaticaly set by Splash."
            ));
    
}

function AddSplashOrigin( $EntityType ) {

    //====================================================================//
    // Init
    //====================================================================//
    $setup              = new Mage_Eav_Model_Entity_Setup('core_setup');
    
    //====================================================================//
    // Add SplashId Attributes
    //====================================================================//
    $setup->addAttribute($EntityType, "splash_origin",  array(
        "type"          => "varchar",
        "backend"       => "",
        "label"         => "Splash Origin",
        "input"         => "text",
        "source"        => "",
        "visible"       => true,
        "required"      => false,
        "default"       => "",
        "frontend"      => false,
        "unique"        => true,
        "note"          => "This Field is automaticaly set by Splash."
            ));
    
}