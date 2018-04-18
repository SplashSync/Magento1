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
 * @abstract    Splash PHP Module For Magento 1 - Local Core Class
 * @author      B. Paquier <contact@splashsync.com>
 */

namespace Splash\Local;

use ArrayObject;

use Splash\Core\SplashCore      as Splash;

use Mage;
use Mage_Core_Model_App;

class Local
{

    public $_Action = null;
    
//====================================================================//
// *******************************************************************//
//  MANDATORY CORE MODULE LOCAL FUNCTIONS
// *******************************************************************//
//====================================================================//
    
    /**
     *      @abstract       Return Local Server Parameters as Aarray
     *
     *      THIS FUNCTION IS MANDATORY
     *
     *      This function called on each initialisation of the module
     *
     *      Result must be an array including mandatory parameters as strings
     *         ["DefaultLanguage"]   =>>  Name of Module Default Language
     *         =>>  An Osws_Local_MyObject Class with standard access functions
     *
     *      @return         array       $parameters
     */
    public static function Parameters()
    {
        $Parameters       =     array();

        //====================================================================//
        // Server Identification Parameters
        $Parameters["WsIdentifier"]         =   Mage::getStoreConfig('splashsync_splash_options/core/id');
        $Parameters["WsEncryptionKey"]      =   Mage::getStoreConfig('splashsync_splash_options/core/key');
        
        //====================================================================//
        // If Expert Mode => Allow Overide of Server Host Address
        if (Mage::getStoreConfig('splashsync_splash_options/advanced/expert')) {
            if (!empty(Mage::getStoreConfig('splashsync_splash_options/advanced/server_url'))) {
                $Parameters["WsHost"]           =   Mage::getStoreConfig('splashsync_splash_options/advanced/server_url');
            }
        }
        
        //====================================================================//
        // Overide Module Parameters with Local User Selected Lang
        $Parameters["DefaultLanguage"]      =   Mage::getStoreConfig('splashsync_splash_options/core/lang');
        
        //====================================================================//
        // Overide Module Local Name in Logs
        $Parameters["localname"]            =   Mage::getStoreConfig('general/store_information/name');
        
        return $Parameters;
    }
    
