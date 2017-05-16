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

namespace   Splash\Local\Objects;

use Splash\Models\ObjectBase;
use Splash\Core\SplashCore      as Splash;
use Mage;

/**
 * @abstract    Splash PHP Module For Magento 1 - ThirdParty Address Object Intégration
 * @author      B. Paquier <contact@splashsync.com>
 */
class Address extends ObjectBase
{
    
    //====================================================================//
    // Object Definition Parameters	
    //====================================================================//
    
    /**
     *  Object Disable Flag. Uncomment this line to Override this flag and disable Object.
     */
//    protected static    $DISABLED        =  True;
    
    /**
     *  Object Name (Translated by Module)
     */
    protected static    $NAME            =  "Address";
    
    /**
     *  Object Description (Translated by Module) 
     */
    protected static    $DESCRIPTION     =  "Magento 1 Customers Address Object";    
    
    /**
     *  Object Icon (FontAwesome or Glyph ico tag) 
     */
    protected static    $ICO     =  "fa fa-envelope-o";

    /**
     *  Object Synchronistion Limitations 
     *  
     *  This Flags are Used by Splash Server to Prevent Unexpected Operations on Remote Server
     */
    protected static    $ALLOW_PUSH_CREATED         =  TRUE;        // Allow Creation Of New Local Objects
    protected static    $ALLOW_PUSH_UPDATED         =  TRUE;        // Allow Update Of Existing Local Objects
    protected static    $ALLOW_PUSH_DELETED         =  TRUE;        // Allow Delete Of Existing Local Objects
    
    /**
     *  Object Synchronistion Recommended Configuration 
     */
    protected static    $ENABLE_PUSH_CREATED       =  TRUE;        // Enable Creation Of New Local Objects when Not Existing
    protected static    $ENABLE_PUSH_UPDATED       =  TRUE;         // Enable Update Of Existing Local Objects when Modified Remotly
    protected static    $ENABLE_PUSH_DELETED       =  TRUE;         // Enable Delete Of Existing Local Objects when Deleted Remotly

    protected static    $ENABLE_PULL_CREATED       =  TRUE;         // Enable Import Of New Local Objects 
    protected static    $ENABLE_PULL_UPDATED       =  TRUE;         // Enable Import of Updates of Local Objects when Modified Localy
    protected static    $ENABLE_PULL_DELETED       =  TRUE;         // Enable Delete Of Remotes Objects when Deleted Localy 
    
    //====================================================================//
    // General Class Variables	
    //====================================================================//

    //====================================================================//
    // Class Constructor
    //====================================================================//
        
    /**
     *      @abstract       Class Constructor (Used only if localy necessary)
     *      @return         int                     0 if KO, >0 if OK
     */
    function __construct()
    {
        //====================================================================//
        // Place Here Any SPECIFIC Initialisation Code
        //====================================================================//
        
        return True;
    }    
    
    //====================================================================//
    // Class Main Functions
    //====================================================================//
    
    /**
    *   @abstract     Return List Of available data for Customer
    *   @return       array   $data     List of all customers available field
    */
    public function Fields()
    {
        //====================================================================//
        // Stack Trace
        Splash::Log()->Trace(__CLASS__,__FUNCTION__);             
        
        //====================================================================//
        //  Load Local Translation File
        Splash::Translator()->Load("objects@local");          
        
        //====================================================================//
        // CORE INFORMATIONS
        //====================================================================//
        $this->buildCoreFields();
        
        //====================================================================//
        // MAIN INFORMATIONS
        //====================================================================//
        $this->buildMainFields();

        //====================================================================//
        // OPTIONNAL INFORMATIONS
        //====================================================================//
        $this->buildOptionalFields();
        
        //====================================================================//
        // Publish Fields
        return $this->FieldsFactory()->Publish();
    }
    
