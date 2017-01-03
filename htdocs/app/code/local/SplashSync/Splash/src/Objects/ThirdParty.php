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
use Mage_Newsletter_Model_Subscriber;

/**
 * @abstract    Splash PHP Module For Magento 1 - ThirdParty Object Intégration
 * @author      B. Paquier <contact@splashsync.com>
 */
class ThirdParty extends ObjectBase
{
    
    //====================================================================//
    // Object Definition Parameters	
    //====================================================================//
    
    /**
     *  Object Disable Flag. Uncomment thius line to Override this flag and disable Object.
     */
//    protected static    $DISABLED        =  True;
    
    /**
     *  Object Name (Translated by Module)
     */
    protected static    $NAME            =  "ThirdParty";
    
    /**
     *  Object Description (Translated by Module) 
     */
    protected static    $DESCRIPTION     =  "Magento 1 Customer Object";    
    
    /**
     *  Object Icon (FontAwesome or Glyph ico tag) 
     */
    protected static    $ICO     =  "fa fa-user";

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
    protected static    $ENABLE_PUSH_CREATED       =  TRUE;         // Enable Creation Of New Local Objects when Not Existing
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
        // PRIMARY ADDRESS
        //====================================================================//
//        $this->buildPrimaryAddressFields();
        //====================================================================//
        // META INFORMATIONS
        //====================================================================//
        $this->buildMetaFields();
        //====================================================================//
        // Publish Fields
        return $this->FieldsFactory()->Publish();
    }
    
    /**
    *   @abstract     Return List Of Objects with required filters
    *   @param        array   $filter               Filters for Customers List. 
    *   @param        array   $params              Search parameters for result List. 
    *                         $params["max"]       Maximum Number of results 
    *                         $params["offset"]    List Start Offset 
    *                         $params["sortfield"] Field name for sort list (Available fields listed below)    
    *                         $params["sortorder"] List Order Constraign (Default = ASC)    
    *   @return       array   $data             List of all customers main data
    *                         $data["meta"]["total"]     ==> Total Number of results
    *                         $data["meta"]["current"]   ==> Total Number of results
    */
    public function ObjectsList($filter=NULL,$params=NULL)
    {
        //====================================================================//
        // Stack Trace
        Splash::Log()->Trace(__CLASS__,__FUNCTION__);             
	/* Get customer model, run a query */
	$Collection = Mage::getModel('customer/customer')
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
        $sortfield = empty($params["sortfield"])?"lastname":$params["sortfield"];
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
        foreach ($Collection->getItems() as $key => $Customer)
        {
            $Data[$key]         = $Customer->toArray();
            $Data[$key]["id"]   = $Data[$key]["entity_id"];
        }
        //====================================================================//
        // Prepare List result meta infos
        $Data["meta"]["current"]    =   count($Data);  // Store Current Number of results
        $Data["meta"]["total"]      =   $total;  // Store Total Number of results
        Splash::Log()->Deb("MsgLocalTpl",__CLASS__,__FUNCTION__,(count($Data)-1)." Customers Found.");
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
        $this->Object = Mage::getModel('customer/customer')->load($id);
        if ( $this->Object->getEntityId() != $id )   {
            return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__," Unable to load Customer (" . $id . ").");
        }
        $this->Out  = array( "id" => $id);

        //====================================================================//
        // Run Through All Requested Fields
        //====================================================================//
        foreach ($this->In as $Key => $FieldName) {
            //====================================================================//
            // Read Requested Fields
            $this->getCoreFields($Key,$FieldName);
            $this->getMainFields($Key,$FieldName);
//            $this->getPrimaryAddressFields($Key,$FieldName);
            $this->getMetaFields($Key,$FieldName);            
            
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
        $this->In = $list;
        $updateMainAddress  = Null;
        $updateAddressList  = Null;        
        $this->update       = False;
//        $this->postupdate   = False;

        //====================================================================//
        // Init Object
        if ( !$this->setInitObject($id) ) {
            return False;
        }
        
        //====================================================================//
        // Run Throw All Requested Fields
        //====================================================================//
        foreach ($this->In as $FieldName => $Data) {
            //====================================================================//
            // Write Requested Fields
            $this->setCoreFields($FieldName,$Data);
            $this->setMainFields($FieldName,$Data);
            $this->setMetaFields($FieldName,$Data);
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
        return Splash::Local()->ObjectDelete('customer/customer',$id);       
    }       

    //====================================================================//
    // Fields Generation Functions
    //====================================================================//

    /**
    *   @abstract     Build Customers Core Fields using FieldFactory
    */
    private function buildCoreFields()
    {
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
        // Email
        $this->FieldsFactory()->Create(SPL_T_EMAIL)
                ->Identifier("email")
                ->Name("Email address")
                ->MicroData("http://schema.org/ContactPoint","email")
                ->Association("firstname","lastname")
                ->isRequired()
                ->isListed();        
        
    }
    
    /**
    *   @abstract     Build Customers Main Fields using FieldFactory
    */
    private function buildMainFields()
    {
        
        //====================================================================//
        // Gender Name
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("gender_name")
                ->Name("Social title")
                ->MicroData("http://schema.org/Person","honorificPrefix")
                ->ReadOnly();       

        //====================================================================//
        // Gender Type
        $desc = "Social title" . " ; 0 => Male // 1 => Female // 2 => Neutral";
        $this->FieldsFactory()->Create(SPL_T_INT)
                ->Identifier("gender")
                ->Name("Social title")
                ->MicroData("http://schema.org/Person","gender")
                ->Description($desc)
                ->AddChoice(0,    "Male")
                ->AddChoice(1,    "Femele")                
                ->NotTested();       
        
        //====================================================================//
        // Date Of Birth
        $this->FieldsFactory()->Create(SPL_T_DATE)
                ->Identifier("dob")
                ->Name("Date of birth")
                ->MicroData("http://schema.org/Person","birthDate");

        //====================================================================//
        // Company
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("company")
                ->Name("Company")
                ->MicroData("http://schema.org/Organization","legalName")
                ->ReadOnly();
        
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
        
//        //====================================================================//
//        // Address List
//        $this->FieldsFactory()->Create(self::ObjectId_Encode( "Address" , SPL_T_ID))
//                ->Identifier("address")
//                ->InList("contacts")
//                ->Name($this->spl->l("Address"))
//                ->MicroData("http://schema.org/Organization","address")
//                ->ReadOnly();
        
    }    
    
    /**
    *   @abstract     Build Customers Main Fields using FieldFactory
    */
    private function buildPrimaryAddressFields()
    {

        //====================================================================//
        // Addess
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("address1")
                ->Name($this->spl->l("Address"))
                ->MicroData("http://schema.org/PostalAddress","streetAddress")
                ->ReadOnly();

        //====================================================================//
        // Addess Complement
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("address2")
                ->Name($this->spl->l("Address"))
                ->MicroData("http://schema.org/PostalAddress","postOfficeBoxNumber")
                ->ReadOnly();
        
        //====================================================================//
        // Zip Code
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("postcode")
                ->Name($this->spl->l("Zip/Postal Code","AdminAddresses"))
                ->MicroData("http://schema.org/PostalAddress","postalCode")
                ->ReadOnly();
        
        //====================================================================//
        // City Name
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("city")
                ->Name($this->spl->l("City"))
                ->MicroData("http://schema.org/PostalAddress","addressLocality")
                ->ReadOnly();
        
        //====================================================================//
        // State Name
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("state")
                ->Name($this->spl->l("State"))
                ->MicroData("http://schema.org/PostalAddress","addressRegion")
                ->ReadOnly();
        
        //====================================================================//
        // State code
        $this->FieldsFactory()->Create(SPL_T_STATE)
                ->Identifier("id_state")
                ->Name($this->spl->l("StateCode"))
                ->MicroData("http://schema.org/PostalAddress","addressRegion")
                ->ReadOnly();
        
        //====================================================================//
        // Country Name
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("country")
                ->Name($this->spl->l("Country"))
                ->MicroData("http://schema.org/PostalAddress","addressCountry")
                ->ReadOnly();
        
        //====================================================================//
        // Country ISO Code
        $this->FieldsFactory()->Create(SPL_T_COUNTRY)
                ->Identifier("id_country")
                ->Name($this->spl->l("CountryCode"))
                ->MicroData("http://schema.org/PostalAddress","addressCountry")
                ->ReadOnly();
                
        //====================================================================//
        // Phone
        $this->FieldsFactory()->Create(SPL_T_PHONE)
                ->Identifier("phone")
                ->Name($this->spl->l("Home phone"))
                ->MicroData("http://schema.org/PostalAddress","telephone")
                ->ReadOnly();
        
        //====================================================================//
        // Mobile Phone
        $this->FieldsFactory()->Create(SPL_T_PHONE)
                ->Identifier("phone_mobile")
                ->Name($this->spl->l("Mobile phone"))
                ->MicroData("http://schema.org/Person","telephone")
                ->ReadOnly();

    }
            
    /**
    *   @abstract     Build Customers Unused Fields using FieldFactory
    */
    private function buildMetaFields()
    {
        
        //====================================================================//
        // STRUCTURAL INFORMATIONS
        //====================================================================//

        //====================================================================//
        // Active
        $this->FieldsFactory()->Create(SPL_T_BOOL)
                ->Identifier("is_active")
                ->Name("Is Enabled")
                ->MicroData("http://schema.org/Organization","active")
                ->IsListed()->ReadOnly();
        
        //====================================================================//
        // Newsletter
        $this->FieldsFactory()->Create(SPL_T_BOOL)
                ->Identifier("newsletter")
                ->Name("Newletter")
                ->MicroData("http://schema.org/Organization","newsletter");
        
        //====================================================================//
        // TRACEABILITY INFORMATIONS
        //====================================================================//

        //====================================================================//
        // Creation Date 
        $this->FieldsFactory()->Create(SPL_T_DATETIME)
                ->Identifier("created_at")
                ->Name("Registration")
                ->MicroData("http://schema.org/DataFeedItem","dateCreated")
                ->ReadOnly();
        
        //====================================================================//
        // Last Change Date 
        $this->FieldsFactory()->Create(SPL_T_DATETIME)
                ->Identifier("updated_at")
                ->Name("Last update")
                ->MicroData("http://schema.org/DataFeedItem","dateModified")
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
        // READ Field
        switch ($FieldName)
        {
            case 'lastname':
            case 'firstname':
            case 'email':
                $this->Out[$FieldName] = $this->Object->getData($FieldName);
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
            // Customer Company Overriden by User Id 
            case 'company':
                if ( !empty($this->Object->getData($FieldName)) ) {
                    $this->Out[$FieldName] = $this->Object->getData($FieldName);
                    break;
                } 
                $this->Out[$FieldName] = "Magento1("  . $this->Object->getEntityId() . ")";
                break;            
            
            //====================================================================//
            // Gender Name
            case 'gender_name':
                if (empty($this->Object->getData("gender") )) {
                    $this->Out[$FieldName] = Splash::Trans("Empty");
                    break;
                }
                if ($this->Object->getData("gender") == 2) {
                    $this->Out[$FieldName] = "Femele";                    
                } else {
                    $this->Out[$FieldName] = "Male";
                }
                break;
            //====================================================================//
            // Gender Type
            case 'gender':
                if ($this->Object->getData($FieldName) == 2) {
                    $this->Out[$FieldName] = 1;                    
                } else {
                    $this->Out[$FieldName] = 0;
                }
                break;  
                
            //====================================================================//
            // Customer Date Of Birth
            case 'dob':
                $this->Out[$FieldName] = date( SPL_T_DATECAST, Mage::getModel("core/date")->timestamp($this->Object->getData($FieldName)));
                break;

            //====================================================================//
            // Customer Extended Names
            case 'prefix':
            case 'middlename':
            case 'suffix':
                $this->Out[$FieldName] = $this->Object->getData($FieldName);
                break;
            
//            //====================================================================//
//            // Customer Address List
//            case 'address@contacts':
//                if ( !$this->getAddressList() ) {
//                   return;
//                }
//                break;   
            default:
                return;
        }
        unset($this->In[$Key]);
    }    
    
    /**
     *  @abstract     Read requested Field
     * 
     *  @return         bool
     */
    private function getAddressList() {
        
        //====================================================================//
        // Create List If Not Existing
        if (!isset($this->Out["contacts"])) {
            $this->Out["contacts"] = array();
        }

        //====================================================================//
        // Read Address List
        $AddresList = $this->Object->getAddresses(Context::getContext()->language->id);

        //====================================================================//
        // If Address List Is Empty => Null
        if (empty($AddresList)) {
            return True;
        }
                
//Splash::Log()->www("AdressList", $AddresList);

        //====================================================================//
        // Run Through Address List
        foreach ($AddresList as $Key => $Address) {
            $this->Out["contacts"][$Key] = array ( "address" => $Address->id_address);
        }
//Splash::Log()->www("AdressList", $this->Out["contacts"]);
                
        return True;
    }    
    
    /**
     *  @abstract     Read requested Field
     * 
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     * 
     *  @return         none
     */
    private function getPrimaryAddressFields($Key,$FieldName)    
    {
        //====================================================================//
        // Identify Main Address Id
        $MainAddress = new Address( Address::getFirstCustomerAddressId($this->Object->id) );
        
        //====================================================================//
        // If Empty, Create A New One 
        if ( !$MainAddress ) {
            $MainAddress = new Address();
        }        
        
        //====================================================================//
        // READ Fields
        switch ($FieldName)
        {
            case 'address1':
            case 'address2':
            case 'postcode':
            case 'city':
            case 'country':
            case 'phone':
            case 'phone_mobile':
                //====================================================================//
                // READ Directly on Class
                $this->Out[$FieldName] = $MainAddress->$FieldName;
                unset($this->In[$Key]);
                break;
            case 'id_country':
                //====================================================================//
                // READ With Convertion
                $this->Out[$FieldName] = Country::getIsoById($MainAddress->id_country);
                unset($this->In[$Key]);
                break;
            case 'state':
                //====================================================================//
                // READ With Convertion
                $state = new State($MainAddress->id_state);
                $this->Out[$FieldName] = $state->name;
                unset($this->In[$Key]);
                break;
            case 'id_state':
                //====================================================================//
                // READ With Convertion
                $state = new State($MainAddress->id_state);
                $this->Out[$FieldName] = $state->iso_code;
                unset($this->In[$Key]);
                break;
        }
    }
    
    /**
     *  @abstract     Read requested Field
     * 
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     * 
     *  @return         none
     */
    private function getMetaFields($Key,$FieldName)
    {
            
        //====================================================================//
        // READ Fields
        switch ($FieldName)
        {
            case 'is_active':
                $this->Out[$FieldName] = $this->Object->getData($FieldName);
                break;
            case 'newsletter':
                $this->Out[$FieldName] = Mage::getModel('newsletter/subscriber')->loadByCustomer($this->Object)->isSubscribed();
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
            $this->Object = Mage::getModel('customer/customer')->load($id);
            if ( $this->Object->getEntityId() != $id )   {
                return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__," Unable to load Customer (" . $id . ").");
            }            
        }
        //====================================================================//
        // If NO $id Given  => Verify Input Data includes minimal valid values
        //                  => Setup Standard Parameters For New Customers
        //====================================================================//
        else
        {
            //====================================================================//
            // Check Customer Name is given
            if ( empty($this->In["firstname"]) ) {
                return Splash::Log()->Err("ErrLocalFieldMissing",__CLASS__,__FUNCTION__,"firstname");
            }
            if ( empty($this->In["lastname"]) ) {
                return Splash::Log()->Err("ErrLocalFieldMissing",__CLASS__,__FUNCTION__,"lastname");
            }
            if ( empty($this->In["email"]) ) {
                return Splash::Log()->Err("ErrLocalFieldMissing",__CLASS__,__FUNCTION__,"email");
            }
            //====================================================================//
            // Create Empty Customer
            $this->Object = Mage::getModel('customer/customer');
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
    private function setCoreFields($FieldName,$Data) {
        //====================================================================//
        // WRITE Fields
        switch ($FieldName)
        {
            case 'firstname':
            case 'lastname':
            case 'email':
                if ( $this->Object->getData($FieldName) != $Data ) {
                    $this->Object->setData($FieldName, $Data);
                    $this->update = True;
                }  
                unset($this->In[$FieldName]);
                break;
        }
    }
    
    /**
     *  @abstract     Write Given Fields
     * 
     *  @param        string    $FieldName              Field Identifier / Name
     *  @param        mixed     $Data                   Field Data
     * 
     *  @return         none
     */
    private function setMainFields($FieldName,$Data) {
        //====================================================================//
        // WRITE Fields
        switch ($FieldName)
        {
            //====================================================================//
            // Gender Type
            case 'gender':
                //====================================================================//
                // Convert Gender Type Value to Magento Values 
                // Splash Social title ; 0 => Male // 1 => Female // 2 => Neutral
                // Magento Social title ; 1 => Male // 2 => Female
                $Data++;
                //====================================================================//
                // Update Gender Type
                if ( $this->Object->getData($FieldName) != $Data ) {
                    $this->Object->setData($FieldName, $Data);
                    $this->update = True;
                }  
                break;                     
            //====================================================================//
            // Customer Date Of Birth
            case 'dob':
                $CurrentDob = date( SPL_T_DATECAST, Mage::getModel("core/date")->timestamp($this->Object->getData($FieldName)));
                if ( $CurrentDob != $Data ) {
                    $this->Object->setData($FieldName, $Data);
                    $this->update = True;
                }   
                break;
            
            //====================================================================//
            // Customer Extended Names
            case 'prefix':
            case 'middlename':
            case 'suffix':
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
     * 
     *  @param        string    $FieldName              Field Identifier / Name
     *  @param        mixed     $Data                   Field Data
     * 
     *  @return         none
     */
    private function setMetaFields($FieldName,$Data) {
        //====================================================================//
        // WRITE Fields
        switch ($FieldName)
        {
            case 'is_active':
                if ( $this->Object->getData($FieldName) != $Data ) {
                    $this->Object->setData($FieldName, $Data);
                    $this->update = True;
                }   
                break;
            case 'newsletter':
                $subscriber = Mage::getModel('newsletter/subscriber')->loadByCustomer($this->Object);
                //====================================================================//
                // Read Newsletter Status
                if ( $subscriber->isSubscribed() == $Data ) {
                    break;
                }
                //====================================================================//
                // Status Change Requiered => Subscribe
                if ( $Data ) {
                    $subscriber->setStatus(Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED);
                } else {
                    $subscriber->setStatus(Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED);
                }
                $subscriber->setSubscriberEmail($this->Object->getEmail());
                $subscriber->setSubscriberConfirmCode($subscriber->RandomSequence());
                $subscriber->setStoreId(Mage::app()->getStore()->getId());
                $subscriber->setCustomerId($this->Object->getId());        
                $subscriber->save();
                $this->update = True;
                break;
            default:
                return;
        }
        unset($this->In[$FieldName]);
    }    
    
    /**
     *  @abstract     Save Object after Writting Fields
     */
    private function setSaveObject() {
    
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
            
        Splash::Log()->Deb("MsgLocalTpl",__CLASS__,__FUNCTION__,"Customer Updated");
        $this->update = False;
        return $this->Object->getEntityId();
//        }
//        
//        //====================================================================//
//        // If NO Id Given = > Create Object
//        //====================================================================//
//            
//        //====================================================================//
//        // If NO Password Given = > Create Random Password
//        if ( empty($this->Object->passwd) ) {
//            $this->Object->passwd = Tools::passwdGen();               
//            Splash::Log()->War("MsgLocalTpl",__CLASS__,__FUNCTION__,"New Customer Password Generated - " . $this->Object->passwd );
//        }
//
//        //====================================================================//
//        // Create Object In Database
//        if ( $this->Object->add()  != True) {    
//            return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,"Unable to create. ");
//        }
//        Splash::Log()->Deb("MsgLocalTpl",__CLASS__,__FUNCTION__,"Customer Created");
//        $this->update = False;
//        return $this->Object->id;        
    }
    
}