    /**
     *      @abstract       Include Local Includes Files
     *
     *      Include here any local files required by local functions.
     *      This Function is called each time the module is loaded
     *
     *      There may be differents scenarios depending if module is
     *      loaded as a library or as a NuSOAP Server.
     *
     *      This is triggered by global constant SPLASH_SERVER_MODE.
     *
     *      @return         bool
     */
    public function Includes()
    {
        //====================================================================//
        // When Library is called in both clinet & server mode
        //====================================================================//

        //====================================================================//
        // Initialize Magento ...
        if (!defined("BP")) {
            //====================================================================//
            // Initialize Magento ...
            require_once(dirname(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__))))))).'/app/Mage.php');
            Mage::app();
            //====================================================================//
            // When Library is called in server mode ONLY
            if (SPLASH_SERVER_MODE) {
                Mage::app()->setCurrentStore((string) Mage_Core_Model_App::ADMIN_STORE_ID);
            }             
        }
        
        //====================================================================//
        //  Load Local Translation File
        Splash::translator()->load("main@local");
        
        return true;
    }

    /**
     *      @abstract       Return Local Server Self Test Result
     *
     *      THIS FUNCTION IS MANDATORY
     *
     *      This function called during Server Validation Process
     *
     *      We recommand using this function to validate all functions or parameters
     *      that may be required by Objects, Widgets or any other modul specific action.
     *
     *      Use Module Logging system & translation tools to retrun test results Logs
     *
     *      @return         bool    global test result
     */
    public static function SelfTest()
    {

        //====================================================================//
        //  Load Local Translation File
        Splash::translator()->load("main@local");
        
        //====================================================================//
        //  Verify - Core Parameters
        if ( !self::SelfTestCoreParameters() ) {
            return false;
        }

        //====================================================================//
        //  Verify - Products Parameters
        if ( !self::SelfTestProductsParameters() ) {
            return false;
        }
        
        return Splash::log()->msg("Self Test Passed");
    }
    
    private static function SelfTestCoreParameters()
    {
        //====================================================================//
        //  Verify - Server Identifier Given
        if (empty(Mage::getStoreConfig('splashsync_splash_options/core/id'))) {
            return Splash::log()->err("ErrSelfTestNoWsId");
        }
                
        //====================================================================//
        //  Verify - Server Encrypt Key Given
        if (empty(Mage::getStoreConfig('splashsync_splash_options/core/key'))) {
            return Splash::log()->err("ErrSelfTestNoWsKey");
        }
        
        //====================================================================//
        //  Verify - Default Language is Given
        if (empty(Mage::getStoreConfig('splashsync_splash_options/core/lang'))) {
            return Splash::log()->err("ErrSelfTestDfLang");
        }

        //====================================================================//
        //  Verify - User Selected
        if (empty(Mage::getStoreConfig('splashsync_splash_options/user/login')) || empty(Mage::getStoreConfig('splashsync_splash_options/user/pwd'))) {
            return Splash::log()->err("ErrSelfTestNoUser");
        }

        //====================================================================//
        //  Verify - FIELDS TRANSLATIONS CONFIG
        if (!self::validateLanguageParameters()) {
            return false;
        }
        
        
        return true;
    }

    private static function SelfTestProductsParameters()
    {

        //====================================================================//
        //  Verify - PRODUCT DEFAULT ATTRIBUTE SET
        $AttributeSetId =   Mage::getStoreConfig('splashsync_splash_options/products/attribute_set');
        if (empty($AttributeSetId)) {
            return Splash::log()->err("No Default Product Attribute Set Selected");
        }
        $AttributeSet    =   Mage::getModel('eav/entity_attribute_set')->load($AttributeSetId);
        if (!$AttributeSet->getAttributeSetId()) {
            return Splash::log()->err("Wrong Product Attribute Set Identifier");
        }
        //====================================================================//
        //  Verify - PRODUCT DEFAULT STOCK
        $StockId    =   Mage::getStoreConfig('splashsync_splash_options/products/default_stock');
        if (empty($StockId)) {
            return Splash::log()->err("No Default Product Warehouse Selected");
        }
        $Stock    =   Mage::getModel('cataloginventory/stock')->load($StockId);
        if (!$Stock->getStockId()) {
            return Splash::log()->err("Wrong Product Warehouse Selected");
        }
        
        //====================================================================//
        //  Verify - Product Prices Include Tax Warning
        if (Mage::getStoreConfig('tax/calculation/price_includes_tax')) {
            Splash::log()->war("You selected to store Products Prices Including Tax. It is highly recommanded to store Product Price without Tax to work with Splash.");
        }
        //====================================================================//
        //  Verify - Shipping Prices Include Tax Warning
        if (Mage::getStoreConfig('tax/calculation/shipping_includes_tax')) {
            Splash::log()->war("You selected to store Shipping Prices Including Tax. It is highly recommanded to store Shipping Price without Tax to work with Splash.");
        }
        
        return true;
    }
    
    /**
     *  @abstract   Update Server Informations with local Data
     *
     *  @param     ArrayObject  $Informations   Informations Inputs
     *
     *  @return     ArrayObject
     */
    public function Informations($Informations)
    {
        //====================================================================//
        // Init Response Object
        $Response = $Informations;

        //====================================================================//
        // Server General Description
        $Response->shortdesc        = "Splash Module for Magento 1";
        $Response->longdesc         = "Splash SOAP Connector Module for Magento 1.";
        
        //====================================================================//
        // Company Informations
        $Response->company          = Mage::getStoreConfig('general/store_information/name');
        $Response->address          = Mage::getStoreConfig('general/store_information/address');
        $Response->zip              = "...";
        $Response->town             = "...";
        $Response->country          = Mage::getStoreConfig('general/store_information/merchant_country');
        $Response->www              = Mage::getStoreConfig('web/secure/base_url');
        $Response->email            = Mage::getStoreConfig('trans_email/ident_general/email');
        $Response->phone            = Mage::getStoreConfig('general/store_information/phone');
        
        //====================================================================//
        // Server Logo & Images
        $Response->icoraw           = Splash::file()->readFileContents(Mage::getBaseDir()  . "/favicon.ico");
        $Response->logourl          = Mage::getStoreConfig('web/secure/base_url') . "skin/frontend/default/default/images/logo_print.gif";
        $Response->logoraw          = Splash::file()->readFileContents(Mage::getBaseDir("skin") . "/frontend/default/default/images/logo_print.gif");
        
        //====================================================================//
        // Server Informations
        $Response->servertype       = "Magento " . Mage::getVersion();
        $Response->serverurl        = Mage::getStoreConfig('web/secure/base_url');
        
        //====================================================================//
        // Module Informations
        $Response->moduleversion    = $this->getExtensionVersion() . ' (Splash Php Core ' . SPLASH_VERSION . ')';
                
        return $Response;
    }
    
