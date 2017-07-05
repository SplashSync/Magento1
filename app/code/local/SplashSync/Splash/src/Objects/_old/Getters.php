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
use Mage_Sales_Model_Order_Invoice                  as MageInvoice;
use Mage_Sales_Model_Order_Payment_Transaction      as Transaction;

/**
 * @abstract    Splash PHP Module For Magento 1 - Order Object Intégration SubClass
 * @author      B. Paquier <contact@splashsync.com>
 */
class Getters extends ObjectBase
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
        //====================================================================//
        // Place Here Any SPECIFIC Initialisation Code
        //====================================================================//
        return True;
    }    
    
    //====================================================================//
    // Class Main Functions
    //====================================================================//
    
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
        $this->Object = Mage::getModel('sales/order')->load($id);
        if ( $this->Object->getEntityId() != $id )   {
            return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__," Unable to load Customer Order (" . $id . ").");
        }
//        $this->Products = $this->Object->getProducts();
        //====================================================================//
        // Init Response Array 
        $this->Out  =   array( "id" => $id );
        //====================================================================//
        // Run Through All Requested Fields
        //====================================================================//
        $Fields = is_a($this->In, "ArrayObject") ? $this->In->getArrayCopy() : $this->In;        
        foreach ($Fields as $Key => $FieldName) {
            //====================================================================//
            // Read Requested Fields            
            $this->getCoreFields($Key,$FieldName);
            $this->getMainFields($Key,$FieldName);
            $this->getAddressFields($Key,$FieldName);
            $this->getProductsLineFields($Key,$FieldName);
            $this->getShippingLineFields($Key,$FieldName);
            $this->getMetaFields($Key, $FieldName);
//            $this->getPostCreateFields($Key, $FieldName);
        }        
        //====================================================================//
        // Verify Requested Fields List is now Empty => All Fields Read Successfully
        if ( count($this->In) ) {
            foreach ($this->In as $FieldName) {
                Splash::Log()->War("ErrLocalWrongField",__CLASS__,__FUNCTION__, $FieldName);
            }
            return False;
        }        
        //====================================================================//
        // Return Data
        //====================================================================//
        Splash::Log()->Deb("MsgLocalTpl",__CLASS__,__FUNCTION__," DATA : " . print_r($this->Out,1));
        return $this->Out; 
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
    private function getShippingLineFields($Key,$FieldName)
    {
        
        //====================================================================//
        // Check List Name
        if (self::ListField_DecodeListName($FieldName) !== "lines") {
            return True;
        }
        if ($this->Object->getShippingAmount() <= 0 ) {
            return True;
        }
        //====================================================================//
        // Decode Field Name
        $ListFieldName = $this->List_InitOutput("lines",$FieldName);        
        
        //====================================================================//
        // READ Fields
        switch ($ListFieldName)
        {
            //====================================================================//
            // Order Line Direct Reading Data          
            case 'sku':
                $Value = Order::SHIPPING_LABEL;
                break;                
            //====================================================================//
            // Order Line Direct Reading Data          
            case 'name':
                $Value = $this->Object->getShippingDescription();
                break;                
            case 'qty_ordered':
                $Value = 1;
                break;                
            case 'discount_percent':
                $Value = 0;
                break;
            //====================================================================//
            // Order Line Product Id
            case 'product_id':
                $Value = Null;
                break;
            //====================================================================//
            // Order Line Unit Price
            case 'unit_price':
                //====================================================================//
                // Read Current Currency Code
                $CurrencyCode   =   $this->Object->getOrderCurrencyCode();
                $ShipAmount     =   $this->Object->getShippingAmount();
                //====================================================================//
                // Compute Shipping Tax Percent
                if ($ShipAmount > 0) {
                    $ShipTaxPercent =  100 * $this->Object->getShippingTaxAmount() / $ShipAmount;
                } else {
                    $ShipTaxPercent =  0;
                }
                    $Value = self::Price_Encode(
                            (double)    $ShipAmount,
                            (double)    $ShipTaxPercent,
                                        Null,
                                        $CurrencyCode,
                                        Mage::app()->getLocale()->currency($CurrencyCode)->getSymbol(),
                                        Mage::app()->getLocale()->currency($CurrencyCode)->getName()); 
                break;
            default:
                return;
        }
        
        //====================================================================//
        // Create Line Array If Needed
        $Index = count($this->Object->getAllItems());
        //====================================================================//
        // Do Fill List with Data
        $this->List_Insert("lines",$FieldName,$Index,$Value);
    }
    

    

}

?>
