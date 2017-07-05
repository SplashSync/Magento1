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

namespace   Splash\Local\Objects\Order;

use Splash\Models\ObjectBase;
use Splash\Core\SplashCore                          as Splash;
use Splash\Local\Objects\Order;

use Mage;
use Mage_Sales_Model_Order                          as MageOrder;

/**
 * @abstract    Splash PHP Module For Magento 1 - Order Object Intégration SubClass
 * @author      B. Paquier <contact@splashsync.com>
 */
class Setters extends ObjectBase
{
    
    //====================================================================//
    // General Class Variables	
    //====================================================================//

    private             $UpdateTotals               =   Null;       // Require order Totals Update
    
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
        //====================================================================//
        // Init Object
        if ( !$this->setInitObject($id) ) {
            return False;
        }        

        //====================================================================//
        // Iterate All Requested Fields
        //====================================================================//
        $Fields = is_a($this->In, "ArrayObject") ? $this->In->getArrayCopy() : $this->In;        
        foreach ($Fields as $FieldName => $Data) {
            //====================================================================//
            // Write Requested Fields
            $this->setCoreFields($FieldName,$Data);
            $this->setMainFields($FieldName,$Data);
//            $this->setOrderLineFields($FieldName,$Data);
//            $this->setMetaFields($FieldName,$Data);
        }
        //====================================================================//
        // Create/Update Object if Requiered
        $OrderId = $this->setSaveObject();        
        //====================================================================//
        // Iterate All Requested Fields
        //====================================================================//
        foreach ($Fields as $FieldName => $Data) {
            //====================================================================//
            // Write Requested Fields
            $this->setAddressFields($FieldName,$Data);
            $this->setProducts($FieldName,$Data);
        }
        
        //================================================rn Order Id====================//
        // Verify Requested Fields List is now Empty => All Fields Read Successfully
        if ( count($this->In) ) {
            foreach ($this->In as $FieldName => $Data) {
                Splash::Log()->Err("ErrLocalWrongField",__CLASS__,__FUNCTION__, $FieldName);
            }
            return False;
        }        
        
        //====================================================================//
        // Update Order Totals if Needed
        $this->setUpdateTotals();
        
        //====================================================================//
        // Update Object if Requiered
        if ( $this->update ) {
            return $this->setSaveObject();        
        } 
        
        return $OrderId;        
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
    // Fields Writting Functions
    //====================================================================//
      
    /**
     *  @abstract     Init Object vefore Writting Fields
     * 
     *  @param        array   $id               Object Id. If NULL, Object needs t be created.
     * 
     */
    private function setInitObject($id) 
    {
        //====================================================================//
        // If $id Given => Load Object From DataBase
        //====================================================================//
        if ( !empty($id) )
        {
            $this->Object = Mage::getModel('sales/order')->load($id);
            if ( $this->Object->getEntityId() != $id )   {
                return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__," Unable to load Customer Order (" . $id . ").");
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
            if ( empty($this->In["customer_id"]) ) {
                return Splash::Log()->Err("ErrLocalFieldMissing",__CLASS__,__FUNCTION__,"customer_id");
            }
            //====================================================================//
            // Create Empty Customer Order
            $this->Object = Mage::getModel('sales/order');
            //====================================================================//
            // Setup Order External Id to Know this is a Splash Created Order
            $this->Object->setExtOrderId(Order::SPLASH_LABEL);
            // Set Is Virtual Order => No Billing or Shipping Address
            $this->Object->setIsVirtual(True);
            // Set Default Payment Method
            $this->Object->setData('payment', array('method'    => 'checkmo'));
            // Set Sales Order Payment
            $this->Object->setPayment(Mage::getModel('sales/order_payment')->setMethod('checkmo'));
            // Set Order Initial Status
            $this->Object->setState(MageOrder::STATE_NEW, "pending", 'Just Created by SplashSync Module',True);
            
            //====================================================================//
            // Set Currency To Default Store Values
            $DefaultCurrency = Mage::app()->getStore()->getBaseCurrencyCode();
            $this->Object->setGlobalCurrencyCode($DefaultCurrency);
            $this->Object->setOrderCurrencyCode($DefaultCurrency);
            $this->Object->setBaseCurrencyCode($DefaultCurrency);
            $this->Object->setBaseToOrderRate(1);
        }
        
        return True;
    }
        


    

    
    /**
     *  @abstract     Save Object after Writting Fields
     */
    private function setSaveObject() 
    {
        //====================================================================//
        // Do Generic Magento Object Save & Verify Update was Ok
        $Save = Splash::Local()->ObjectSave($this->Object, $this->update , "Customer Order");
        if ( $Save !== False ) {
            Splash::Object("Order")->Lock($Save);
            $this->update = False;
        }
        return $Save;
    }    
    
    //====================================================================//
    // Class Tooling Functions
    //====================================================================//

    
}

?>