    /**
    *   @abstract     Return List Of Customer with required filters
    *   @param        array   $filters          Filters for Customers List. 
    *   @param        array   $params              Search parameters for result List. 
    *                         $params["max"]       Maximum Number of results 
    *                         $params["offset"]    List Start Offset 
    *                         $params["sortfield"] Field name for sort list (Available fields listed below)    
    *                         $params["sortorder"] List Order Constraign (Default = ASC)    
    *   @return       array   $data             List of all customers main data
    *                         $data["meta"]["total"]     ==> Total Number of results
    *                         $data["meta"]["current"]   ==> Total Number of results
    */
    public function ObjectsList($filters=NULL,$params=NULL)
    {
        //====================================================================//
        // Stack Trace
        Splash::Log()->Trace(__CLASS__,__FUNCTION__);             
	/* Get customer model, run a query */
	$Collection = Mage::getModel('customer/address')
				  ->getCollection()
				  ->addAttributeToSelect('*');        
//        //====================================================================//
//        // Setup filters
//        // Add filters with names convertions. Added LOWER function to be NON case sensitive
//        if ( !empty($filter) && is_string($filter)) {
//            //====================================================================//
//            // Search in Customer Company
//            $Where  = " LOWER( c.`company` ) LIKE LOWER( '%" . $filter ."%') ";        
//            //====================================================================//
//            // Search in Customer FirstName
//            $Where .= " OR LOWER( c.`firstname` ) LIKE LOWER( '%" . $filter ."%') ";        
//            //====================================================================//
//            // Search in Customer LastName
//            $Where .= " OR LOWER( c.`lastname` ) LIKE LOWER( '%" . $filter ."%') ";        
//            //====================================================================//
//            // Search in Customer Email
//            $Where .= " OR LOWER( c.`email` ) LIKE LOWER( '%" . $filter ."%') ";        
//            $sql->where($Where);        
//        } 
        
        //====================================================================//
        // Setup sortorder
        $sortfield = empty($params["sortfield"])?"entity_id":$params["sortfield"];
        // Build ORDER BY
        $Collection->setOrder($sortfield, $params["sortorder"] );
        //====================================================================//
        // Compute Total Number of Results
        $total      = $Collection->getSize();
        //====================================================================//
        // Build LIMIT
        $Collection->setPageSize($params["max"]);
        $Collection->setCurPage($params["offset"]);
        //====================================================================//
        // Init Result Array
        $Data       = array();
        //====================================================================//
        // For each result, read information and add to $Data
        foreach ($Collection->getItems() as $key => $Address)
        {
            $Data[$key]         = array_merge(array("company" => null), $Address->toArray());
            $Data[$key]["id"]   = $Data[$key]["entity_id"];
        }
        //====================================================================//
        // Prepare List result meta infos
        $Data["meta"]["current"]    =   count($Data);  // Store Current Number of results
        $Data["meta"]["total"]      =   $total;  // Store Total Number of results
        Splash::Log()->Deb("MsgLocalTpl",__CLASS__,__FUNCTION__,(count($Data)-1)." Customers Address Found.");
        return $Data;
    }
    
    /**
    *   @abstract     Return requested Customer Data
    *   @param        array   $id               Customers Id.  
    *   @param        array   $list             List of requested fields    
    */
    public function Get($id=NULL,$list=0)
    {
        //====================================================================//
        // Stack Trace
        Splash::Log()->Trace(__CLASS__,__FUNCTION__);             
        //====================================================================//
        // Init Reading
        $this->In = $list;
        //====================================================================//
        // Init Object 
        $this->Object = Mage::getModel('customer/address')->load($id);
        if ( $this->Object->getEntityId() != $id )   {
            return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__," Unable to load Address (" . $id . ").");
        }
        $this->Out  = array( "id" => $id);

        //====================================================================//
        // Run Through All Requested Fields
        //====================================================================//
        $Fields = is_a($this->In, "ArrayObject") ? $this->In->getArrayCopy() : $this->In;        
        foreach ($Fields as $Key => $FieldName) {
            //====================================================================//
            // Read Requested Fields
            $this->getCoreFields($Key,$FieldName);
            $this->getMainFields($Key,$FieldName);
            $this->getOptionalFields($Key,$FieldName);
        }
        
