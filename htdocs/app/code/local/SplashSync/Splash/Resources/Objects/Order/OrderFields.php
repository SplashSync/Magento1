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
 * @abstract    Splash PHP Module For Magento 1 - Order Object Intégration SubClass
 * @author      B. Paquier <contact@splashsync.com>
 */
class SplashOrderFields extends SplashObject
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
        // ORDER ADDRESS INFORMATIONS
        //====================================================================//
        $this->buildAddressFields();
        //====================================================================//
        // MAIN ORDER LINE INFORMATIONS
        //====================================================================//
        $this->buildProductsLineFields();
        //====================================================================//
        // META INFORMATIONS
        //====================================================================//
        $this->buildMetaFields();
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
                ->MicroData("http://schema.org/Organization","ID")
                ->isRequired();  
        
        //====================================================================//
        // Reference
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("increment_id")
                ->Name('Reference')
                ->MicroData("http://schema.org/Order","orderNumber")       
                ->isRequired()
                ->IsListed();

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
        // Order Total Price HT
        $this->FieldsFactory()->Create(SPL_T_DOUBLE)
                ->Identifier("grand_total_excl_tax")
                ->Name("Total (tax excl.)" . " (" . Mage::app()->getStore()->getCurrentCurrencyCode() . ")")
                ->MicroData("http://schema.org/Invoice","totalPaymentDue")
