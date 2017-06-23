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
use Mage_Catalog_Model_Product_Status;
use Mage_Catalog_Model_Product_Type;
use DateTime;

/**
 * @abstract    Splash PHP Module For Magento 1 - Product Object Intégration
 * @author      B. Paquier <contact@splashsync.com>
 */
class Product extends ObjectBase
{
    //====================================================================//
    // Object Definition Parameters	
    //====================================================================//
    
    /**
     *  Object Disable Flag. Uncomment thius line to Override this flag and disable Object.
     */
    protected static    $DISABLED        =  True;
    
    /**
     *  Object Name (Translated by Module)
     */
    protected static    $NAME            =  "Product";
    
    /**
     *  Object Description (Translated by Module) 
     */
    protected static    $DESCRIPTION     =  "Magento 1 Product Object";    
    
    /**
     *  Object Icon (FontAwesome or Glyph ico tag) 
     */
    protected static    $ICO     =  "fa fa-product-hunt";
    
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
    protected static    $ENABLE_PUSH_CREATED       =  FALSE;        // Enable Creation Of New Local Objects when Not Existing
    protected static    $ENABLE_PUSH_UPDATED       =  TRUE;         // Enable Update Of Existing Local Objects when Modified Remotly
    protected static    $ENABLE_PUSH_DELETED       =  TRUE;         // Enable Delete Of Existing Local Objects when Deleted Remotly

    protected static    $ENABLE_PULL_CREATED       =  TRUE;         // Enable Import Of New Local Objects 
    protected static    $ENABLE_PULL_UPDATED       =  TRUE;         // Enable Import of Updates of Local Objects when Modified Localy
    protected static    $ENABLE_PULL_DELETED       =  TRUE;         // Enable Delete Of Remotes Objects when Deleted Localy       
    
    
    //====================================================================//
    // General Class Variables	
    //====================================================================//
    private $ProductId      = Null;     // Magento Product Class Id
//    private $Attribute      = Null;     // Prestashop Product Attribute Class
    private $AttributeId    = Null;     // Magento Product Attribute Class Id
//    private $AttributeUpdate= False;    // Prestashop Product Attribute Update is Requierd
//    private $LangId         = Null;     // Prestashop Language Class Id
//    private $Currency       = Null;     // Prestashop Currency Class
    
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
    *   @return       array   $data             List of all customers available data
    *                                           All data must match with OSWS Data Types
    *                                           Use OsWs_Data::Define to create data instances
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
        // PRODUCT DESCRIPTIONS
        //====================================================================//
        $this->buildDescFields();
        //====================================================================//
        // MAIN INFORMATIONS
        //====================================================================//
        $this->buildMainFields();
        //====================================================================//
        // STOCK INFORMATIONS
        //====================================================================//
        $this->buildStockFields();
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
     * 
    *   @param        string  $filter                   Filters/Search String for Contact List. 
    *   @param        array   $params                   Search parameters for result List. 
    *                         $params["max"]            Maximum Number of results 
    *                         $params["offset"]         List Start Offset 
    *                         $params["sortfield"]      Field name for sort list (Available fields listed below)    
    *                         $params["sortorder"]      List Order Constraign (Default = ASC)    
     * 
    *   @return       array   $data                     List of all customers main data
    *                         $data["meta"]["total"]     ==> Total Number of results
    *                         $data["meta"]["current"]   ==> Total Number of results
    */
    public function ObjectsList($filter=NULL,$params=NULL)
    {
        //====================================================================//
        // Stack Trace
        Splash::Log()->Trace(__CLASS__,__FUNCTION__);             
	/* Get customer model, run a query */
	$Collection = Mage::getModel('catalog/product')
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
        $sortfield = empty($params["sortfield"])?"sku":$params["sortfield"];
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
//            $Data[$key]         = $Customer->toArray();
            $Data[$key]["id"]       = $Customer->getEntityId();
            $Data[$key]["sku"]      = $Customer->getSku();
            $Data[$key]["name"]     = $Customer->getName();
            $Data[$key]["status"]   = $Customer->getStatus();
            $Data[$key]["price"]    = $Customer->getPrice();
            
            $Data[$key]["qty"]      = $Customer->getStockItem()->getQty();
            
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
    *   @param        array   $UnikId           Product Unik Id. (Combo of Product Id & Attribute Id) 
    *   @param        array   $List             List of requested fields    
    */
    public function Get($UnikId=NULL,$List=0)
    {
        //====================================================================//
        // Stack Trace
        Splash::Log()->Trace(__CLASS__,__FUNCTION__);  
        //====================================================================//
        // Init Reading
        $this->In = $List;
        //====================================================================//
        // Decode Product Id
        $this->ProductId        = self::getId($UnikId);
        $this->AttributeId      = self::getAttribute($UnikId);
        //====================================================================//
        // Safety Checks 
        if (empty ($UnikId)  || empty($this->ProductId)) {
            return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__," Missing Id.");
        }
        //====================================================================//
        // If $id Given => Load Product Object From DataBase
        //====================================================================//
        if ( !empty($this->ProductId) ) {
            //====================================================================//
            // Init Object 
            $this->Object = Mage::getModel('catalog/product')->load($this->ProductId);
            if ( $this->Object->getEntityId() != $this->ProductId )   {
                return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,"Unable to fetch Product (" . $this->ProductId . ")");
            }
        }
        //====================================================================//
        // If $id_attribute Given => Load Product Attribute Combinaisons From DataBase
        //====================================================================//
//        if ( !empty($this->AttributeId) ) {
//            $this->Attribute = new Combination($this->AttributeId);
//            if ($this->Attribute->id != $this->AttributeId ) {
//                return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,"Unable to fetch Product Attribute (" . $this->AttributeId . ")");
//            }
//            $this->Object->id_product_attribute = $this->AttributeId;
//        }

