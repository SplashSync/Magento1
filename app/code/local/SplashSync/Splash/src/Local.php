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

use Splash\Core\SplashCore      as Splash;
use Mage;

class Local 
{
    //====================================================================//
    // Class Constructor
    //====================================================================//
        
    /**
     *      @abstract       Class Constructor (Used only if localy necessary)
     *      @return         int                     0 if KO, >0 if OK
     */
    function __construct()
    {
        return True;
    }

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
        if ( Mage::getStoreConfig('splashsync_splash_options/advanced/expert') ) {
            if ( !empty(Mage::getStoreConfig('splashsync_splash_options/advanced/server_url')) ) {
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
        // When Library is called in server mode ONLY
        //====================================================================//
        if ( SPLASH_SERVER_MODE )
        {
            // NOTHING TO DO 
        }

        //====================================================================//
        // When Library is called in client mode ONLY
        //====================================================================//
        else
        {
            // NOTHING TO DO 
        }

        //====================================================================//
        // When Library is called in both clinet & server mode
        //====================================================================//

        //====================================================================//
        // Initialize Magento ...
        if ( !defined("BP") )
        {
            require_once( dirname(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__))))))).'/app/Mage.php' );
            // Initialize Magento ...
            Mage::app();
//            Mage::app("admin");
        }
        //====================================================================//
        // Load Recurent Use Parameters
        $this->multilang    =   Mage::getStoreConfig('splashsync_splash_options/langs/multilang');
        $this->default_lang =   Mage::getStoreConfig('splashsync_splash_options/langs/default_lang');       
        
        Mage::app()->setCurrentStore(\Mage_Core_Model_App::ADMIN_STORE_ID);
        
        return True;
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
        Splash::Translator()->Load("main@local");          
        
        //====================================================================//
        //  Verify - Server Identifier Given
        if ( empty(Mage::getStoreConfig('splashsync_splash_options/core/id')) ) {
            return Splash::Log()->Err("ErrSelfTestNoWsId");
        }        
                
        //====================================================================//
        //  Verify - Server Encrypt Key Given
        if ( empty(Mage::getStoreConfig('splashsync_splash_options/core/key')) ) {
            return Splash::Log()->Err("ErrSelfTestNoWsKey");
        }        
        
        //====================================================================//
        //  Verify - Default Language is Given
        if ( empty(Mage::getStoreConfig('splashsync_splash_options/core/lang')) ) {
            return Splash::Log()->Err("ErrSelfTestDfLang");
        }        

        
        //====================================================================//
        //  Verify - User Selected
        if ( empty(Mage::getStoreConfig('splashsync_splash_options/user/login')) || empty(Mage::getStoreConfig('splashsync_splash_options/user/pwd')) ) {
            return Splash::Log()->Err("ErrSelfTestNoUser");
        }        

        //====================================================================//
        //  Verify - FIELDS TRANSLATIONS CONFIG
        if ( !self::validateLanguageParameters() )
        {
            return False;
        }
        
        //====================================================================//
        //  Verify - PRODUCT DEFAULT ATTRIBUTE SET
        $AttributeSetId =   Mage::getStoreConfig('splashsync_splash_options/products/attribute_set');
        if ( empty($AttributeSetId) ) {
            return Splash::Log()->Err("No Default Product Attribute Set Selected");
        }        
        $AttributeSet    =   Mage::getModel('eav/entity_attribute_set')->load($AttributeSetId);
        if ( !$AttributeSet->getAttributeSetId() ) {
            return Splash::Log()->Err("Wrong Product Attribute Set Identifier");
        }        
        //====================================================================//
        //  Verify - PRODUCT DEFAULT STOCK
        $StockId    =   Mage::getStoreConfig('splashsync_splash_options/products/default_stock');
        if ( empty($StockId) ) {
            return Splash::Log()->Err("No Default Product Warehouse Selected");
        }        
        $Stock    =   Mage::getModel('cataloginventory/stock')->load($StockId);
        if ( !$Stock->getStockId() ) {
            return Splash::Log()->Err("Wrong Product Warehouse Selected");
        }        
//        Splash::Log()->War("WarSelfTestSkipped");
        return Splash::Log()->Msg("Self Test Passed");
    }       
    
    /**
     *  @abstract   Update Server Informations with local Data
     * 
     *  @param     arrayobject  $Informations   Informations Inputs
     * 
     *  @return     arrayobject
     */
    public function Informations($Informations)
    {
        global $conf;
        $g = $conf->global;
        
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
        $Response->icoraw           = Splash::File()->ReadFileContents(Mage::getBaseDir()  . "/favicon.ico");
        $Response->logourl          = Mage::getStoreConfig('web/secure/base_url') . "skin/frontend/default/default/images/logo_print.gif";
        $Response->logoraw          = Splash::File()->ReadFileContents(Mage::getBaseDir("skin") . "/frontend/default/default/images/logo_print.gif");
        
        //====================================================================//
        // Server Informations
        $Response->servertype       =   "Magento " . Mage::getVersion();;
        $Response->serverurl        =   Mage::getStoreConfig('web/secure/base_url');
        
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
        if ( empty($multilang) && !empty($default_lang) ) {
            $Parameters["Langs"][] = Mage::getStoreConfig('splashsync_splash_options/langs/default_lang');
        } 
        
        return $Parameters;
    }   
    
