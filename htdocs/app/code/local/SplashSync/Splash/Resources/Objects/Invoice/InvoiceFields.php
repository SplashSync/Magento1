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
 * @abstract    Splash PHP Module For Magento 1 - Invoice Object Intégration SubClass
 * @author      B. Paquier <contact@splashsync.com>
 */
class SplashInvoiceFields extends SplashObject
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
        // MAIN INFORMATIONS
        //====================================================================//
        $this->buildMainFields();
        //====================================================================//
        // MAIN INVOICE LINE INFORMATIONS
        //====================================================================//
        $this->buildProductsLineFields();
        //====================================================================//
        //INVOICE PAYMENTS LIST INFORMATIONS
        //====================================================================//
        $this->buildPaymentLineFields();
        //====================================================================//
        // Publish Fields
        return $this->FieldsFactory()->Publish();
    }

    //====================================================================//
    // Fields Generation Functions
    //====================================================================//

    /**
    *   @abstract     Build Core Fields using FieldFactory
    */
    private function buildCoreFields()   {
        
        //====================================================================//
        // Customer Object
        $this->FieldsFactory()->Create(self::ObjectId_Encode( "ThirdParty" , SPL_T_ID))
                ->Identifier("customer_id")
                ->Name('Customer')
                ->MicroData("http://schema.org/Invoice","customer")
                ->ReadOnly();

        //====================================================================//
        // Customer Name
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("customer_name")
                ->Name('Customer Name')
                ->MicroData("http://schema.org/Invoice","customer")
                ->isListed()
                ->ReadOnly();

        
        //====================================================================//
        // Order Object
        $this->FieldsFactory()->Create(self::ObjectId_Encode( "Order" , SPL_T_ID))
                ->Identifier("order_id")
                ->Name('Order')
                ->MicroData("http://schema.org/Invoice","referencesOrder")
                ->isRequired();  
        
        //====================================================================//
        // Invoice Reference
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("increment_id")
                ->Name('Number')
                ->MicroData("http://schema.org/Invoice","confirmationNumber")       
                ->IsListed();

        //====================================================================//
        // Order Reference
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("reference")
                ->Name('Reference')
                ->MicroData("http://schema.org/Order","orderNumber")       
                ->ReadOnly();

        //====================================================================//
        // Order Date 
        $this->FieldsFactory()->Create(SPL_T_DATE)
                ->Identifier("created_at")
                ->Name("Date")
                ->MicroData("http://schema.org/Order","orderDate")
                ->isRequired()
                ->IsListed();
        
    }    

    /**
    *   @abstract     Build Address Fields using FieldFactory
    */
    private function buildMainFields() {
        
//        //====================================================================//
//        // Delivery Date 
//        $this->FieldsFactory()->Create(SPL_T_DATE)
//                ->Identifier("date_livraison")
//                ->Name($langs->trans("DeliveryDate"))
//                ->MicroData("http://schema.org/ParcelDelivery","expectedArrivalUntil");
        
        //====================================================================//
        // PRICES INFORMATIONS
        //====================================================================//
        
        //====================================================================//
        // Invoice Total Price HT
        $this->FieldsFactory()->Create(SPL_T_DOUBLE)
                ->Identifier("grand_total_excl_tax")
                ->Name("Total (tax excl.)" . " (" . Mage::app()->getStore()->getCurrentCurrencyCode() . ")")
                ->MicroData("http://schema.org/Invoice","totalPaymentDue")
//                ->isListed()
                ->ReadOnly();
        
        //====================================================================//
        // Invoice Total Price TTC
        $this->FieldsFactory()->Create(SPL_T_DOUBLE)
                ->Identifier("grand_total")
                ->Name("Total (tax incl.)" . " (" . Mage::app()->getStore()->getCurrentCurrencyCode() . ")")
                ->MicroData("http://schema.org/Invoice","totalPaymentDueTaxIncluded")
                ->isListed()
                ->ReadOnly();        

        //====================================================================//
        // INVOICE STATUS FLAGS
        //====================================================================//        
        
        //====================================================================//
        // Order Current Status
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("state")
                ->Name("Status")
                ->MicroData("http://schema.org/Invoice","paymentStatus")
//                ->ReadOnly()
                ->AddChoices(
                    array(  "PaymentDraft"          => "Draft",
                            "PaymentDue"            => "Payment Due",
                            "PaymentDeclined"       => "Payment Declined",
                            "PaymentPastDue"        => "Payment Past Due",
                            "PaymentComplete"       => "Payment Complete",
                            "PaymentCanceled"       => "Canceled",
                        )
                    )                
                ->NotTested();
        
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("state_name")
                ->Name("Status Name")
                ->MicroData("http://schema.org/Invoice","paymentStatusName")
                ->ReadOnly();        
        
        //====================================================================//
        // INVOICE STATUS FLAGS
        //====================================================================//        
        
        //====================================================================//
        // Is Canceled
        // => There is no Diffrence Between a Draft & Canceled Order on Prestashop. 
        //      Any Non Validated Order is considered as Canceled
        $this->FieldsFactory()->Create(SPL_T_BOOL)
                ->Identifier("isCanceled")
                ->Name(Mage::helper('sales')->__('Invoice') . " : " . Mage::helper('sales')->__('Canceled'))
                ->MicroData("http://schema.org/PaymentStatusType","PaymentDeclined")
                ->Association("iscanceled","isvalidated","ispaid")
                ->ReadOnly();     
        
        //====================================================================//
        // Is Validated
        $this->FieldsFactory()->Create(SPL_T_BOOL)
                ->Identifier("isValidated")
                ->Name(Mage::helper('sales')->__('Invoice') . " : " . "Valid")
                ->MicroData("http://schema.org/PaymentStatusType","PaymentDue")
                ->Association("iscanceled","isvalidated","ispaid")
                ->ReadOnly();

        //====================================================================//
        // Is Paid
        $this->FieldsFactory()->Create(SPL_T_BOOL)
                ->Identifier("isPaid")
                ->Name(Mage::helper('sales')->__('Invoice') . " : " . Mage::helper('sales')->__('Paid'))
                ->MicroData("http://schema.org/PaymentStatusType","PaymentComplete")
                ->ReadOnly()
                ->NotTested();
        
        return;
    }
        
    /**
    *   @abstract     Build Address Fields using FieldFactory
    */
    private function buildProductsLineFields() {
        
        $ListName = Mage::helper('sales')->__('Items') . " => " ;
        $ListName = "" ;
        
        //====================================================================//
        // Order Line Label
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("sku")
//                ->isRequired()
                ->InList("items")
//                ->ReadOnly()
                ->Name( $ListName . Mage::helper('sales')->__('Sku'))
                ->MicroData("http://schema.org/partOfInvoice","name")
                ->Association("description@items","qty@items","price@items");        
        
        //====================================================================//
        // Order Line Description
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("name")
                ->InList("items")
//                ->ReadOnly()
                ->Name( $ListName . Mage::helper('sales')->__('Description Message'))
                ->MicroData("http://schema.org/partOfInvoice","description")        
                ->Association("product_name@items","quantity@items","price@items");        

        //====================================================================//
        // Order Line Product Identifier
        $this->FieldsFactory()->Create(self::ObjectId_Encode( "Product" , SPL_T_ID))        
                ->Identifier("product_id")
                ->InList("items")
//                ->ReadOnly()
                ->Name( $ListName . Mage::helper('sales')->__('Product'))
                ->MicroData("http://schema.org/Product","productID")
                ->Association("product_name@items","quantity@items","price@items");      
//                ->NotTested();        

        //====================================================================//
        // Order Line Quantity
        $this->FieldsFactory()->Create(SPL_T_INT)        
                ->Identifier("qty")
//                ->isRequired()
                ->InList("items")
                ->Name( $ListName . Mage::helper('sales')->__('Qty Invoiced'))                
                ->MicroData("http://schema.org/QuantitativeValue","value")        
                ->Association("product_name@items","quantity@items","price@items");

        //====================================================================//
        // Order Line Discount
        $this->FieldsFactory()->Create(SPL_T_DOUBLE)        
                ->Identifier("discount_percent")
                ->InList("items")
//                ->ReadOnly()
//                ->WriteOnly()
                ->Name( $ListName . Mage::helper('sales')->__('Discount (%s)'))                
                ->MicroData("http://schema.org/Order","discount")
                ->Association("product_name@items","quantity@items","price@items"); 

        //====================================================================//
        // Order Line Unit Price
        $this->FieldsFactory()->Create(SPL_T_PRICE)        
                ->Identifier("unit_price")
//                ->isRequired()
//                ->ReadOnly()
                ->InList("items")
                ->Name( $ListName . Mage::helper('sales')->__('Price'))     
                ->MicroData("http://schema.org/PriceSpecification","price")        
                ->Association("product_name@items","quantity@items","price@items");       

    }

    /**
    *   @abstract     Build Address Fields using FieldFactory
    */
    private function buildPaymentLineFields() {
        
        $ListName = Mage::helper('sales')->__('Payment Information') . " => " ;
        $ListName = "" ;
        
        //====================================================================//
        // Payment Line Payment Method 
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("mode")
                ->InList("payments")
                ->Name( $ListName .  Mage::helper('sales')->__('Payment Method'))
                ->MicroData("http://schema.org/Invoice","PaymentMethod")
                ->ReadOnly()
                ->NotTested();        

        //====================================================================//
        // Payment Line Date
        $this->FieldsFactory()->Create(SPL_T_DATE)        
                ->Identifier("date")
                ->InList("payments")
                ->Name( $ListName .  Mage::helper('sales')->__('Date'))
                ->MicroData("http://schema.org/PaymentChargeSpecification","validFrom")
                ->Association("date@payments","mode@payments","amount@payments");        
//                ->ReadOnly()
//                ->NotTested();        

        //====================================================================//
        // Payment Line Payment Identifier
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)        
                ->Identifier("number")
                ->InList("payments")
                ->Name( $ListName .  Mage::helper('sales')->__('Transaction ID'))
                ->MicroData("http://schema.org/Invoice","paymentMethodId")        
                ->Association("date@payments","mode@payments","amount@payments");        
//                ->ReadOnly()
//                ->NotTested();        

        //====================================================================//
        // Payment Line Amount
        $this->FieldsFactory()->Create(SPL_T_DOUBLE)        
                ->Identifier("amount")
                ->InList("payments")
                ->Name( $ListName .  Mage::helper('sales')->__("Amount"))
                ->MicroData("http://schema.org/PaymentChargeSpecification","price");
//                ->ReadOnly()
//                ->NotTested();                    
    }
}



?>