        //====================================================================//
        // Init Response Array 
        $this->Out  =   array( "id" => $UnikId );
        
        //====================================================================//
        // Run Through All Requested Fields
        //====================================================================//
        $Fields = is_a($this->In, "ArrayObject") ? $this->In->getArrayCopy() : $this->In;        
        foreach ($Fields as $Key => $FieldName) {
            //====================================================================//
            // Read Requested Fields            
            $this->getCoreFields($Key,$FieldName);
            $this->getDescFields($Key,$FieldName);
            $this->getMainFields($Key,$FieldName);
            $this->getStockFields($Key, $FieldName);
            $this->getMetaFields($Key, $FieldName);
//        Splash::Log()->War("MsgLocalTpl",__CLASS__,__FUNCTION__," DATA : " . print_r($this->Out,1));
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
//        Splash::Log()->War("MsgLocalTpl",__CLASS__,__FUNCTION__," DATA : " . print_r($this->Out,1));
        return $this->Out; 
    }
        
    /**
    *   @abstract     Write or Create requested Object Data
    *   @param        array   $UnikId           Object Id.  If NULL, Object needs t be created.
    *   @param        array   $List             List of requested fields    
    *   @return       string  $id               Object Id.  If NULL, Object wasn't created.    
    */
    public function Set($UnikId=NULL,$List=NULL)
    {
        //====================================================================//
        // Stack Trace
        Splash::Log()->Trace(__CLASS__,__FUNCTION__);
        
        //====================================================================//
        // Load User
        if ( !Splash::Local()->LoadLocalUser() )     { 
            return False;
        }
        
        //====================================================================//
        // Init Reading
        $this->In           =   $List;
        //====================================================================//
        // Init Object
        if ( !$this->setInitObject($UnikId) ) {
            return False;
        }        

        //====================================================================//
        // Run Through All Requested Fields
        //====================================================================//
        $Fields = is_a($this->In, "ArrayObject") ? $this->In->getArrayCopy() : $this->In;        
        foreach ($Fields as $FieldName => $Data) {
            //====================================================================//
            // Write Requested Fields
            $this->setCoreFields($FieldName,$Data);
            $this->setDescFields($FieldName,$Data);
            $this->setMetaFields($FieldName,$Data);
            $this->setMainFields($FieldName,$Data);
        }
        //====================================================================//
        // Create/Update Object Main Fields
        $this->setSaveObject();  
        //====================================================================//
        // Create/Update Object Images
//        $this->setImageGallery();
        //====================================================================//
        // Create/Update Object Stock Item Fields
        $this->setStockFields();
        //====================================================================//
        // Verify Requested Fields List is now Empty => All Fields Read Successfully
        if ( count($this->In) ) {
            foreach ($this->In as $FieldName => $Data) {
                Splash::Log()->Err("ErrLocalWrongField",__CLASS__,__FUNCTION__, $FieldName);
            }
            return False;
        }        
        return $this->ProductId;
    }       

    /**
    *   @abstract   Delete requested Object
    *   @param      int         $UnikId         Object Id.  If NULL, Object needs to be created.
    *   @return     int                         0 if KO, >0 if OK 
    */    
    public function Delete($UnikId=NULL)
    {
        //====================================================================//
        // Stack Trace
        Splash::Log()->Trace(__CLASS__,__FUNCTION__);  
        //====================================================================//
        // Decode Product Id
        if ( !empty($UnikId)) {
            $this->ProductId    = $this->getId($UnikId);
            $this->AttributeId  = $this->getAttribute($UnikId);
        } else {
            return Splash::Log()->Err("ErrSchWrongObjectId",__FUNCTION__);
        }        
        //====================================================================//
        // If Attribute Defined => Delete Combination From DataBase
//        if ( $this->AttributeId ) {
//                return $this->Object->deleteAttributeCombination($this->AttributeId);
//        }
        //====================================================================//
        // Execute Generic Magento Delete Function ...
        return Splash::Local()->ObjectDelete('catalog/product',$this->ProductId);      
    }       

    //====================================================================//
    // Fields Generation Functions
    //====================================================================//