//                ->isListed()
                ->ReadOnly();
        
        //====================================================================//
        // Order Total Price TTC
        $this->FieldsFactory()->Create(SPL_T_DOUBLE)
                ->Identifier("grand_total")
                ->Name("Total (tax incl.)" . " (" . Mage::app()->getStore()->getCurrentCurrencyCode() . ")")
                ->MicroData("http://schema.org/Invoice","totalPaymentDueTaxIncluded")
                ->isListed()
                ->ReadOnly();        
        
        //====================================================================//
        // ORDER STATUS
        //====================================================================//        

        //====================================================================//
        // Order Current Status
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("state")
                ->Name("Status")
                ->MicroData("http://schema.org/Order","orderStatus")
                ->isListed()
                ->AddChoices(
                    array(  "OrderPaymentDue"       => "Payment Due",
                            "OrderProcessing"       => "In Process",
                            "OrderInTransit"        => "in Transit",
                            "OrderPickupAvailable"  => "Pick Up Available",
                            "OrderDelivered"        => "Delivered",
                            "OrderReturned"         => "Returned",
                            "OrderCancelled"        => "Canceled",
                            "OrderProblem"          => "On Hold"
                        )
                    )
                ->NotTested();      

        //====================================================================//
        // ORDER STATUS FLAGS
        //====================================================================//        
        
        //====================================================================//
        // Is Canceled
        // => There is no Diffrence Between a Draft & Canceled Order on Prestashop. 
        //      Any Non Validated Order is considered as Canceled
        $this->FieldsFactory()->Create(SPL_T_BOOL)
                ->Identifier("isCanceled")
                ->Name("Order" . " : " . "Canceled")
                ->MicroData("http://schema.org/OrderStatus","OrderCancelled")
                ->Association( "isdraft","iscanceled","isvalidated","isclosed")
                ->ReadOnly();     
        
        //====================================================================//
        // Is Validated
        $this->FieldsFactory()->Create(SPL_T_BOOL)
                ->Identifier("isValidated")
                ->Name("Order" . " : " . "Valid")
                ->MicroData("http://schema.org/OrderStatus","OrderProcessing")
                ->Association( "isdraft","iscanceled","isvalidated","isclosed")
                ->ReadOnly();
        
        //====================================================================//
        // Is Closed
        $this->FieldsFactory()->Create(SPL_T_BOOL)
                ->Identifier("isClosed")
                ->Name("Order" . " : " . "Closed")
                ->MicroData("http://schema.org/OrderStatus","OrderDelivered")
                ->Association( "isdraft","iscanceled","isvalidated","isclosed")
                ->ReadOnly();

        //====================================================================//
        // Is Paid
        $this->FieldsFactory()->Create(SPL_T_BOOL)
                ->Identifier("isPaid")
                ->Name("Order" . " : " . "Paid")
                ->MicroData("http://schema.org/OrderStatus","OrderPaid")
                ->ReadOnly();
        
        
        //====================================================================//
        // ORDER Currency Data
        //====================================================================//        
        
        //====================================================================//
        // Order Currency 
        $this->FieldsFactory()->Create(SPL_T_CURRENCY)
                ->Identifier("order_currency_code")
                ->Name("Currency")
                ->MicroData("https://schema.org/PriceSpecification","priceCurrency");

        //====================================================================//
        // Order Currency 
        $this->FieldsFactory()->Create(SPL_T_DOUBLE)
                ->Identifier("base_to_order_rate")
                ->Name("Currency Rate")
                ->MicroData("https://schema.org/PriceSpecification","priceCurrencyRate");
        
        return;
    }
        
    /**
    *   @abstract     Build Address Fields using FieldFactory
    */
    private function buildProductsLineFields() {
        
//        $ListName = "Products => ";
        $ListName = "";
        
        //====================================================================//
        // Order Line Label
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("sku")
                ->InList("lines")
                ->Name( $ListName . "Label")
                ->MicroData("http://schema.org/partOfInvoice","name")
                ->Association("name@lines","qty_ordered@lines","unit_price@lines");        
        
        //====================================================================//
        // Order Line Description
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("name")
                ->InList("lines")
                ->Name( $ListName . "Description")
                ->MicroData("http://schema.org/partOfInvoice","description")        
                ->Association("name@lines","qty_ordered@lines","unit_price@lines");        

        //====================================================================//
        // Order Line Product Identifier
        $this->FieldsFactory()->Create(self::ObjectId_Encode( "Product" , SPL_T_ID))        
                ->Identifier("product_id")
                ->InList("lines")
                ->Name( $ListName . "Product ID")
                ->MicroData("http://schema.org/Product","productID")
                ->Association("name@lines","qty_ordered@lines","unit_price@lines");        
//                ->NotTested();        

        //====================================================================//
        // Order Line Quantity
        $this->FieldsFactory()->Create(SPL_T_INT)        
                ->Identifier("qty_ordered")
                ->InList("lines")
                ->Name( $ListName . "Quantity")
                ->MicroData("http://schema.org/QuantitativeValue","value")        
                ->Association("name@lines","qty_ordered@lines","unit_price@lines");        

        //====================================================================//
        // Order Line Discount
        $this->FieldsFactory()->Create(SPL_T_DOUBLE)        
                ->Identifier("discount_percent")
                ->InList("lines")
                ->Name( $ListName . "Discount (%)")
                ->MicroData("http://schema.org/Order","discount")
                ->Association("name@lines","qty_ordered@lines","unit_price@lines");        

        //====================================================================//
        // Order Line Unit Price
        $this->FieldsFactory()->Create(SPL_T_PRICE)        
                ->Identifier("unit_price")
                ->InList("lines")
                ->Name( $ListName . "Price" )
                ->MicroData("http://schema.org/PriceSpecification","price")        
                ->Association("name@lines","qty_ordered@lines","unit_price@lines");        

    }

    /**
    *   @abstract     Build Address Fields using FieldFactory
    */
    private function buildAddressFields()   {
        
        //====================================================================//
        // Billing Address
        $this->FieldsFactory()->Create(self::ObjectId_Encode( "Address" , SPL_T_ID))
                ->Identifier("billing_address_id")
                ->Name('Billing Address ID')
                ->MicroData("http://schema.org/Order","billingAddress")
                ->isRequired();  
        
        //====================================================================//
        // Shipping Address
        $this->FieldsFactory()->Create(self::ObjectId_Encode( "Address" , SPL_T_ID))
                ->Identifier("shipping_address_id")
                ->Name('Shipping Address ID')
                ->MicroData("http://schema.org/Order","orderDelivery");  
        
    }    
    
    /**
    *   @abstract     Build Meta Fields using FieldFactory
    */
    private function buildMetaFields() {

        
        //====================================================================//
        // STRUCTURAL INFORMATIONS
        //====================================================================//

//        //====================================================================//
//        // Order Generic Status
//        $this->FieldsFactory()->Create(SPL_T_BOOL)
//                ->Identifier("status")
//                ->Name($langs->trans("Active"))
//                ->MicroData("http://schema.org/Organization","active")
//                ->IsListed();        
//        
//        if ( Splash::Local()->DolVersionCmp("3.6.0") >= 0 ) {
//            //====================================================================//
//            // isProspect
//            $this->FieldsFactory()->Create(SPL_T_BOOL)
//                    ->Identifier("prospect")
//                    ->Name($langs->trans("Prospect"))
//                    ->MicroData("http://schema.org/Organization","prospect");        
//        }

        //====================================================================//
        // TRACEABILITY INFORMATIONS
        //====================================================================//        
        
        //====================================================================//
        // TMS - Last Change Date 
        $this->FieldsFactory()->Create(SPL_T_DATE)
                ->Identifier("updated_at")
                ->Name("Last update")
                ->MicroData("http://schema.org/DataFeedItem","dateModified")
                ->ReadOnly();
        
    }   

    /**
    *   @abstract     Build Core Fields using FieldFactory
    */
    private function buildPaymentFields()   {
        
        $ListName = "";
        
//        //====================================================================//
//        // Payment Line Payment Method 
//        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
//                ->Identifier("mode")
//                ->InList("payments")
//                ->Name( $ListName .  "PaymentMode")
//                ->MicroData("http://schema.org/Invoice","PaymentMethod")
//                ->NotTested();        
//
//        //====================================================================//
//        // Payment Line Date
//        $this->FieldsFactory()->Create(SPL_T_DATE)        
//                ->Identifier("date")
//                ->InList("payments")
//                ->Name( $ListName .  "Date")
//                ->MicroData("http://schema.org/PaymentChargeSpecification","validFrom")
////                ->Association("date@payments","mode@payments","amount@payments");        
//                ->NotTested();        
//
//        //====================================================================//
//        // Payment Line Payment Identifier
//        $this->FieldsFactory()->Create(SPL_T_VARCHAR)        
//                ->Identifier("number")
//                ->InList("payments")
//                ->Name( $ListName .  'Number')
//                ->MicroData("http://schema.org/Invoice","paymentMethodId")        
////                ->Association("date@payments","mode@payments","amount@payments");        
//                ->NotTested();        
//
//        //====================================================================//
//        // Payment Line Amount
//        $this->FieldsFactory()->Create(SPL_T_DOUBLE)        
//                ->Identifier("amount")
//                ->InList("payments")
//                ->Name( $ListName .  "Amount")
//                ->MicroData("http://schema.org/PaymentChargeSpecification","price")
//                ->NotTested();         
        
        //====================================================================//
        // Invoices Objects Collection
//        $this->FieldsFactory()->Create(self::ObjectId_Encode( "Invoices" , SPL_T_ID))
//                ->InList("Invoices")
//                ->Identifier("invoice_id")
//                ->Name('Customer Invoice')
//                ->MicroData("http://schema.org/Order","referencesInvoice");  
        
    }  
    
}

?>