        //====================================================================//
        // Verify Requested Fields List is now Empty => All Fields Read Successfully
        if ( count($this->In) ) {
            foreach ($this->In as $FieldName) {
                Splash::Log()->Err("ErrLocalWrongField",__CLASS__,__FUNCTION__, $FieldName);
            }
            return False;
        }
        //====================================================================//
        // Return Data
        //====================================================================//
        Splash::Log()->Deb("MsgLocalTpl",__CLASS__,__FUNCTION__," DATA : " . print_r($this->Out,1));
        return $this->Out; 
    }
        
    /**
    *   @abstract     Write or Create requested Customer Data
    *   @param        array   $id               Customers Id.  If NULL, Customer needs t be created.
    *   @param        array   $list             List of requested fields    
    *   @return       string  $id               Customers Id.  If NULL, Customer wasn't created.    
    */
    public function Set($id=NULL,$list=NULL) 
    {
        //====================================================================//
        // Stack Trace
        Splash::Log()->Trace(__CLASS__,__FUNCTION__);             
        //====================================================================//
        // Init Reading
        $this->In           =   $list;
        $this->update       =   False;
        //====================================================================//
        // Init Object
        if ( !$this->setInitObject($id) ) {
            return False;
        }
        //====================================================================//
        // Run Throw All Requested Fields
        //====================================================================//
        $Fields = is_a($this->In, "ArrayObject") ? $this->In->getArrayCopy() : $this->In;        
        foreach ($Fields as $FieldName => $Data) {
            //====================================================================//
            // Write Requested Fields
            $this->setCoreFields($FieldName,$Data);
            $this->setMainFields($FieldName,$Data);
            $this->setOptionalFields($FieldName,$Data);
        }
        //====================================================================//
        // Verify Requested Fields List is now Empty => All Fields Read Successfully
        if ( count($this->In) ) {
            foreach ($this->In as $FieldName => $Data) {
                Splash::Log()->Err("ErrLocalWrongField",__CLASS__,__FUNCTION__, $FieldName);
            }
            return False;
        }        
        
        //====================================================================//
        // Create/Update Object if Requiered
        return $this->setSaveObject();
    }       

    /**
    *   @abstract   Delete requested Object
    *   @param      int         $id             Object Id.  If NULL, Object needs to be created.
    *   @return     int                         0 if KO, >0 if OK 
    */    
    public function Delete($id=NULL)
    {
        //====================================================================//
        // Execute Generic Magento Delete Function ...
        return Splash::Local()->ObjectDelete('customer/address',$id);       
    }       
 

    //====================================================================//
    // Fields Generation Functions
    //====================================================================//

    /**
    *   @abstract     Build Address Core Fields using FieldFactory
    */
    private function buildCoreFields()
    {
        //====================================================================//
        // Customer
        $this->FieldsFactory()->Create(self::ObjectId_Encode( "ThirdParty" , SPL_T_ID))
                ->Identifier("parent_id")
                ->Name("Customer")
                ->MicroData("http://schema.org/Organization","ID")
                ->isRequired();
        
        //====================================================================//
        // Company
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("company")
                ->Name("Company")
                ->MicroData("http://schema.org/Organization","legalName")
                ->isListed();
        
        //====================================================================//
        // Firstname
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("firstname")
                ->Name("First name")
                ->MicroData("http://schema.org/Person","familyName")
                ->Association("firstname","lastname")        
                ->isRequired()
                ->isListed();        
        
        //====================================================================//
        // Lastname
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("lastname")
                ->Name("Last name")
                ->MicroData("http://schema.org/Person","givenName")
                ->Association("firstname","lastname")            
                ->isRequired()
                ->isListed();             
        
        //====================================================================//
        // Prefix
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("prefix")
                ->Name("Prefix name")
                ->MicroData("http://schema.org/Person","honorificPrefix");        
        
        //====================================================================//
        // MiddleName
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("middlename")
                ->Name("Middlename")
                ->MicroData("http://schema.org/Person","additionalName");        
        
        //====================================================================//
        // Suffix
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("suffix")
                ->Name("Suffix name")
                ->MicroData("http://schema.org/Person","honorificSuffix");        
        
    }
    
    /**
    *   @abstract     Build Address Main Fields using FieldFactory
    */
    private function buildMainFields()
    {
        
        $AddressGroup = "Address";
        
        //====================================================================//
        // Addess
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("street")
                ->Name("Address")
                ->MicroData("http://schema.org/PostalAddress","streetAddress")
                ->Group($AddressGroup)
                ->isRequired();
        
        //====================================================================//
        // Zip Code
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("postcode")
                ->Name("Zip/Postal Code")
                ->MicroData("http://schema.org/PostalAddress","postalCode")
                ->Group($AddressGroup)
                ->isRequired();
        
        //====================================================================//
        // City Name
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("city")
                ->Name("City")
                ->MicroData("http://schema.org/PostalAddress","addressLocality")
                ->isRequired()
                ->Group($AddressGroup)
                ->isListed();
        
        //====================================================================//
        // State Name
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("region")
                ->Name("State")
                ->Group($AddressGroup)
                ->ReadOnly();
        
        //====================================================================//
        // State code
        $this->FieldsFactory()->Create(SPL_T_STATE)
                ->Identifier("region_id")
                ->Name("StateCode")
                ->MicroData("http://schema.org/PostalAddress","addressRegion")
                ->Group($AddressGroup)
                ->NotTested();
        
        //====================================================================//
        // Country Name
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("country")
                ->Name("Country")
                ->Group($AddressGroup)
                ->ReadOnly();
//                ->isListed();
        
        //====================================================================//
        // Country ISO Code
        $this->FieldsFactory()->Create(SPL_T_COUNTRY)
                ->Identifier("country_id")
                ->Name("CountryCode")
                ->MicroData("http://schema.org/PostalAddress","addressCountry")
                ->Group($AddressGroup)
                ->isRequired();
    }
            
    /**
    *   @abstract     Build Address Optional Fields using FieldFactory
    */
    private function buildOptionalFields()
    {
        $ContactGroup   =    "Contacts";
        $MetaGroup      =    "Meta";
        
        //====================================================================//
        // Phone
        $this->FieldsFactory()->Create(SPL_T_PHONE)
                ->Identifier("telephone")
                ->Name("Phone")
                ->Group($ContactGroup)
                ->MicroData("http://schema.org/PostalAddress","telephone");
        
        //====================================================================//
        // Fax
        $this->FieldsFactory()->Create(SPL_T_PHONE)
                ->Identifier("fax")
                ->Name("Fax")
                ->Group($ContactGroup)
                ->MicroData("http://schema.org/PostalAddress","faxNumber");

        //====================================================================//
        // VAT ID
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("vat_id")
                ->Name("VAT Number")
                ->MicroData("http://schema.org/Organization","vatID");
                
        //====================================================================//
        // TRACEABILITY INFORMATIONS
        //====================================================================//

        //====================================================================//
        // TRACEABILITY INFORMATIONS
        //====================================================================//

        //====================================================================//
        // Creation Date 
        $this->FieldsFactory()->Create(SPL_T_DATETIME)
                ->Identifier("created_at")
                ->Name("Registration")
                ->MicroData("http://schema.org/DataFeedItem","dateCreated")
                ->Group($MetaGroup)
                ->ReadOnly();
        
        //====================================================================//
        // Last Change Date 
        $this->FieldsFactory()->Create(SPL_T_DATETIME)
                ->Identifier("updated_at")
                ->Name("Last update")
                ->MicroData("http://schema.org/DataFeedItem","dateModified")
                ->Group($MetaGroup)
                ->ReadOnly();
        
    }    
     
    //====================================================================//
    // Fields Reading Functions
    //====================================================================//
    
    /**
     *  @abstract     Read requested Field
     * 
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     * 
     *  @return         none
     */
    private function getCoreFields($Key,$FieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($FieldName)
        {
            //====================================================================//
            // Direct Readings
            case 'company':
            case 'firstname':
            case 'middlename':
            case 'lastname':
            case 'prefix':
            case 'suffix':
                $this->Out[$FieldName] = $this->Object->getData($FieldName);
                break;

            //====================================================================//
            // Customer Object Id Readings
            case 'parent_id':
                $this->Out[$FieldName] = self::ObjectId_Encode( "ThirdParty" , $this->Object->getParentId() );
                break;
            
            default:
                return;            
        }
        unset($this->In[$Key]);
    }
    
    /**
     *  @abstract     Read requested Field
     * 
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     * 
     *  @return         none
     */
    private function getMainFields($Key,$FieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($FieldName)
        {
            //====================================================================//
            // Direct Readings
            case 'street':
            case 'postcode':
            case 'city':
            case 'country_id':
            case 'region':
                $this->Out[$FieldName] = $this->Object->getData($FieldName);
                break;
            //====================================================================//
            // State ISO Id - READ With Convertion
            case 'region_id':
                //====================================================================//
                // READ With Convertion
                $this->Out[$FieldName] = Mage::getModel('directory/region')
                        ->load($this->Object->getData($FieldName))
                        ->getCode();
                break;
            //====================================================================//
            // Country Name - READ With Convertion
            case 'country':
                $this->Out[$FieldName] = Mage::getModel('directory/country')
                    ->load($this->Object->getData("country_id"))
                    ->getName();
                break;
                
            default:
                return;
        }
        unset($this->In[$Key]);
    }    
  
    /**
     *  @abstract     Read requested Field
     * 
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     * 
     *  @return         none
     */
    private function getOptionalFields($Key,$FieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($FieldName)
        {
            //====================================================================//
            // Direct Readings
            case 'telephone':
            case 'fax':
            case 'vat_id':
                $this->Out[$FieldName] = $this->Object->getData($FieldName);
                break;
            case 'created_at':
            case 'updated_at':
                $this->Out[$FieldName] = date( SPL_T_DATETIMECAST, Mage::getModel("core/date")->timestamp($this->Object->getData($FieldName)));
                break;
            default:
                return;
        }
        unset($this->In[$Key]);
    }    
    
    //====================================================================//
    // Fields Writting Functions
    //====================================================================//
      
    /**
     *  @abstract     Init Object vefore Writting Fields
     * 
     *  @param        array   $id               Object Id. If NULL, Object needs t be created.
     * 
     */
    private function setInitObject($id) {
        
        //====================================================================//
        // If $id Given => Load Customer Object From DataBase
        //====================================================================//
        if ( !empty($id) )
        {
            $this->Object = Mage::getModel('customer/address')->load($id);
            if ( $this->Object->getEntityId() != $id )   {
                return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__," Unable to load Address (" . $id . ").");
            }            
        }
        //====================================================================//
        // If NO $id Given  => Verify Input Data includes minimal valid values
        //                  => Setup Standard Parameters For New Customers
        //====================================================================//
        else
        {
            //====================================================================//
            // Check Address Minimum Fields Are Given
            if ( empty($this->In["parent_id"]) ) {
                return Splash::Log()->Err("ErrLocalFieldMissing",__CLASS__,__FUNCTION__,"parent_id");
            }
            if ( empty($this->In["firstname"]) ) {
                return Splash::Log()->Err("ErrLocalFieldMissing",__CLASS__,__FUNCTION__,"firstname");
            }
            if ( empty($this->In["lastname"]) ) {
                return Splash::Log()->Err("ErrLocalFieldMissing",__CLASS__,__FUNCTION__,"lastname");
            }
            if ( empty($this->In["street"]) ) {
                return Splash::Log()->Err("ErrLocalFieldMissing",__CLASS__,__FUNCTION__,"address1");
            }
            if ( empty($this->In["postcode"]) ) {
                return Splash::Log()->Err("ErrLocalFieldMissing",__CLASS__,__FUNCTION__,"postcode");
            }
            if ( empty($this->In["city"]) ) {
                return Splash::Log()->Err("ErrLocalFieldMissing",__CLASS__,__FUNCTION__,"city");
            }
            if ( empty($this->In["country_id"]) ) {
                return Splash::Log()->Err("ErrLocalFieldMissing",__CLASS__,__FUNCTION__,"country_id");
            }

            //====================================================================//
            // Create Empty Address
            $this->Object = Mage::getModel('customer/address');           
        }
        
        return True;
    }
        
    /**
     *  @abstract     Write Given Fields
     * 
     *  @param        string    $FieldName              Field Identifier / Name
     *  @param        mixed     $Data                   Field Data
     * 
     *  @return         none
     */
    private function setCoreFields($FieldName,$Data) 
    {
        //====================================================================//
        // WRITE Field
        switch ($FieldName)
        {
            //====================================================================//
            // Direct Writtings
            case 'company':
            case 'firstname':
            case 'middlename':
            case 'lastname':
            case 'prefix':
            case 'suffix':
                if ( $this->Object->getData($FieldName) != $Data ) {
                    $this->Object->setData($FieldName, $Data);
                    $this->update = True;
                }  
                break;

            //====================================================================//
            // Customer Object Id Writtings
            case 'parent_id':
                $this->setParentId($Data);
                break;                    
            default:
                return;            
        }
        unset($this->In[$FieldName]);
    }
    
    /**
     *  @abstract     Write Given Fields
     * 
     *  @param        string    $FieldName              Field Identifier / Name
     *  @param        mixed     $Data                   Field Data
     * 
     *  @return         none
     */
    private function setMainFields($FieldName,$Data) 
    {
        
        //====================================================================//
        // WRITE Field
        switch ($FieldName)
        {
            //====================================================================//
            // Direct Readings
            case 'street':
            case 'postcode':
            case 'city':
            case 'country_id':
                if ( $this->Object->getData($FieldName) != $Data ) {
                    $this->Object->setData($FieldName, $Data);
                    $this->update = True;
                }  
                break;

            //====================================================================//
            // State ISO Id - READ With Convertion
            case 'region_id':
                //====================================================================//
                // Get Country ISO Id - From Inputs or From Current Objects
                $CountryId  =   isset($this->In["country_id"])?$this->In["country_id"]:$this->Object->getData("country_id");
                $RegionId   =   Mage::getModel('directory/region')
                        ->loadByCode($Data,$CountryId)->getRegionId();
                if ( ( $RegionId ) && $this->Object->getData($FieldName)  != $RegionId ) {
                    $this->Object->setData($FieldName, $RegionId);
                    $this->update = True;
                }  
                unset($this->In[$FieldName]);
            default:
                return;            
        }
        unset($this->In[$FieldName]);
    }    

    /**
     *  @abstract     Write Given Fields
     * 
     *  @param        string    $FieldName              Field Identifier / Name
     *  @param        mixed     $Data                   Field Data
     * 
     *  @return         none
     */
    private function setOptionalFields($FieldName,$Data) 
    {
        //====================================================================//
        // WRITE Field
        switch ($FieldName)
        {
            //====================================================================//
            // Direct Readings
            case 'telephone':
            case 'fax':
            case 'vat_id':
                if ( $this->Object->getData($FieldName) != $Data ) {
                    $this->Object->setData($FieldName, $Data);
                    $this->update = True;
                }  
                break;
            default:
                return;            
        }
        unset($this->In[$FieldName]);
    }

    /**
     *  @abstract     Write Given Fields
     */
    private function setParentId($Data) 
    {
        //====================================================================//
        // Decode Customer Id
        $Id = self::ObjectId_DecodeId( $Data );
        //====================================================================//
        // Check For Change
        if ( $Id == $this->Object->getParentId() ) {
            return True;
        } 
        //====================================================================//
        // Verify Object Type
        if ( self::ObjectId_DecodeType( $Data ) !== "ThirdParty" ) {
            return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__," Wrong Object Type (" . self::ObjectId_DecodeType( $Data ) . ").");
        } 
        //====================================================================//
        // Verify Object Exists
        $Customer = Mage::getModel('customer/customer')->load($Id);
        if ( $Customer->getEntityId() != $Id )   {        
            return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__," Unable to load Address Customer(" . $Id . ").");
        } 
        //====================================================================//
        // Update Link
        $this->Object->setParentId($Id);
        return True;
    }   
    
    /**
     *  @abstract     Save Object after Writting Fields
     */
    private function setSaveObject() 
    {
        //====================================================================//
        // Verify Update Is requiered
        if ( $this->update == False ) {
            Splash::Log()->Deb("MsgLocalNoUpdateReq",__CLASS__,__FUNCTION__);
            return $this->Object->getEntityId();
        }
        //====================================================================//
        // If Id Given = > Update Object
        //====================================================================//
        $this->Object->save();
        if ( $this->Object->_hasDataChanges ) {  
            return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,"Unable to update (" . $this->Object->getEntityId() . ").");
        }
        Splash::Log()->Deb("MsgLocalTpl",__CLASS__,__FUNCTION__,"Customer Address Updated");
        $this->update = False;
        return $this->Object->getEntityId();    
    }
    
}