//====================================================================//
// *******************************************************************//
//  OPTIONNAl CORE MODULE LOCAL FUNCTIONS
// *******************************************************************//
//====================================================================//
    
    /**
     *      @abstract       Return Local Server Test Parameters as Aarray
     *
     *      THIS FUNCTION IS OPTIONNAL - USE IT ONLY IF REQUIRED
     *
     *      This function called on each initialisation of module's tests sequences.
     *      It's aim is to overide general Tests settings to be adjusted to local system.
     *
     *      Result must be an array including parameters as strings or array.
     *
     *      @see Splash\Tests\Tools\ObjectsCase::settings for objects tests settings
     *
     *      @return         array       $parameters
     */
    public static function TestParameters()
    {
        //====================================================================//
        // Load Recurent Use Parameters
        $multilang    =   Mage::getStoreConfig('splashsync_splash_options/langs/multilang');
        $default_lang =   Mage::getStoreConfig('splashsync_splash_options/langs/default_lang');
        
        //====================================================================//
        // Init Parameters Array
        $Parameters       =     array();

        //====================================================================//
        // Server Actives Languages List
        $Parameters["Langs"] = array();
        
        //====================================================================//
        // Single Language Mode
        if (empty($multilang) && !empty($default_lang)) {
            $Parameters["Langs"][] = Mage::getStoreConfig('splashsync_splash_options/langs/default_lang');
        }
        
        //====================================================================//
        // Setup Magento Prices Parameters
        //====================================================================//
        
        //====================================================================//
        // Load Products Appliable Tax Rates
        $Store              =   Mage::app()->getStore();
        $TaxCalculation     =   Mage::getModel('tax/calculation');
        $TaxRequest         =   $TaxCalculation->getRateRequest(null, null, null, $Store);
        $AvailableTaxes     =   $TaxCalculation->getRatesForAllProductTaxClasses($TaxRequest);
        //====================================================================//
        // Setup Appliable Tax Rate
        if (!empty($AvailableTaxes)) {
            $Parameters["VAT"]              = array_shift($AvailableTaxes);
        }
        
        $Parameters["Currency"]         = Mage::app()->getStore()->getCurrentCurrencyCode();
        $Parameters["CurrencySymbol"]   = Mage::app()->getLocale()->currency($Parameters["Currency"])->getSymbol();
        $Parameters["PriceBase"]        = ( (bool) Mage::getStoreConfig('tax/calculation/price_includes_tax') ) ? "TTC" : "HT";
        $Parameters["PricesPrecision"]  = 3;
        
        
        return $Parameters;
    }
    
    /**
     *      @abstract       Return Local Server Test Sequences as Aarray
     *
     *      THIS FUNCTION IS OPTIONNAL - USE IT ONLY IF REQUIRED
     *
     *      This function called on each initialization of module's tests sequences.
     *      It's aim is to list different configurations for testing on local system.
     *
     *      If Name = List, Result must be an array including list of Sequences Names.
     *
     *      If Name = ASequenceName, Function will Setup Sequence on Local System.
     *
     *      @return         array       $Sequences
     */
    public static function TestSequences($Name = null)
    {
        switch ($Name) {
            case "ProductVATIncluded":
                Splash::local()->LoadLocalUser();
                Mage::getConfig()->saveConfig('tax/calculation/price_includes_tax', '1');
                Mage::getConfig()->cleanCache();
                return array();
                
            case "ProductVATExcluded":
                Splash::local()->LoadLocalUser();
                Mage::getConfig()->saveConfig('tax/calculation/price_includes_tax', '0');
                Mage::getConfig()->cleanCache();
                return array();
            
            case "List":
                return array( "ProductVATIncluded" , "ProductVATExcluded" );
        }
    }
    
//====================================================================//
// *******************************************************************//
// Place Here Any SPECIFIC ro COMMON Local Functions
// *******************************************************************//
//====================================================================//
    
    /**
     * @abstract    Initiate Local Request User if not already defined
     * 
     * @return      bool
     */
    public function LoadLocalUser()
    {
        //====================================================================//
        // Verify User Not Already Authenticated
        if (Mage::registry('isSecureArea')) {
            return true;
        }
                
        //====================================================================//
        // LOAD USER FROM PARAMETERS
        //====================================================================//
        $Login  =   Mage::getStoreConfig('splashsync_splash_options/user/login');
        $Pwd    =   Mage::getStoreConfig('splashsync_splash_options/user/pwd');
        
        //====================================================================//
        // Safety Check
        if (empty($Login) || empty($Pwd)) {
            return Splash::log()->err("ErrSelfTestNoUser");
        }
        
        //====================================================================//
        // Authenticate Admin User
        if (Mage::getModel('admin/user')->authenticate($Login, $Pwd)) {
            Mage::register('isSecureArea', true);
            return true;
        }
        return Splash::log()->err("ErrUnableToLoginUser");
    }
        
//====================================================================//
//  Magento Dedicated Parameter SelfTests
//====================================================================//
    /**
     *   @abstract   Verify Langage Parameters are correctly set.
     *   @return     mixed
     */
    public static function validateLanguageParameters()
    {
        //====================================================================//
        //  Verify - SINGLE LANGUAGE MODE
        if (empty(Mage::getStoreConfig('splashsync_splash_options/langs/multilang'))) {
            if (empty(Mage::getStoreConfig('splashsync_splash_options/langs/default_lang'))) {
                return Splash::log()->err("In single Language mode, You must select a default Language for Multilang Fields");
            }
            return true;
        }
        
        //====================================================================//
        //  Verify - MULTILANG MODE - ALL STORES HAVE AN ISO LANGUAGE
        foreach (Mage::app()->getWebsites() as $Website) {
            foreach ($Website->getStores() as $Store) {
                if (empty($Store->getConfig('splashsync_splash_options/langs/store_lang'))) {
                    return Splash::log()->err("Multi-Language mode, You must select a Language for Store: " . $Store->getName());
                }
            }
        }
        return true;
    }
    
//====================================================================//
//  Magento Getters & Setters
//====================================================================//
    
    private function getExtensionVersion()
    {
        if (!isset(Mage::getConfig()->getNode()->modules->SplashSync_Splash->version)) {
            return 'Unknown';
        }
        return (string) Mage::getConfig()->getNode()->modules->SplashSync_Splash->version;
    }
}