    /**
    *   @abstract     Build Core Fields using FieldFactory
    */
    private function buildCoreFields()   {
        //====================================================================//
        // Reference
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("sku")
                ->Name('Reference - SKU')
                ->IsListed()
                ->MicroData("http://schema.org/Product","model")
                ->isRequired();
        
        //====================================================================//
        // Product Type Id
        $this->FieldsFactory()->Create(SPL_T_INT)
                ->Identifier("type_id")
                ->Name('Type Identifier')
                ->Description('Product Type Identifier')
                ->MicroData("http://schema.org/Product","type")
                ->ReadOnly();
        
    }    

    /**
    *   @abstract     Build Description Fields using FieldFactory
    */
    private function buildDescFields()   {
        
        //====================================================================//
        // PRODUCT DESCRIPTIONS
        //====================================================================//

        //====================================================================//
        // Name without Options
        $this->FieldsFactory()->Create(SPL_T_MVARCHAR)
                ->Identifier("name")
                ->Name("Product Name without Options")
                ->Group("Description")
                ->IsListed()
                ->MicroData("http://schema.org/Product","alternateName")
                ->isRequired();

//        //====================================================================//
//        // Name with Options
//        $this->FieldsFactory()->Create(SPL_T_MVARCHAR)
//                ->Identifier("name")
//                ->Name("Product Name with Options")
//                ->ReadOnly()
//                ->IsListed()
//                ->MicroData("http://schema.org/Product","name");

        //====================================================================//
        // Long Description
        $this->FieldsFactory()->Create(SPL_T_MTEXT)
                ->Identifier("description")
                ->Name("Description")
                ->Group("Description")
                ->MicroData("http://schema.org/Article","articleBody");
        
        //====================================================================//
        // Short Description
        $this->FieldsFactory()->Create(SPL_T_MVARCHAR)
                ->Identifier("short_description")
                ->Name("Short Description")
                ->Group("Description")
                ->MicroData("http://schema.org/Product","description");

        //====================================================================//
        // Meta Description
        $this->FieldsFactory()->Create(SPL_T_MVARCHAR)
                ->Identifier("meta_description")
                ->Name("SEO" . " " . "Meta description")
                ->Group("SEO")
                ->MicroData("http://schema.org/Article","headline");

        //====================================================================//
        // Meta Title
        $this->FieldsFactory()->Create(SPL_T_MVARCHAR)
                ->Identifier("meta_title")
                ->Name("SEO" . " " . "Meta title")
                ->Group("SEO")
                ->MicroData("http://schema.org/Article","name");
        
//        //====================================================================//
//        // Meta KeyWords
//        $this->FieldsFactory()->Create(SPL_T_MVARCHAR)
//                ->Identifier("meta_keywords")
//                ->Name("SEO" . " " . "Meta keywords")
//                ->Group("SEO")
//                ->MicroData("http://schema.org/Article","keywords")
//                ->ReadOnly();

        //====================================================================//
        // Url Path
        $this->FieldsFactory()->Create(SPL_T_MVARCHAR)
                ->Identifier("url_key")
                ->Name("SEO" . " " . "Friendly URL")
                ->Group("SEO")
                ->MicroData("http://schema.org/Product","urlRewrite");
        
    }    

    /**
    *   @abstract     Build Address Fields using FieldFactory
    */
    private function buildMainFields() {
        //====================================================================//
        // PRODUCT SPECIFICATIONS
        //====================================================================//

        //====================================================================//
        // Weight
        $this->FieldsFactory()->Create(SPL_T_DOUBLE)
                ->Identifier("weight")
                ->Name("Weight")
                ->MicroData("http://schema.org/Product","weight");
        
        //====================================================================//
        // PRICES INFORMATIONS
        //====================================================================//
        
        //====================================================================//
        // Product Selling Price
        $this->FieldsFactory()->Create(SPL_T_PRICE)
                ->Identifier("price")
                ->Name("Selling Price HT" . " (" . Mage::app()->getStore()->getCurrentCurrencyCode() . ")")
                ->MicroData("http://schema.org/Product","price")
                ->isListed();
        
        //====================================================================//
        // WholeSale Price
//        $this->FieldsFactory()->Create(SPL_T_PRICE)
//                ->Identifier("price-wholesale")
//                ->Name($this->spl->l("Supplier Price") . " (" . $this->Currency->sign . ")")
//                ->MicroData("http://schema.org/Product","wholesalePrice");
        
        //====================================================================//
        // PRODUCT IMAGES
        //====================================================================//
        
        //====================================================================//
        // Product Cover Image Position
//        $this->FieldsFactory()->Create(SPL_T_INT)
//                ->Identifier("cover_image")
//                ->Name($this->spl->l("Cover"))
//                ->MicroData("http://schema.org/Product","coverImage")
//                ->NotTested();
        
        //====================================================================//
        // Product Images List
        $this->FieldsFactory()->Create(SPL_T_IMG)
                ->Identifier("image")
                ->InList("images")
                ->Name("Images")
                ->Group("Description")
                ->MicroData("http://schema.org/Product","image");
        
        return;
    }

