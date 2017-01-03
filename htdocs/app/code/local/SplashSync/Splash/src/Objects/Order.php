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
use Splash\Core\SplashCore                          as Splash;
use Splash\Local\Objects\Order\Fields;
use Splash\Local\Objects\Order\Getters;
use Splash\Local\Objects\Order\Setters;
use Mage_Sales_Model_Order                          as MageOrder;
use Mage;

/**
 * @abstract    Splash PHP Module For Magento 1 - Order Object Intégration
 * @author      B. Paquier <contact@splashsync.com>
 */
class Order extends ObjectBase
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
    protected static    $NAME            =  "Customer Order";
    
    /**
     *  Object Description (Translated by Module) 
     */
    protected static    $DESCRIPTION     =  "Magento 1 Customers Order Object";    
    
    /**
     *  Object Icon (FontAwesome or Glyph ico tag) 
     */
    protected static    $ICO     =  "fa fa-shopping-cart ";
    
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

    const               SHIPPING_LABEL             =   "__Shipping";       
    const               SPLASH_LABEL               =   "__Splash__";        
    
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
        //  Load Local SubClass
//        require_once "Order/OrderFields.php"; 
        $SubClass  =   new Fields();
        //====================================================================//
        //  Forward SubClass Action
        return $SubClass->Fields();  
    }
    
    /**
    *   @abstract     Return List Of Customer with required filters
    *   @param        array   $filter          Filters for Customers List. 
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
        
	/* Get Object Model Collection */
	$Collection = Mage::getModel('sales/order')
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
        $sortfield = empty($params["sortfield"])?"created_at":$params["sortfield"];
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
        foreach ($Collection->getItems() as $key => $Order)
        {
            $Data[$key]["id"]           = $Order->getEntityId();
            $Data[$key]["increment_id"] = $Order->getIncrementId();
            $Data[$key]["created_at"]   = $Order->getCreatedAt();
            $Data[$key]["grand_total"]  = $Order->getGrandTotal() . Mage::app()->getStore()->getCurrentCurrencyCode();
            $Data[$key]["state"]        = $this->getStandardOrderState($Order->getState());
            
        }
        //====================================================================//
        // Prepare List result meta infos
        $Data["meta"]["current"]    =   count($Data);  // Store Current Number of results
        $Data["meta"]["total"]      =   $total;  // Store Total Number of results
        Splash::Log()->Deb("MsgLocalTpl",__CLASS__,__FUNCTION__,(count($Data)-1)." Orders Found.");
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
        //  Load Local SubClass
        $SubClass  =   new Getters();
        //====================================================================//
        //  Forward SubClass Action
        return $SubClass->Get($id,$list);   
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
        //  Load Local SubClass
        $SubClass  =   new Setters();
        //====================================================================//
        //  Forward SubClass Action
        return $SubClass->Set($id,$list);      
    }       

    /**
    *   @abstract   Delete requested Object
    *   @param      int         $id             Object Id.  If NULL, Object needs to be created.
    *   @return     int                         0 if KO, >0 if OK 
    */    
    public function Delete($Id=NULL)
    {
        //====================================================================//
        // Stack Trace
        Splash::Log()->Trace(__CLASS__,__FUNCTION__);  
        //====================================================================//
        // Execute Generic Magento Delete Function ...
        return Splash::Local()->ObjectDelete('sales/order',$Id);      
    }       

    //====================================================================//
    // Class Tooling Functions
    //====================================================================//

    /**
     *   @abstract   Get Standardized Order Status
     * 
     *   @return     bool 
     */
    public static function getStandardOrderState($State) {
        //====================================================================//
        // Generate Schema.org orderStatus From Order State
        switch ($State)
        {
            case MageOrder::STATE_NEW:
            case MageOrder::STATE_PENDING_PAYMENT:
                return "OrderPaymentDue";
            case MageOrder::STATE_PROCESSING:
                return "OrderProcessing";
            case MageOrder::STATE_COMPLETE:
                return "OrderDelivered";
            case MageOrder::STATE_CLOSED:
                return "OrderReturned";
            case MageOrder::STATE_CANCELED:
                return "OrderCancelled";
            case MageOrder::STATE_HOLDED:
                return "OrderProblem";
            case MageOrder::STATE_PAYMENT_REVIEW:
                return "OrderProblem";
        }
        return "OrderDelivered";
    }     
    
}

?>
