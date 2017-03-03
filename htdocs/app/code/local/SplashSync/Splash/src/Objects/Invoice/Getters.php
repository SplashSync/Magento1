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

namespace   Splash\Local\Objects\Invoice;

use Splash\Models\ObjectBase;
use Splash\Core\SplashCore                          as Splash;
use Splash\Local\Objects\Invoice;

use Mage;
use Mage_Sales_Model_Order_Invoice                  as MageInvoice;
use Mage_Sales_Model_Order_Payment_Transaction      as Transaction;
use Varien_Data_Collection;

/**
 * @abstract    Splash PHP Module For Magento 1 - Invoice Object Intégration SubClass
 * @author      B. Paquier <contact@splashsync.com>
 */
class Getters extends ObjectBase
{
    
    
    //====================================================================//
    // General Class Variables	
    //====================================================================//

    protected   $Order          = Null;
    protected   $Products       = Null;
    protected   $Payments       = Null;

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
        $this->Object = Mage::getModel('sales/order_invoice')->load($id);
        if ( $this->Object->getEntityId() != $id )   {
            return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__," Unable to load Customer Invoice (" . $id . ").");
        }
        //====================================================================//
        // Init Linked Objects 
        $this->Order        = $this->Object->getOrder();
        $this->Products     = $this->Object->getAllItems(); 
        $this->Payment      = $this->Order->getPayment();
        //====================================================================//
        // Init Response Array 
        $this->Out          =   array( "id" => $id );
        //====================================================================//
        // Run Through All Requested Fields
        //====================================================================//
        foreach (clone $this->In as $Key => $FieldName) {
            //====================================================================//
            // Read Requested Fields            
            $this->getCoreFields($Key,$FieldName);
            $this->getMainFields($Key,$FieldName);
            $this->getProductsLineFields($Key,$FieldName);
            $this->getShippingLineFields($Key,$FieldName);
            $this->getPaymentLineFields($Key, $FieldName);
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
//        Splash::Log()->Deb("MsgLocalTpl",__CLASS__,__FUNCTION__," DATA : " . print_r($this->Out,1));
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
            // Order Reference Number
            case 'number':
                $this->Out[$FieldName] = $this->Object->getOrderIncrementId();
                break;
            //====================================================================//
            // Customer Object Id Readings
            case 'customer_id':
                $this->Out[$FieldName] = self::ObjectId_Encode( "ThirdParty" , $this->Object->getOrder()->getData($FieldName) );
                break;
            //====================================================================//
            // Customer Name
            case 'customer_name':
                $this->Out[$FieldName] = $this->Object->getOrder()->getCustomerName();
                break;
            //====================================================================//
            // Object Object Id Readings
            case 'order_id':
                $this->Out[$FieldName] = self::ObjectId_Encode( "Order" , $this->Object->getData($FieldName) );
                break;
            //====================================================================//
            // Order Official Date
            case 'created_at':
                $this->Out[$FieldName] = date( SPL_T_DATECAST, Mage::getModel("core/date")->timestamp($this->Object->getData($FieldName)));
                break;
            case 'reference':
                $this->getSingleField($FieldName,"Order");                
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
            // INVOICE STATUS
            //====================================================================//   
            case 'state':
                if ($this->Object->isCanceled()) {
                    $this->Out[$FieldName]  = "PaymentCanceled";
                } elseif ($this->Object->getState() == MageInvoice::STATE_PAID) {
                    $this->Out[$FieldName]  = "PaymentComplete";
                } else {
                    $this->Out[$FieldName]  = "PaymentDue";
                }
            break; 
            case 'state_name':
                $this->Out[$FieldName]  = $this->Object->getStateName();
            break; 
            
            //====================================================================//
            // INVOICE PAYMENT STATUS
            //====================================================================//   
            case 'isCanceled':
                $this->Out[$FieldName]  = (bool) $this->Object->isCanceled();
                break;
            case 'isValidated':
                $this->Out[$FieldName]  = (bool) !$this->Object->isCanceled();
                break;          
            case 'isPaid':
                $this->Out[$FieldName]  = (bool) ($this->Object->getState() == MageInvoice::STATE_PAID)?True:False;
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
        // Decode Field Name
        $ListFieldName = $this->List_InitOutput("items",$FieldName);
        
        //====================================================================//
        // READ Fields
        switch ($ListFieldName)
        {
            //====================================================================//
            // Order Line Direct Reading Data          
            case 'sku':
                $Value = Invoice::SHIPPING_LABEL;
                break;                
            //====================================================================//
            // Order Line Direct Reading Data          
            case 'name':
                $Value = $this->Object->getOrder()->getShippingDescription();
                break;                
            case 'qty':
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
        // Do Fill List with Data
        $this->List_Insert("items",$FieldName,count($this->Products),$Value);
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
        // Decode Field Name
        $ListFieldName = $this->List_InitOutput("items",$FieldName);
        //====================================================================//
        // Verify List is Not Empty
        if ( !is_array($this->Products) ) {
            return True;
        }        
        
        //====================================================================//
        // Fill List with Data
        foreach ($this->Products as $Index => $Product) {
            
            //====================================================================//
            // READ Fields
            switch ($ListFieldName)
            {
                //====================================================================//
                // Invoice Line Direct Reading Data
                case 'sku':
                case 'name':
                    $Value = $Product->getData($ListFieldName);
                    break;
                case 'discount_percent':
                    if ( $Product->getPriceInclTax() && $Product->getQty() ) {
                        $Value = (double) 100 * $Product->getDiscountAmount() / ($Product->getPriceInclTax() * $Product->getQty());
                    } else {
                        $Value = 0;
                    }
                    break;
                case 'qty':
                    $Value = (int) $Product->getData($ListFieldName);
                    break;
                //====================================================================//
                // Invoice Line Product Id
                case 'product_id':
                    $Value = self::ObjectId_Encode( "Product" , $Product->getData($ListFieldName) );
                    break;
                //====================================================================//
                // Invoice Line Unit Price
                case 'unit_price':
                    //====================================================================//
                    // Read Current Currency Code
                    $CurrencyCode   =   $this->Object->getOrderCurrencyCode();
                    //====================================================================//
                    // Build Price Array
                    $Value = self::Price_Encode(
                            (double)    $Product->getPrice(),
                            (double)    $Product->getOrderItem()->getTaxPercent(),
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
            $this->List_Insert("items",$FieldName,$Index,$Value);
        }
        unset($this->In[$Key]);
    }
    
    /**
     *  @abstract     Try To Detect Payment method Standardized Name
     * 
     *  @param  OrderPayment    $OrderPayment 
     * 
     *  @return         none
     */
    private function getPaymentMethod($OrderPayment)
    {
        //====================================================================//
        // Detect Payment Metyhod Type from Default Payment "known" methods
        $Method = $OrderPayment->getMethod();
        foreach ( Invoice::$PAYMENT_METHODS as $PaymentMethod => $Ids )
        {
            if ( in_array($Method, $Ids) ) {
                return $PaymentMethod;
            }
        }
        return "free";
    }    
    
    /**
     *  @abstract     Read requested Field
     * 
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     * 
     *  @return         none
     */
    private function getPaymentLineFields($Key,$FieldName)
    {
        $Index  =   0;
        //====================================================================//
        // Decode Field Name
        $ListFieldName = $this->List_InitOutput("payments",$FieldName);
    
        //====================================================================//
        // Retrieve List of Order Transactions
        $Transactions = Mage::getModel('sales/order_payment_transaction')->getCollection()
                    ->setOrderFilter($this->Order)
                    ->addPaymentIdFilter($this->Payment->getId())
                    ->addTxnTypeFilter(Transaction::TYPE_PAYMENT)
                    ->setOrder('created_at', Varien_Data_Collection::SORT_ORDER_DESC)
                    ->setOrder('transaction_id', Varien_Data_Collection::SORT_ORDER_DESC);        
        
        //====================================================================//
        // Fill List with Data
        foreach ($Transactions as $Transaction) {
            //====================================================================//
            // READ Fields
            switch ($ListFieldName)
            {
                //====================================================================//
                // Payment Line - Payment Mode
                case 'mode':
                    $Value = $this->getPaymentMethod($this->Payment);
                    break;
                //====================================================================//
                // Payment Line - Payment Date
                case 'date':
                    $Value = date( SPL_T_DATECAST, Mage::getModel("core/date")->timestamp($Transaction->getCreatedAt()));
                    break;
                //====================================================================//
                // Payment Line - Payment Identification Number
                case 'number':
                    $Value = $Transaction->getTxnId();
                    break;
                //====================================================================//
                // Payment Line - Payment Amount
                case 'amount':
                    $Details    = $Transaction->getAdditionalInformation(Transaction::RAW_DETAILS );
                    $Value      = isset($Details["Amount"])?$Details["Amount"]:0;
                    break;
                default:
                    return;
            }
            //====================================================================//
            // Do Fill List with Data
            $this->List_Insert("payments",$FieldName,$Index,$Value);
            $Index++;
        }
        unset($this->In[$Key]);
    }
    
}



?>