//====================================================================//
// *******************************************************************//
// Place Here Any SPECIFIC ro COMMON Local Functions
// *******************************************************************//
//====================================================================//
    
    /**
     *      @abstract       Initiate Local Request User if not already defined
     *      @return         int                     0 if KO, >0 if OK
     */
    public function LoadLocalUser()
    {
        //====================================================================//
        // Verify User Not Already Authenticated
        if ( Mage::registry('isSecureArea') ) {
            return True;
        }
                
        //====================================================================//
        // LOAD USER FROM PARAMETERS
        //====================================================================//
        $Login  =   Mage::getStoreConfig('splashsync_splash_options/user/login');
        $Pwd    =   Mage::getStoreConfig('splashsync_splash_options/user/pwd');
        
        //====================================================================//
        // Safety Check
        if ( empty($Login) || empty($Pwd) ) {
            return Splash::Log()->Err("ErrSelfTestNoUser");
        }        
        
        //====================================================================//
        // Authenticate Admin User
        if (Mage::getModel('admin/user')->authenticate($Login, $Pwd) ) {
            Mage::register('isSecureArea', true);
            return True;
        }        
        return Splash::Log()->Err("ErrSelfTestNoUser");
    }
    
    /**
    *   @abstract   Generic Delete of requested Object
    *   @param      string      $Type           Object Magento Type
    *   @param      int         $Id             Object Id.  If NULL, Object needs to be created.
    *   @return     int                         0 if KO, >0 if OK 
    */    
    public function ObjectDelete($Type, $Id=NULL)
    {
        //====================================================================//
        // Stack Trace
        Splash::Log()->Trace(__CLASS__,__FUNCTION__);    
        //====================================================================//
        // Safety Checks 
        if (empty($Id)) {
            return Splash::Log()->Err("ErrSchNoObjectId",__CLASS__."::".__FUNCTION__);
        }
        //====================================================================//
        // Initialize Remote Admin user ...
        if ( !Splash::Local()->LoadLocalUser() ) {
            return True;       
        }      
        //====================================================================//
        // Load Object From DataBase
        //====================================================================//
        $Object = Mage::getModel($Type)->load($Id);
        if ( $Object->getEntityId() != $Id )   {
            return Splash::Log()->War("ErrLocalTpl",__CLASS__,__FUNCTION__,"Unable to load (" . $Id . ").");
        }          
        //====================================================================//
        // Delete Object From DataBase
        //====================================================================//
        $Object->delete();
        return True;       
    }   
    
    /**
     *   @abstract   Generic Save of requested Object
     *   @param      mixed      $Object          Magento Object
     *   @param      bool       $Updated         Object Updated Flag.  If False, no action but dispatch a Debug & Trace Message.
     *   @param      string     $Name            Object Name for Messaging. 
     *   @return     int                         Magento Object Id 
     */    
    public function ObjectSave($Object, $Updated = True, $Name = "Object")
    {
        //====================================================================//
        // Stack Trace
        Splash::Log()->Trace(__CLASS__,__FUNCTION__);    
        //====================================================================//
        // Verify Update Is requiered
        if ( $Updated == False ) {
            Splash::Log()->Deb("MsgLocalNoUpdateReq",__CLASS__,__FUNCTION__);
            return $Object->getEntityId();
        }
        //====================================================================//
        // If Id Given = > Update Object
        //====================================================================//
        $Object->save();
        if ( $Object->_hasDataChanges ) {  
            return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,"Unable to update (" . $Object->getEntityId() . ").");
        }
        Splash::Log()->Deb("MsgLocalTpl",__CLASS__,__FUNCTION__,$Name . " Updated");
        return $Object->getEntityId();   
    } 
    
    /**
     *   @abstract   Generic - Compute Numeric Values Changes for a Given Object
     *   @param      mixed      $Object          Magento Object
     *   @return     array                       Result Array 
     */    
    public function ObjectChanges($Object)
    {
        //====================================================================//
        // Read Original Object Datas
        $Changes = $Object->getOrigData();
        //====================================================================//
        // Compute Data Changes
        array_walk_recursive($Object->getData(), function($item, $key) use (&$Changes){
            if ( isset($Changes[$key]) && is_numeric($item) && is_numeric($Changes[$key])) {
                $Changes[$key] = $item - $Changes[$key];
            } else {
                $Changes[$key] = Null;
            }
        });

        return $Changes;
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
        if (    empty(Mage::getStoreConfig('splashsync_splash_options/langs/multilang')) ) {
            if (    empty(Mage::getStoreConfig('splashsync_splash_options/langs/default_lang')) ) {
                return Splash::Log()->Err("In single Language mode, You must select a default Language for Multilang Fields");
            }        
            return true;
        }
        
        //====================================================================//
        //  Verify - MULTILANG MODE - ALL STORES HAVE AN ISO LANGUAGE
        foreach (Mage::app()->getWebsites() as $Website) {
            foreach ($Website->getStores() as $Store) {
                if ( empty($Store->getConfig('splashsync_splash_options/langs/store_lang')) ) {
                    return Splash::Log()->Err("Multi-Language mode, You must select a Language for Store: " . $Store->getName() );
                }
            }
        }       
        return true;
    }   
    
