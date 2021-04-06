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

namespace Splash\Local;

use ArrayObject;
use Mage;
use Mage_Core_Model_App;
use Splash\Core\SplashCore      as Splash;
use Splash\Models\LocalClassInterface;

/**
 * Splash PHP Module For Magento 1 - Local Core Class
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Local implements LocalClassInterface
{
    /**
     * Object Commit Mode
     *
     * @var string
     */
    public $action;

    //====================================================================//
    // *******************************************************************//
    //  MANDATORY CORE MODULE LOCAL FUNCTIONS
    // *******************************************************************//
    //====================================================================//

    /**
     * {@inheritDoc}
     */
    public function parameters(): array
    {
        $parameters = array();

        //====================================================================//
        // Server Identification Parameters
        $parameters["WsIdentifier"] = Mage::getStoreConfig('splashsync_splash_options/core/id');
        $parameters["WsEncryptionKey"] = Mage::getStoreConfig('splashsync_splash_options/core/key');

        //====================================================================//
        // Server Ws Method
        $parameters["WsMethod"] = Mage::getStoreConfig('splashsync_splash_options/core/use_nusoap') ? "NuSOAP" : "SOAP";

        //====================================================================//
        // If Expert Mode => Allow Override of Server Host Address
        if (Mage::getStoreConfig('splashsync_splash_options/advanced/expert')) {
            if (!empty(Mage::getStoreConfig('splashsync_splash_options/advanced/server_url'))) {
                $parameters["WsHost"] = Mage::getStoreConfig('splashsync_splash_options/advanced/server_url');
            }
        }

        //====================================================================//
        // Override Module Parameters with Local User Selected Lang
        $parameters["DefaultLanguage"] = Mage::getStoreConfig('splashsync_splash_options/core/lang');

        //====================================================================//
        // Override Module Local Name in Logs
        $parameters["localname"] = Mage::getStoreConfig('general/store_information/name');

        return $parameters;
    }

    /**
     * Include Local Includes Files
     *
     *      Include here any local files required by local functions.
     *      This Function is called each time the module is loaded
     *
     *      There may be differents scenarios depending if module is
     *      loaded as a library or as a NuSOAP Server.
     *
     *      This is triggered by global constant SPLASH_SERVER_MODE.
     *
     * @return bool
     */
    public function includes(): bool
    {
        //====================================================================//
        // When Library is called in both client & server mode
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
            if (defined("SPLASH_SERVER_MODE") && !empty(SPLASH_SERVER_MODE)) {
                Mage::app()->setCurrentStore((string) Mage_Core_Model_App::ADMIN_STORE_ID);
            }
        }

        //====================================================================//
        //  Load Local Translation File
        Splash::translator()->load("main@local");

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function selfTest(): bool
    {
        //====================================================================//
        //  Load Local Translation File
        Splash::translator()->load("main@local");

        //====================================================================//
        //  Verify - Core Parameters
        if (!self::selfTestCoreParameters()) {
            return false;
        }

        //====================================================================//
        //  Verify - Products Parameters
        if (!self::selfTestProductsParameters()) {
            return false;
        }

        return Splash::log()->msg("Self Test Passed");
    }

    /**
     * {@inheritDoc}
     */
    public function informations($informations): ArrayObject
    {
        //====================================================================//
        // Init Response Object
        $response = $informations;

        //====================================================================//
        // Server General Description
        $response->shortdesc = "Splash Module for Magento 1";
        $response->longdesc = "Splash SOAP Connector Module for Magento 1.";

        //====================================================================//
        // Company Informations
        $response->company = Mage::getStoreConfig('general/store_information/name');
        $response->address = Mage::getStoreConfig('general/store_information/address');
        $response->zip = "...";
        $response->town = "...";
        $response->country = Mage::getStoreConfig('general/store_information/merchant_country');
        $response->www = Mage::getStoreConfig('web/secure/base_url');
        $response->email = Mage::getStoreConfig('trans_email/ident_general/email');
        $response->phone = Mage::getStoreConfig('general/store_information/phone');

        //====================================================================//
        // Server Logo & Images
        $response->icoraw = Splash::file()->readFileContents(Mage::getBaseDir()."/favicon.ico");
        $response->logourl = Mage::getStoreConfig('web/secure/base_url');
        $response->logourl .= "skin/frontend/default/default/images/logo_print.gif";
        $response->logoraw = Splash::file()->readFileContents(
            Mage::getBaseDir("skin")."/frontend/default/default/images/logo_print.gif"
        );

        //====================================================================//
        // Server Informations
        $response->servertype = "Magento ".Mage::getVersion();
        $response->serverurl = Mage::getStoreConfig('web/secure/base_url');

        //====================================================================//
        // Module Informations
        $response->moduleversion = $this->getExtensionVersion().' (Splash Php Core '.SPLASH_VERSION.')';

        return $response;
    }

    //====================================================================//
    // *******************************************************************//
    //  OPTIONAl CORE MODULE LOCAL FUNCTIONS
    // *******************************************************************//
    //====================================================================//

    /**
     * {@inheritDoc}
     */
    public function testParameters(): array
    {
        //====================================================================//
        // Load Recurrent Use Parameters
        $multiLang = Mage::getStoreConfig('splashsync_splash_options/langs/multilang');
        $defaultLang = Mage::getStoreConfig('splashsync_splash_options/langs/default_lang');

        //====================================================================//
        // Init Parameters Array
        $parameters = array();

        //====================================================================//
        // Server Actives Languages List
        $parameters["Langs"] = array();

        //====================================================================//
        // Single Language Mode
        if (empty($multiLang) && !empty($defaultLang)) {
            $parameters["Langs"][] = Mage::getStoreConfig('splashsync_splash_options/langs/default_lang');
        //====================================================================//
        // Multi Language Mode
        } elseif (!empty($multiLang)) {
            foreach (Mage::app()->getStores() as $store) {
                $isoLang = Mage::getStoreConfig('splashsync_splash_options/langs/store_lang', $store->getId());
                $parameters["Langs"][$store->getId()] = $isoLang;
            }
        }

        //====================================================================//
        // Setup Magento Prices Parameters
        //====================================================================//

        //====================================================================//
        // Load Products Tax Rates
        $store = Mage::app()->getStore();
        /** @var \Mage_Tax_Model_Calculation $taxCalculation */
        $taxCalculation = Mage::getModel('tax/calculation');
        $taxRequest = $taxCalculation->getRateRequest(null, null, null, $store->getEntityId());
        $availableTaxes = $taxCalculation->getRatesForAllProductTaxClasses($taxRequest);
        //====================================================================//
        // Setup Tax Rate
        if (!empty($availableTaxes)) {
            $parameters["VAT"] = array_shift($availableTaxes);
        }

        $parameters["Currency"] = Mage::app()->getStore()->getCurrentCurrencyCode();
        $parameters["CurrencySymbol"] = Mage::app()->getLocale()->currency($parameters["Currency"])->getSymbol();
        $parameters["PriceBase"] = ((bool) Mage::getStoreConfig('tax/calculation/price_includes_tax')) ? "TTC" : "HT";
        $parameters["PricesPrecision"] = 3;

        return $parameters;
    }

    /**
     * {@inheritDoc}
     */
    public function testSequences($name = null): array
    {
        switch ($name) {
            case "ProductVATIncluded":
                $this->loadLocalUser();
                Mage::getConfig()->saveConfig('tax/calculation/price_includes_tax', '1');
                Mage::getConfig()->saveConfig('splashsync_splash_options/langs/multilang', '0');
                Mage::getConfig()->cleanCache();

                return array();
            case "ProductVATExcluded":
                $this->loadLocalUser();
                Mage::getConfig()->saveConfig('tax/calculation/price_includes_tax', '0');
                Mage::getConfig()->saveConfig('splashsync_splash_options/langs/multilang', '0');
                Mage::getConfig()->cleanCache();

                return array();
            case "Multilang":
                $this->loadLocalUser();
                Mage::getConfig()->saveConfig('tax/calculation/price_includes_tax', '0');
                Mage::getConfig()->saveConfig('splashsync_splash_options/langs/multilang', '1');
                Mage::getConfig()->cleanCache();

                return array();
            case "List":
                return array(
                    "ProductVATIncluded" ,
                    "ProductVATExcluded" ,
                    "Multilang"
                );
        }

        return array();
    }

    //====================================================================//
    // *******************************************************************//
    // Place Here Any SPECIFIC ro COMMON Local Functions
    // *******************************************************************//
    //====================================================================//

    /**
     * Initiate Local Request User if not already defined
     *
     * @return bool
     */
    public function loadLocalUser(): bool
    {
        //====================================================================//
        // Verify User Not Already Authenticated
        if (Mage::registry('isSecureArea')) {
            return true;
        }

        //====================================================================//
        // LOAD USER FROM PARAMETERS
        //====================================================================//
        $login = Mage::getStoreConfig('splashsync_splash_options/user/login');
        $pwd = Mage::getStoreConfig('splashsync_splash_options/user/pwd');

        //====================================================================//
        // Safety Check
        if (empty($login) || empty($pwd)) {
            return Splash::log()->err("ErrSelfTestNoUser");
        }

        //====================================================================//
        // Authenticate Admin User
        /** @var \Mage_Admin_Model_User $userModel */
        $userModel = Mage::getModel('admin/user');
        if ($userModel->authenticate($login, $pwd)) {
            Mage::register('isSecureArea', true);

            return true;
        }

        return Splash::log()->err("ErrUnableToLoginUser");
    }

    //====================================================================//
    //  Magento Dedicated Parameter SelfTests
    //====================================================================//

    /**
     * Verify Langage Parameters are correctly set.
     *
     * @return bool
     */
    public static function validateLanguageParameters(): bool
    {
        //====================================================================//
        //  Verify - SINGLE LANGUAGE MODE
        if (empty(Mage::getStoreConfig('splashsync_splash_options/langs/multilang'))) {
            if (empty(Mage::getStoreConfig('splashsync_splash_options/langs/default_lang'))) {
                return Splash::log()->err(
                    "In single Language mode, You must select a default Language for Multi-lang Fields"
                );
            }

            return true;
        }

        //====================================================================//
        //  Verify - MULTILANG MODE - ALL STORES HAVE AN ISO LANGUAGE
        foreach (Mage::app()->getWebsites() as $website) {
            foreach ($website->getStores() as $store) {
                if (empty($store->getConfig('splashsync_splash_options/langs/store_lang'))) {
                    return Splash::log()->err(
                        "Multi-Language mode, You must select a Language for Store: ".$store->getName()
                    );
                }
            }
        }

        return true;
    }

    /**
     * Check if Bundle Componants Price Mode is Enabled
     *
     * @return bool
     */
    public static function isBundleComponantsPricesMode(): bool
    {
        return (bool) Mage::getStoreConfig('splashsync_splash_options/advanced/bundle_mode');
    }

    /**
     * Self Tests - Core Parameters
     *
     * @return bool
     */
    private static function selfTestCoreParameters()
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
        if (empty(Mage::getStoreConfig('splashsync_splash_options/user/login'))
            || empty(Mage::getStoreConfig('splashsync_splash_options/user/pwd'))) {
            return Splash::log()->err("ErrSelfTestNoUser");
        }

        //====================================================================//
        //  Verify - FIELDS TRANSLATIONS CONFIG
        if (!self::validateLanguageParameters()) {
            return false;
        }

        return true;
    }

    /**
     * Self Tests - Products Parameters
     *
     * @return bool
     */
    private static function selfTestProductsParameters()
    {
        //====================================================================//
        //  Verify - PRODUCT DEFAULT ATTRIBUTE SET
        $attributeSetId = Mage::getStoreConfig('splashsync_splash_options/products/attribute_set');
        if (empty($attributeSetId)) {
            return Splash::log()->err("No Default Product Attribute Set Selected");
        }
        /** @var \Mage_Eav_Model_Entity_Attribute_Set $attributeSetModel */
        $attributeSetModel = Mage::getModel('eav/entity_attribute_set');
        if (empty($attributeSetModel->load($attributeSetId))) {
            return Splash::log()->err("Wrong Product Attribute Set Identifier");
        }
        //====================================================================//
        //  Verify - PRODUCT DEFAULT STOCK
        $stockId = Mage::getStoreConfig('splashsync_splash_options/products/default_stock');
        if (empty($stockId)) {
            return Splash::log()->err("No Default Product Warehouse Selected");
        }
        /** @var \Mage_CatalogInventory_Model_Stock $stockModel */
        $stockModel = Mage::getModel('cataloginventory/stock');
        if (empty($stockModel->load($stockId))) {
            return Splash::log()->err("Wrong Product Warehouse Selected");
        }

        //====================================================================//
        //  Verify - Product Prices Include Tax Warning
        if (Mage::getStoreConfig('tax/calculation/price_includes_tax')) {
            Splash::log()->war(
                "You selected to store Products Prices Including Tax. 
                It is highly recommended to store Product Price without Tax to work with Splash."
            );
        }
        //====================================================================//
        //  Verify - Shipping Prices Include Tax Warning
        if (Mage::getStoreConfig('tax/calculation/shipping_includes_tax')) {
            Splash::log()->war(
                "You selected to store Shipping Prices Including Tax. 
                It is highly recommended to store Shipping Price without Tax to work with Splash."
            );
        }

        return true;
    }

    //====================================================================//
    //  Magento Getters & Setters
    //====================================================================//

    /**
     * @return string
     */
    private function getExtensionVersion(): string
    {
        if (!isset(Mage::getConfig()->getNode()->modules->SplashSync_Splash->version)) {
            return 'Unknown';
        }

        return (string) Mage::getConfig()->getNode()->modules->SplashSync_Splash->version;
    }
}