    /**
    *   @abstract     Build Fields using FieldFactory
    */
    private function buildStockFields() {
        
        //====================================================================//
        // PRODUCT STOCKS
        //====================================================================//
        
        //====================================================================//
        // Stock Reel
        $this->FieldsFactory()->Create(SPL_T_INT)
                ->Identifier("qty")
                ->Name("Stock")
                ->Group("Stocks")
                ->MicroData("http://schema.org/Offer","inventoryLevel")
                ->isListed();

        //====================================================================//
        // Out of Stock Flag
        $this->FieldsFactory()->Create(SPL_T_BOOL)
                ->Identifier("outofstock")
                ->Name('Out of stock')
                ->Group("Stocks")
                ->MicroData("http://schema.org/ItemAvailability","OutOfStock")
                ->ReadOnly();
                
        //====================================================================//
        // Minimum Order Quantity
        $this->FieldsFactory()->Create(SPL_T_INT)
                ->Identifier("min_sale_qty")
                ->Name('Min. Order Quantity')
                ->Group("Stocks")
                ->MicroData("http://schema.org/Offer","eligibleTransactionVolume");
        
        return;
    }
    
    /**
    *   @abstract     Build Meta Fields using FieldFactory
    */
    private function buildMetaFields() {
        
        //====================================================================//
        // SPLASH RESERVED INFORMATIONS
        //====================================================================//

        //====================================================================//
        // Splash Unique Object Id
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("splash_id")
                ->Name("Splash Id")
                ->Group("Meta")
                ->MicroData("http://splashync.com/schemas","ObjectId");

        //====================================================================//
        // STRUCTURAL INFORMATIONS
        //====================================================================//

        //====================================================================//
        // Active => Product Is Enables & Visible
        $this->FieldsFactory()->Create(SPL_T_BOOL)
                ->Identifier("status")
                ->Group("Meta")
                ->Name("Enabled")
                ->MicroData("http://schema.org/Product","active")        
                ->isListed();
        
        //====================================================================//
        // Active => Product Is available_for_order
        $this->FieldsFactory()->Create(SPL_T_BOOL)
                ->Identifier("available_for_order")
                ->Name("Available for order")
                ->Group("Meta")
                ->MicroData("http://schema.org/Product","offered")
                ->ReadOnly();
        
        //====================================================================//
        // On Sale 
        $this->FieldsFactory()->Create(SPL_T_BOOL)
                ->Identifier("on_special")
                ->Name("On Sale")
                ->Group("Meta")
                ->MicroData("http://schema.org/Product","onsale")
                ->ReadOnly();
        
        //====================================================================//
        // TRACEABILITY INFORMATIONS
        //====================================================================//        
        
        //====================================================================//
        // TMS - Last Change Date 
        $this->FieldsFactory()->Create(SPL_T_DATETIME)
                ->Identifier("updated_at")
                ->Name("Last Modification Date")
                ->Group("Meta")
                ->MicroData("http://schema.org/DataFeedItem","dateModified")
                ->ReadOnly();
        
        //====================================================================//
        // datec - Creation Date 
        $this->FieldsFactory()->Create(SPL_T_DATETIME)
                ->Identifier("created_at")
                ->Name("Creation Date")
                ->Group("Meta")
                ->MicroData("http://schema.org/DataFeedItem","dateCreated")
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
            // MAIN INFORMATIONS
            //====================================================================//
            case 'sku':
            case 'type_id':
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
    private function getDescFields($Key,$FieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($FieldName)
        {
            //====================================================================//
            // PRODUCT MULTILANGUAGES CONTENTS
            //====================================================================//
            case 'name':
            case 'description':
            case 'short_description':
            case 'meta_title':
            case 'meta_description':
            case 'meta_keywords':
            case 'url_key':
                $this->Out[$FieldName] = Splash::Local()->getMultilang($this->Object,$FieldName);
                break;
//            case 'fullname':
//                $this->Out[$FieldName] = $this->getMultilangFullName($this->Object,$FieldName);
//                break;
                
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
                // PRODUCT SPECIFICATIONS
                //====================================================================//
//                case 'height':
//                case 'depth':
//                case 'width':
                case 'weight':
                    $this->Out[$FieldName] = (double) $this->Object->getData($FieldName);                
                    break;
                
                //====================================================================//
                // PRICE INFORMATIONS
                //====================================================================//

                case 'price':
                    //====================================================================//
                    // Load Product Appliable Tax Rate
                    $Store              =   Mage::app()->getStore($this->Object->getStore());
                    $TaxCalculation     =   Mage::getModel('tax/calculation');
                    $TaxRequest         =   $TaxCalculation->getRateRequest(null, null, null, $Store);
                    $Tax                =   (double)  $TaxCalculation->getRate(
                            $TaxRequest->setProductClassId($this->Object->getTaxClassId())
                    );                    
                    //====================================================================//
                    // Read HT Price
                    $PriceHT    = (double)  $this->Object->getPrice();
                    //====================================================================//
                    // Read Current Currency Code
                    $CurrencyCode   =   Mage::app()->getStore()->getCurrentCurrencyCode();
                    //====================================================================//
                    // Build Price Array
                    $this->Out[$FieldName] = self::Price_Encode(
                            $PriceHT,$Tax,Null,
                            $CurrencyCode,
                            Mage::app()->getLocale()->currency($CurrencyCode)->getSymbol(),
                            Mage::app()->getLocale()->currency($CurrencyCode)->getName());
                    break;
                    
                //====================================================================//
                // PRODUCT IMAGES
                //====================================================================//
                case 'image@images':     
                    $this->getImageGallery();
                    break;
                case 'cover_image':
                    $CoverImage             = Image::getCover((int) $this->ProductId);
                    $this->Out[$FieldName]  = isset($CoverImage["position"])?$CoverImage["position"]:0;
                    break;
                
            default:
                return;
        }
        
        if (!is_null($Key)) {
            unset($this->In[$Key]);
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
    private function getStockFields($Key,$FieldName) {
        //====================================================================//
        // READ Fields
        switch ($FieldName)
        {
            //====================================================================//
            // PRODUCT STOCKS
            //====================================================================//
            // Stock Reel
            case 'qty':
            //====================================================================//
            // Minimum Order Quantity
            case 'min_sale_qty':
                $this->Out[$FieldName] = (int) $this->Object->getStockItem()->getData($FieldName);                
                break;
            //====================================================================//
            // Out Of Stock
            case 'outofstock':
                $this->Out[$FieldName] = $this->Object->getStockItem()->getIsInStock() ? False : True;
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
    private function getMetaFields($Key,$FieldName) {

        //====================================================================//
        // READ Fields
        switch ($FieldName)
        {
            //====================================================================//
            // Splash Id
            case 'splash_id':
                $this->Out[$FieldName] = $this->Object->getData($FieldName);                
                break;
            //====================================================================//
            // OTHERS INFORMATIONS
            //====================================================================//
            case 'status':
                $this->Out[$FieldName] = !$this->Object->isDisabled();                
                break;
            case 'available_for_order':
                $this->Out[$FieldName] = $this->Object->getData("status") && $this->Object->getStockItem()->getIsInStock();                
                break;
            case 'on_special':
                $Current    = new DateTime();
                $From       = Mage::getModel("core/date")->timestamp($this->Object->getData("special_from_date"));
                if ( $Current->getTimestamp() < $From ) {
                    $this->Out[$FieldName] = False;                
                    break;
                }
                $To         = Mage::getModel("core/date")->timestamp($this->Object->getData("special_to_date"));
                if ( $Current->getTimestamp() < $To ) {
                    $this->Out[$FieldName] = False;                
                    break;
                }
                $this->Out[$FieldName] = True;                
                break;
            //====================================================================//
            // TRACEABILITY INFORMATIONS
            //====================================================================//
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
     *  @abstract     Init Object before Writting Fields
     * 
     *  @param        array   $UnikId               Object Id. If NULL, Object needs t be created.
     * 
     */
    private function setInitObject($UnikId) 
    {
        
        //====================================================================//
        // Decode Product Id
        if ( !empty($UnikId)) {
            $this->ProductId    = $this->getId($UnikId);
            $this->AttributeId  = $this->getAttribute($UnikId);
        } else {
            $this->ProductId    = Null;
            $this->AttributeId  = Null;
        }
        
        //====================================================================//
        // If $id Given => Load Object From DataBase
        //====================================================================//
        if ( !empty($this->ProductId) )
        {
            $this->Object = Mage::getModel('catalog/product')->load($this->ProductId);
            if ( $this->Object->getEntityId() != $this->ProductId )   {
                return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__," Unable to load Product (" . $this->ProductId . ").");
            }
            return True;
        }
        
        //====================================================================//
        // If NO $id Given  => Verify Input Data includes minimal valid values
        //                  => Setup Standard Parameters For New Customers
        //====================================================================//
        
        //====================================================================//
        // Check Product Ref is given
        if ( empty($this->In["sku"]) ) {
            return Splash::Log()->Err("ErrLocalFieldMissing",__CLASS__,__FUNCTION__,"sku");
        }
        
        //====================================================================//
        // Init Product Class
        $this->Object = Mage::getModel('catalog/product')
                ->setStatus(Mage_Catalog_Model_Product_Status::STATUS_ENABLED);
        //====================================================================//
        // Init Product Entity
        $this->Object->setAttributeSetId(Mage::getStoreConfig('splashsync_splash_options/products/attribute_set'));
        //====================================================================//
        // Init Product Type => Always Simple when Created formOutside Magento
        $this->Object->setTypeId((Mage_Catalog_Model_Product_Type::TYPE_SIMPLE));
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
            // MAIN INFORMATIONS
            //====================================================================//
            case 'sku':
                if ( $this->Object->getData($FieldName) != $Data ) {
                    $this->Object->setData($FieldName, $Data);
                    $this->update = True;
                }  
                break;
            
            
//            case 'ref':
//                //====================================================================//
//                // Product has Attribute
//                if ( $this->AttributeId && ($this->Attribute->reference !== $Data) ) {             
//                    $this->Attribute->reference = $Data;
//                    $this->AttributeUpdate = True;
//                //====================================================================//
//                // Product has No Attribute
//                } else if ( !$this->AttributeId && ( $this->Object->reference !== $Data) ) {             
//                    $this->Object->reference = $Data;
//                    $this->update = True;
//                }                    
//                break;   
                
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
    private function setDescFields($FieldName,$Data) 
    {
        //====================================================================//
        // WRITE Field
        switch ($FieldName)
        {
            
            //====================================================================//
            // PRODUCT MULTILANGUAGES CONTENTS
            //====================================================================//
            case 'name':
            case 'description':
            case 'short_description':
            case 'meta_title':
            case 'meta_description':
            case 'meta_keywords':
            case 'url_key':
                $this->update   |=   Splash::Local()->setMultilang($this->Object,$FieldName,$Data);
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
            // PRODUCT SPECIFICATIONS
            //====================================================================//
            case 'weight':
                if ( !$this->Float_Compare($this->Object->getData($FieldName) , $Data ) ) {
                    $this->Object->setData($FieldName, $Data);
                    $this->update = True;
                }  
                break;
            //====================================================================//
            // PRICES INFORMATIONS
            //====================================================================//
            case 'price':
                //====================================================================//
                // Read Current Product Price (Via Out Buffer)
                $this->getMainFields(Null,"price");
                //====================================================================//
                // Compare Prices
                if ( $this->Price_Compare($this->Out["price"],$Data) ) {
                    break; 
                }
                //====================================================================//
                // Update HT Price if Required
                if ( !$this->Float_Compare($this->Out["price"]["ht"], $Data["ht"]) ) {
                    $this->Object->setPrice($Data["ht"]);
                    $this->update   = True;
                }
                break;    
            //====================================================================//
            // PRODUCT IMAGES
            //====================================================================//
            case 'images':
                $this->setImageGallery($Data);
                break;
            case 'cover_image':
//                //====================================================================//
//                // Read Product Images List
//                $ObjectImagesList   =   Image::getImages($this->LangId,$this->ProductId);
//                //====================================================================//
//                // Disable Wrong Images Cover
//                foreach ($ObjectImagesList as $ImageArray) {
//                    //====================================================================//
//                    // Is Cover Image but shall not
//                    if ($ImageArray["cover"] && ($ImageArray["position"] != $Data) ) {
//                        $ObjectImage = new Image($ImageArray["id_image"],  $this->LangId);
//                        $ObjectImage->cover     =   0;
//                        $this->update           =   True;
//                        $ObjectImage->update();
//                    }
//                }
//                //====================================================================//
//                // Enable New Image Cover
//                foreach ($ObjectImagesList as $ImageArray) {
//                    //====================================================================//
//                    // Is Cover Image but shall not
//                    if (!$ImageArray["cover"] && ($ImageArray["position"] == $Data) ) {
//                        $ObjectImage = new Image($ImageArray["id_image"],  $this->LangId);
//                        $ObjectImage->cover     =   1;
//                        $this->update           =   True;
//                        $ObjectImage->update();
//                    }
//                }
                break;

                
            default:
                return;
        }
        unset($this->In[$FieldName]);
    }
                
    /**
     *  @abstract     Update Product Stock Item Fields
     * 
     *  @return         none
     */
    private function setStockFields() 
    {
        //====================================================================//
        // If New PRODUCT => Reload Product to get Stcok Item 
        if ( empty($this->Object->getStockItem()) )
        {
            $this->Object = Mage::getModel('catalog/product')->load($this->ProductId);
        }
        //====================================================================//
        // Get Stcok Item 
        $StockItem      = $this->Object->getStockItem();
        if ( empty($StockItem) ) {
            return False;
        }
        //====================================================================//
        // Run Through All Requested Fields
        //====================================================================//
        $UpdateStock    = False;
        foreach ($this->In as $FieldName => $Data) {
            //====================================================================//
            // WRITE Field
            switch ($FieldName)
            {
                //====================================================================//
                // PRODUCT STOCKS
                //====================================================================//

                //====================================================================//
                // Direct Writtings
                case 'qty':
                case 'min_sale_qty':
                    if ( $StockItem->getData($FieldName) != $Data ) {
                        $StockItem->setData($FieldName, $Data);
                        $UpdateStock = True;
                    }  
                    unset($this->In[$FieldName]);
                    break;

                default:
                    break;
            }
        }
        //====================================================================//
        // UPDATE PRODUCT STOCK 
        //====================================================================//
        if ( $UpdateStock )
        {
            //====================================================================//
            // If New PRODUCT => Set Stock/Warehouse Id 
            if (!$StockItem->getStockId()) {
                $StockItem->setStockId(Mage::getStoreConfig('splashsync_splash_options/products/default_stock'));
            } else {
                $StockItem->setStockId($StockItem->getStockId());
            }
            //====================================================================//
            // Save PRODUCT Stock Item
            $StockItem->save();
            //====================================================================//
            // Verify Item Saved
            if ( $StockItem->_hasDataChanges ) {  
                return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,"Unable to update Stocks (" . $this->Object->getEntityId() . ").");
            }
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
    private function setMetaFields($FieldName,$Data) 
    {
        //====================================================================//
        // WRITE Field
        switch ($FieldName)
        {
            //====================================================================//
            // Splash Id
            case 'splash_id':
                if ( $this->Object->getData($FieldName) != $Data ) {
                    $this->Object->setData($FieldName, $Data);
                    $this->update = True;
                }  
                break;
            //====================================================================//
            // Direct Writtings
            case 'status':
                if ( $this->Object->isDisabled() && $Data ) {
                    $this->Object->setData($FieldName, Mage_Catalog_Model_Product_Status::STATUS_ENABLED);
                    $this->update = True;
                } elseif ( !$this->Object->isDisabled() && !$Data ) {
                    $this->Object->setData($FieldName, Mage_Catalog_Model_Product_Status::STATUS_DISABLED);
                    $this->update = True;
                }  
                break; 
                
            default:
                return;
        }
        unset($this->In[$FieldName]);
    }
    
    /**
     *  @abstract     Save Object after Writting Fields
     */
    private function setSaveObject() 
    {
        //====================================================================//
        // Verify Update Is requiered
        if ( !$this->update ) {
            Splash::Log()->War("MsgLocalNoUpdateReq",__CLASS__,__FUNCTION__);
            return $this->Object->getEntityId();
        }
        //====================================================================//
        // CREATE PRODUCT IF NEW
        if ( $this->update ) {
            $this->Object->save();
            if ( $this->Object->_hasDataChanges ) {  
                return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,"Unable to update (" . $this->Object->getEntityId() . ").");
            }
            //====================================================================//
            // Store New Id on SplashObject Class
            $this->ProductId    = $this->Object->getEntityId();
        }        
        $this->update = False;
        return $this->ProductId; 
    }    
    
    //====================================================================//
    // Class Tooling Functions
    //====================================================================//

// *******************************************************************//
// Product COMMON Local Functions
// *******************************************************************//

    /**
     *      @abstract       Convert id_product & id_product_attribute pair 
     *      @param          int(10)       $ProductId               Product Identifier
     *      @param          int(10)       $AttributeId     Product Combinaison Identifier
     *      @return         int(32)       $UnikId                   0 if KO, >0 if OK
     */
    public function getUnikId($ProductId = Null, $AttributeId = 0) 
    {
        if (is_null($ProductId)) {
            return $this->ProductId + ($this->AttributeId << 20);
        }
        return $ProductId + ($AttributeId << 20);
    }   
    
    /**
     *      @abstract       Revert UnikId to decode id_product
     *      @param          int(32)       $UnikId                   Product UnikId
     *      @return         int(10)       $id_product               0 if KO, >0 if OK
     */
    static public function getId($UnikId) 
    {
        return $UnikId & 0xFFFFF;
    }  
    
    /**
     *      @abstract       Revert UnikId to decode id_product_attribute
     *      @param          int(32)       $UnikId                   Product UnikId
     *      @return         int(10)       $id_product_attribute     0 if KO, >0 if OK
     */
    static public function getAttribute($UnikId) 
    {
        return $UnikId >> 20;
    } 
    
    /**
     *   @abstract     Return Product Image Array from Prestashop Object Class
     */
    public function getImageGallery() 
    {
        //====================================================================//
        // Load Object Images List
        $ObjectImagesList   =   $this->Object->getMediaGallery();
        $Media              =   $this->Object->getMediaConfig(); 
        //====================================================================//
        // Init Images List
        if ( !isset($this->Out["images"]) ) {
            $this->Out["images"] = array();
        }
        //====================================================================//
        // Images List is Empty
        if ( !isset($ObjectImagesList["images"]) || !count ($ObjectImagesList["images"]) ) {
            return True;
        }
        //====================================================================//
        // Create Images List
        foreach ($ObjectImagesList["images"] as $key => $Image) {
            //====================================================================//
            // Init Image List Item
            if ( !isset($this->Out["images"][$key]) ) {
                $this->Out["images"][$key] = array();
            }
            //====================================================================//
            // Insert Image in Output List
            $this->Out["images"][$key]["image"] = $this->Img_Encode(
                    $Image["label"]?$Image["label"]:$this->Object->getSku(),// Image Legend/Label
                    basename($Image["file"]),                               // Image File Filename
                    dirname($Media->getMediaPath($Image['file'])) . DS,     // Image Server Path (Without Filename)
                    $Media->getMediaUrl($Image['file'])                     // Image Public Url 
            );
        }
            
        return True;
    }
         
     
    /**
    *   @abstract     Update Product Image Array from Server Data
    *   @param        array   $Data             Input Image List for Update    
    */
    public function setImageGallery($Data) 
    {
        //====================================================================//
        // Safety Check
        if ( !is_array($Data) && !is_a($Data, "ArrayObject")) { 
            return False; 
        }
        //====================================================================//
        // Load Current Object Images List
        //====================================================================//

        //====================================================================//
        // Load Object Images List
        $ObjectImagesList   =   $this->Object->getMediaGallery();
        
        //====================================================================//
        // If New PRODUCT => Reload Product to get Stcok Item 
        if ( empty($ObjectImagesList) )
        {
            //====================================================================//
            // Media gallery initialization
            $this->Object->setMediaGallery (array('images'=>array (), 'values'=>array ())); 
            //====================================================================//
            // Load Object Images List
            $ObjectImagesList   =   $this->Object->getMediaGallery();
        }
        //====================================================================//
        // UPDATE IMAGES LIST
        //====================================================================//
        $this->ImgPosition = 0;
        //====================================================================//
        // Given List Is Not Empty
        foreach ($Data as $InValue) {
            if ( !isset($InValue["image"]) || empty ($InValue["image"]) ) {
                continue;
            }
            $this->ImgPosition++;
            $InImage = $InValue["image"];
            //====================================================================//
            // Search For Image In Current List
            $ImageFound = False;
            foreach ($ObjectImagesList["images"] as $key => $Image) {
                //====================================================================//
                // Compute Md5 CheckSum for this Image 
                $CheckSum = md5_file( $this->Object->getMediaConfig()->getMediaPath($Image['file']) );
                //====================================================================//
                // If CheckSum are Different => Continue
                if ( $InImage["md5"] !== $CheckSum ) {
                    continue;
                }
                //====================================================================//
                // If Positions are Different => Continue
                if ( $this->ImgPosition !== $Image['position'] ) {
                    continue;
                }
                
                $ImageFound = $Image;
                //====================================================================//
                // If Object Found, Unset from Current List
                unset ($ObjectImagesList["images"][$key]);
                break;
            }

            //====================================================================//
            // If found => Next
            if ( $ImageFound ) {
                continue;
            }
            //====================================================================//
            // If Not found, Add this object to list
            $this->addImage($InImage);
        }
        
        //====================================================================//
        // If Remaining Image List Is Not Empty => Clear Remaining Local Images
        //====================================================================//
        if ( isset($ObjectImagesList["images"]) && !empty($ObjectImagesList["images"]) ) {
            $this->cleanUpImages($ObjectImagesList["images"]);
        }
        
        return True;
    }
            
    /**
    *   @abstract     Import a Product Image from Server Data
    *   @param        array   $ImgArray             Splash Image Definition Array    
    */
    public function addImage($ImgArray) 
    {
        //====================================================================//
        // Read Image Raw Data From Splash Server
        $NewImageFile    =   Splash::File()->GetFile($ImgArray["filename"],$ImgArray["md5"]);
        //====================================================================//
        // File Imported => Write it Here
        if ( $NewImageFile == False ) {
            return False;
        }
        //====================================================================//
        // Write Image On Local Import Folder
        $Path       = Mage::getBaseDir('media') . DS . 'import' . DS ;
        Splash::File()->WriteFile($Path,$NewImageFile["filename"],$NewImageFile["md5"],$NewImageFile["raw"]); 
        //====================================================================//
        // Create Image in Product Media Gallery
        if ( file_exists($Path . $NewImageFile["filename"]) ) {
            try {
                $this->Object->addImageToMediaGallery($Path . $NewImageFile["filename"], array('image'), true, false);
                $this->update = True;
            } catch (Exception $e) {
                return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,"Unable to add image (" . $e->getMessage() . ").");
            }
        }
        return True;
    }
    
    /**
    *   @abstract   Remaining Image List Is Not Empty => Clear Remaining Local Images
    *   @param      array   $MageImgArray             Magento Product Image Gallery Array   
    */
    public function cleanUpImages($MageImgArray) 
    {
        //====================================================================//
        // Load Images Gallery Object
        $ProductAttributes = $this->Object->getTypeInstance()->getSetAttributes();
        if (!isset($ProductAttributes['media_gallery'])) {
            return True;
        }
        $ImageGallery = $ProductAttributes['media_gallery'];
        //====================================================================//
        // Iterate All Remaining Images 
        foreach ($MageImgArray as $Image) {
            //====================================================================//
            // Delete Image Object
            if ($ImageGallery->getBackend()->getImage($this->Object, $Image['file'])) {
                $ImageGallery->getBackend()->removeImage($this->Object, $Image['file']);
                $this->update = True;
            }
        }
        return True;
    }      
    
}