//====================================================================//
//  Magento Getters & Setters
//====================================================================//
    
    /**
     *      @abstract       Read Multilangual Fields of an Object
     *      @param          object      $Object     Pointer to Prestashop Object
     *      @param          array       $key        Id of a Multilangual Contents
     *      @return         int                     0 if KO, 1 if OK
     */
    public function getMultilang(&$Object=Null,$key=Null)
    {
        if ( empty($this->multilang) && !empty($this->default_lang) ) {
            return array(
                Mage::getStoreConfig('splashsync_splash_options/langs/default_lang') => $Object->getData($key)
            );
        }           
    }

    /**
     *      @abstract       Update Multilangual Fields of an Object
     * 
     *      @param          object      $Object     Pointer to Prestashop Object
     *      @param          array       $key        Id of a Multilangual Contents
     *      @param          array       $Data       New Multilangual Contents
     *      @param          int         $MaxLength  Maximum Contents Lenght
     * 
     *      @return         bool                     0 if no update needed, 1 if update needed
     */
    public function setMultilang($Object=Null,$key=Null,$Data=Null,$MaxLength=null)
    {
        //====================================================================//        
        // Check Received Data Are Valid
        if ( !is_array($Data) && !is_a($Data, "ArrayObject") ) { 
            return False;
        }
        
        $UpdateRequired = False;
        
        if ( empty($this->multilang) && !empty($this->default_lang) ) {
            //====================================================================//        
            // Compare Data
            if ( !array_key_exists($this->default_lang,$Data) 
                ||  ( $Object->getData($key) === $Data[$this->default_lang]) ) {             
                return $UpdateRequired;
            }
            //====================================================================//        
            // Verify Data Lenght
            if ( $MaxLength &&  ( strlen($Data[$this->default_lang]) > $MaxLength) ) {             
                Splash::Log()->War("MsgLocalTpl",__CLASS__,__FUNCTION__,"Text is too long for filed " . $key . ", modification skipped.");
                return $UpdateRequired;
            }
            //====================================================================//        
            // Update Data
            $Object->setData($key,$Data[$this->default_lang]);
            $UpdateRequired = True;
        }   
        
        //====================================================================//        
        // Update Multilangual Contents
//        foreach ($Data as $IsoCode => $Content) {
//            //====================================================================//        
//            // Check Language Is Valid
//            $LanguageCode = self::Lang_Decode($IsoCode);
//            if ( !Validate::isLanguageCode($LanguageCode) ) {   
//                continue;  
//            }
//            //====================================================================//        
//            // Load Language
//            $Language = Language::getLanguageByIETFCode($LanguageCode);
//            if ( empty($Language) ) {   
//                Splash::Log()->War("MsgLocalTpl",__CLASS__,__FUNCTION__,"Language " . $LanguageCode . " not available on this server.");
//                continue;  
//            }
//            //====================================================================//        
//            // Store Contents
//            //====================================================================//        
//            //====================================================================//        
//            // Extract Contents
//            $Current   =   &$Object->$key;
//            //====================================================================//        
//            // Create Array if Needed
//            if ( !is_array($Current) ) {    $Current = array();     }             
//            //====================================================================//        
//            // Compare Data
//            if ( array_key_exists($Language->id, $Current) && ( $Current[$Language->id] === $Content) ) {             
//                continue;
//            }
//            //====================================================================//        
//            // Verify Data Lenght
//            if ( $MaxLength &&  ( strlen($Content) > $MaxLength) ) {             
//                Splash::Log()->War("MsgLocalTpl",__CLASS__,__FUNCTION__,"Text is too long for filed " . $key . ", modification skipped.");
//                continue;
//            }
//            
//            
//            //====================================================================//        
//            // Update Data
//            $Current[$Language->id]     = $Content;
//            $UpdateRequired = True;
//        }

        return $UpdateRequired;
    }     
    
}

?>
