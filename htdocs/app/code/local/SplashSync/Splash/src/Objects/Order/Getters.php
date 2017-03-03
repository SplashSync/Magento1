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
        foreach (clone $this->In as $Key => $FieldName) {
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
            foreach (clone $this->In as $FieldName) {
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
    private function getCoreFields($Key,$FieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($FieldName)
        {
            //====================================================================//
            // Direct Readings
            case 'increment_id':
                $this->Out[$FieldName] = $this->Object->getData($FieldName);
                break;
            //====================================================================//
            // Customer Object Id Readings
            case 'customer_id':
                $this->Out[$FieldName] = self::ObjectId_Encode( "ThirdParty" , $this->Object->getData($FieldName) );
                break;
            //====================================================================//
            // Order Official Date
            case 'created_at':
                $this->Out[$FieldName] = date( SPL_T_DATECAST, Mage::getModel("core/date")->timestamp($this->Object->getData($FieldName)));
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
        $State  =   $this->Object->getState();
        //====================================================================//
        // READ Fields
        switch ($FieldName)
        {
            //====================================================================//
            // Order Delivery Date
//            case 'date_livraison':
//                $this->Out[$FieldName] = !empty($this->Object->date_livraison)?dol_print_date($this->Object->date_livraison, '%Y-%m-%d'):Null;
//                break;            
            
            //====================================================================//
            // PRICE INFORMATIONS
            //====================================================================//
            case 'grand_total_excl_tax':
                $this->Out[$FieldName] = $this->Object->getSubtotal() + $this->Object->getShippingAmount();
                break;
            case 'grand_total':
                $this->Out[$FieldName] = $this->Object->getData($FieldName);
                break;
            
            //====================================================================//
            // ORDER STATUS
            //====================================================================//   
            case 'state':
                $this->Out[$FieldName]  = Order::getStandardOrderState($this->Object->getState());
            break;    

            case 'isCanceled':
                if ( $State === MageOrder::STATE_CANCELED ) {
                    $this->Out[$FieldName]  = True;
                } else {
                    $this->Out[$FieldName]  = False;
                }
                break;
            case 'isValidated':
                if (    $State === MageOrder::STATE_NEW 
                    ||  $State === MageOrder::STATE_PROCESSING 
                    ||  $State === MageOrder::STATE_COMPLETE 
                    ||  $State === MageOrder::STATE_CLOSED 
                    ||  $State === MageOrder::STATE_CANCELED 
                    ||  $State === MageOrder::STATE_HOLDED 
                        ) {
                    $this->Out[$FieldName]  = True;
                } else {
                    $this->Out[$FieldName]  = False;
                }
                break;
            case 'isClosed':
                if (    $State === MageOrder::STATE_COMPLETE 
                    ||  $State === MageOrder::STATE_CLOSED 
                        ) {
                    $this->Out[$FieldName]  = True;
                } else {
                    $this->Out[$FieldName]  = False;
                }
                break;            
            case 'isPaid':
                if (    $State === MageOrder::STATE_PROCESSING 
                    ||  $State === MageOrder::STATE_COMPLETE 
                    ||  $State === MageOrder::STATE_CLOSED 
                        ) {
                    $this->Out[$FieldName]  = True;
                } else {
                    $this->Out[$FieldName]  = False;
                }
                break;            
        
            //====================================================================//
            // ORDER Currency Data
            //====================================================================//        
            case 'order_currency_code':
            case 'base_to_order_rate':
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
    private function getAddressFields($Key,$FieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($FieldName)
        {
            //====================================================================//
            // Billing/Shipping Address Object Id Readings
            case 'billing_address_id':
            case 'shipping_address_id':
                if ($FieldName == "billing_address_id") {
                    $Address    =  $this->Object->getBillingAddress(); 
                } else {
                    $Address    =  $this->Object->getShippingAddress(); 
                }
                if ($Address && $Address->getCustomerAddressId() ) {
                    $this->Out[$FieldName] = self::ObjectId_Encode( "Address" , $Address->getCustomerAddressId() );
                    break;
                }
                $this->Out[$FieldName] = Null;
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
    
    /**
     *  @abstract     Read requested Field
     * 
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     * 
     *  @return         none
     */
    private function getProductsLineFields($Key,$FieldName)
    {
        //====================================================================//
        // Check List Name
        if (self::ListField_DecodeListName($FieldName) !== "lines") {
            return True;
        }
        //====================================================================//
        // Decode Field Name
        $ListFieldName = $this->List_InitOutput("lines",$FieldName);   
        //====================================================================//
        // Verify List is Not Empty
        $Products = $this->Object->getAllItems();
        if ( !is_array($Products) ) {
            return True;
        }        
        
        //====================================================================//
        // Fill List with Data
        foreach ($Products as $Index => $Product) {
            
            //====================================================================//
            // READ Fields
            switch ($ListFieldName)
            {
                //====================================================================//
                // Order Line Direct Reading Data
                case 'sku':
                case 'name':
                case 'discount_percent':
                    $Value = $Product->getData($ListFieldName);
                    break;
                case 'qty_ordered':
                    $Value = (int) $Product->getData($ListFieldName);
                    break;
                //====================================================================//
                // Order Line Product Id
                case 'product_id':
                    $Value = self::ObjectId_Encode( "Product" , $Product->getData($ListFieldName) );
                    break;
                //====================================================================//
                // Order Line Unit Price
                case 'unit_price':
                    //====================================================================//
                    // Read Current Currency Code
                    $CurrencyCode   =   $this->Object->getOrderCurrencyCode();
                    //====================================================================//
                    // Build Price Array
                    $Value = self::Price_Encode(
                            (double)    $Product->getPrice(),
                            (double)    $Product->getTaxPercent(),
                                        Null,
                                        $CurrencyCode,
                                        Mage::app()->getLocale()->currency($CurrencyCode)->getSymbol(),
                                        Mage::app()->getLocale()->currency($CurrencyCode)->getName()); 
                    break;
                default:
                    return;
            }
            //====================================================================//
            // Do Fill List with Data
            $this->List_Insert("lines",$FieldName,$Index,$Value);            
            
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
            // STRUCTURAL INFORMATIONS
            //====================================================================//

            //====================================================================//
            // TRACEABILITY INFORMATIONS
            //====================================================================//

            case 'updated_at':
                $this->Out[$FieldName] = date( SPL_T_DATETIMECAST, Mage::getModel("core/date")->timestamp($this->Object->getData($FieldName)));
                break;
                    
            default:
                return;
        }
        
        unset($this->In[$Key]);
    }    
}

?>
