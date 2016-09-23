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
use Mage_Sales_Model_Order                          as MageOrder;
use Mage_Sales_Model_Order_Invoice                  as MageInvoice;
use Mage_Sales_Model_Order_Payment_Transaction      as Transaction;

/**
 *	\class      Order
 *	\brief      Customers Orders Management Class
 */
class SplashOrderSetter extends SplashObject
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
        foreach ($this->In as $FieldName => $Data) {
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
        foreach ($this->In as $FieldName => $Data) {
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
            $this->Object->setExtOrderId(SplashOrder::SPLASH_LABEL);
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
            // Direct Readings
            case 'increment_id':
                if ( $this->Object->getData($FieldName) != $Data ) {
                    $this->Object->setData($FieldName, $Data);
                    $this->update = True;
                }   
                break;
            
            //====================================================================//
            // Order Official Date
            case 'created_at':
                $CurrentDate = date( SPL_T_DATECAST, Mage::getModel("core/date")->timestamp($this->Object->getData($FieldName)));
                if ( $CurrentDate != $Data ) {
                    $this->Object->setData($FieldName, $Data);
                    $this->update = True;
                }   
                break;                
                    
            //====================================================================//
            // Order Company Id 
            case 'customer_id':
                $CustomerId = self::ObjectId_DecodeId( $Data );
                if ( $this->Object->getCustomerId() != $CustomerId ) {
                    //====================================================================//
                    // Load Customer Object 
                    $NewCustomer = Mage::getModel("customer/customer")->load($CustomerId);
                    //====================================================================//
                    //Update Customer Id 
                    $this->Object->setCustomer(Mage::getModel("customer/customer")->load($CustomerId));
                    //====================================================================//
                    //Update Customer Infos 
                    $this->Object->setCustomerEmail($NewCustomer->getEmail());
                    $this->Object->setCustomerFirstname($NewCustomer->getFirstname());
                    $this->Object->setCustomerLastname($NewCustomer->getLastname());
                    $this->Object->setCustomerMiddlename($NewCustomer->getMiddlename());
                    $this->Object->setCustomerPrefix($NewCustomer->getPrefix());
                    $this->Object->setCustomerSufix($NewCustomer->getSufix());
                    
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
    private function setMainFields($FieldName,$Data) 
    {   
        //====================================================================//
        // WRITE Field
        switch ($FieldName)
        {
            //====================================================================//
            // ORDER STATUS
            //====================================================================//   
            case 'state':
                $this->setOrderStatus($Data);
            break;                  
                
//        //====================================================================//
//        // ORDER INVOCE
//        //====================================================================//        
//        case 'facturee':
//            if ($Data) {
//                $this->Object->classifyBilled();
//            }
//            break;            
                
            //====================================================================//
            // ORDER Currency Data
            //====================================================================//        
            case 'order_currency_code':
            case 'base_to_order_rate':
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
    private function setAddressFields($FieldName,$Data) 
    {
        //====================================================================//
        // WRITE Field
        switch ($FieldName)
        {
            //====================================================================//
            // Billing/Shipping Address Writting
            case 'billing_address_id':
                $this->setAddressContents('billing', self::ObjectId_DecodeId( $Data ));
                break;                
            case 'shipping_address_id':
                //====================================================================//
                // Retrieve Address Object Id 
                $AdressId = self::ObjectId_DecodeId( $Data );
                //====================================================================//
                // Setup Address Object & Set Order as "Non Virtual" => With Shipping 
                if ($AdressId > 0) {
                    $this->setAddressContents('shipping', self::ObjectId_DecodeId( $Data ));
                    $this->Object->setIsVirtual(False);
                //====================================================================//
                // No Address Setup & Set Order as "Virtual" => No Shipping 
                } else {
                    $this->Object->setIsVirtual(True);
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
    private function setProducts($FieldName,$Data) 
    {
        //====================================================================//
        // Safety Check
        if ( $FieldName !== "lines" ) {
            return True;
        }
        if ( !$this->isSplash() ) {
            Splash::Log()->Deb("You Cannot Edit Orders Created on Magento");  
            unset($this->In[$FieldName]);            
            return True;
        }

        //====================================================================//
        // Get Original Order Items List
        $this->OrderItems   =   $this->Object->getAllItems();
        //====================================================================//
        // Verify Lines List & Update if Needed 
        foreach ($Data as $LineData) {
            //====================================================================//
            // Detect Shipping Informations => Product Label === SplashOrder::SHIPPING_LABEL
            if ( array_key_exists("sku", $LineData)
                &&  ($LineData["sku"] === SplashOrder::SHIPPING_LABEL) ) {
                $this->setShipping($LineData); 
                continue;
            }
            //====================================================================//
            // Update Shipping Informations
            $this->setOrderLineInit();
            //====================================================================//
            // Update Line Product/Infos/Totals
            $this->setProductLine($LineData);
            $this->setProductLineInfos($LineData);
            $this->setProductLineTotals();
            //====================================================================//
            // Save Changes
            if ( $this->OrderItemUpdate ) {  
                $this->OrderItem->save();
                Splash::Log()->Deb("Order Item Saved");            
                $this->OrderItemUpdate = False;
                $this->update = True;
            }        
            
        } 
        //====================================================================//
        // Delete Remaining Lines
        foreach ($this->OrderItems as $OrderItem) {
            //====================================================================//
            // Perform Line Delete
            $OrderItem->delete();
            $this->update = True;
        }        
        unset($this->In[$FieldName]);
    }

    /**
     *  @abstract     Set Given Order Address
     * 
     *  @return         none
     */
    private function setAddressContents($Type,$AddressId) {
        
        //====================================================================//
        // Read Original Billing/Shipping Order Address        
        if( $Type === "billing") {
            $Address    = $this->Object->getBillingAddress();
        } elseif( $Type === "shipping") {
            $Address    = $this->Object->getShippingAddress();
        } else {
            return False;
        }
        //====================================================================//
        // Empty => Create Order Address        
        if( !$Address ) {
            $Address    =   Mage::getModel('sales/order_address')
                    ->setOrder($this->Object)
                    ->setAddressType($Type);
        }

        //====================================================================//
        // Check For Changes       
        if ( $Address->getCustomerAddressId() == $AddressId ) {
            return False;
        } 
        //====================================================================//
        // Load Customer Address 
        $CustomerAddress = Mage::getModel('customer/address')->load($AddressId);
        if ( $CustomerAddress->getEntityId() != $AddressId ) {
            return False;
        }
        //====================================================================//
        // Update Address 
        $Address
            ->setCustomerAddressId($AddressId)
            ->setFirstname($CustomerAddress->getFirstname())
            ->setMiddlename($CustomerAddress->getMiddlename())
            ->setLastname($CustomerAddress->getLastname())
            ->setSuffix($CustomerAddress->getSuffix())
            ->setCompany($CustomerAddress->getCompany())
            ->setStreet($CustomerAddress->getStreet())
            ->setCity($CustomerAddress->getCity())
            ->setCountry_id($CustomerAddress->getCountry_id())
            ->setRegion($CustomerAddress->getRegion())
            ->setRegion_id($CustomerAddress->getRegion_id())
            ->setPostcode($CustomerAddress->getPostcode())
            ->setTelephone($CustomerAddress->getTelephone())
            ->setFax($CustomerAddress->getFax())                
            ->save();
        $this->update = True;
//        Splash::Log()->www("Address After", $Address->getData());
        //====================================================================//
        // Update Order Address Collection       
        if( $Type === "billing") {
            $this->Object->setBillingAddress($Address);
        } elseif( $Type === "shipping") {
            $this->Object->setShippingAddress($Address);
        }
        return True;
    }     
    
    /**
     *  @abstract     Init Given Order Line Data For Update
     * 
     *  @param        array     $OrderLineData          OrderLine Data Array
     * 
     *  @return         none
     */
    private function setOrderLineInit() 
    {
        //====================================================================//
        // Read Next Order Product Line
        $this->OrderItem = array_shift($this->OrderItems);
        //====================================================================//
        // Empty => Create New Line
        if ( !$this->OrderItem ) {
            //====================================================================//
            // Create New Order Item
            $this->OrderItem = Mage::getModel('sales/order_item')
                ->setStoreId($this->Object->getStore()->getStoreId())
                ->setQuoteItemId(NULL)
                ->setQuoteParentItemId(NULL)
                ->setOrder($this->Object);  
            //====================================================================//
            // Add Item to Order
            $this->Object->addItem($this->OrderItem);
            Splash::Log()->Deb("New Order Item Created");            
        }
    }
    
    /**
     *  @abstract     Add or Update Given Product Order Line Data
     * 
     *  @param        array     $OrderLineData          OrderLine Data Array
     * 
     *  @return         none
     */
    private function setProductLine($OrderLineData) 
    {
        //====================================================================//
        // Detect & Verify Product Id 
        if ( array_key_exists("product_id", $OrderLineData) ) {
            $ProductId  = $this->ObjectId_DecodeId($OrderLineData["product_id"]);
            $Product    = Mage::getModel('catalog/product')
                    ->load($ProductId);
            //====================================================================//
            // Verify Product Id Is Valid
            if ( $Product->getEntityId() !== $ProductId ) {
                $Product = Null;
            }
        } else {
            $Product = Null;
        }
        
        //====================================================================//
        // If Valid Product Given => Update Product Informations
        if ( $Product ) {
            //====================================================================//
            // Verify Product Id Changed
            if ( $this->OrderItem->getProductId() !== $ProductId ) {
                //====================================================================//
                // Update Order Item
                $this->OrderItem
                        ->setProductId($Product->getEntityId())
                        ->setProductType($Product->getTypeId())
                        ->setName($Product->getName())
                        ->setSku($Product->getSku());
                $this->OrderItemUpdate = True;
                Splash::Log()->Deb("Product Order Item Updated");            
            }
        //====================================================================//
        // Update Line Without Product Id
        } else {
            if (  array_key_exists("sku", $OrderLineData) 
                &&  ($this->OrderItem->getName() !== $OrderLineData["sku"] ) ) {
                //====================================================================//
                // Update Order Item
                $this->OrderItem
                        ->setProductId(Null)
                        ->setProductType(Null)
                        ->setSku($OrderLineData["sku"]);
                $this->OrderItemUpdate = True;
            }
            if (  array_key_exists("name", $OrderLineData) 
                &&  ($this->OrderItem->getName() !== $OrderLineData["name"] ) ) {
                //====================================================================//
                // Update Order Item
                $this->OrderItem
                        ->setProductId(Null)
                        ->setProductType(Null)
                        ->setName($OrderLineData["name"]);
                $this->OrderItemUpdate = True;
                Splash::Log()->Deb("Custom Order Item Updated");
            }
        }
    }
    
    /**
     *  @abstract     Add or Update Given Order Line Informations
     * 
     *  @param        array     $OrderLineData          OrderLine Data Array
     * 
     *  @return         none
     */
    private function setProductLineInfos($OrderLineData) 
    {
        //====================================================================//
        // Update Quantity Informations
        if ( array_key_exists("qty_ordered", $OrderLineData) ) {
            //====================================================================//
            // Verify Qty Changed
            if ( $this->OrderItem->getQtyOrdered() !== $OrderLineData["qty_ordered"] ) {
                $this->OrderItem->setQtyBackordered(NULL)
                    ->setTotalQtyOrdered($OrderLineData["qty_ordered"])
                    ->setQtyOrdered($OrderLineData["qty_ordered"]);
                $this->OrderItemUpdate  = True;
                $this->UpdateTotals     = True;                
            }
        }
        //====================================================================//
        // Update Price Informations
        if ( array_key_exists("unit_price", $OrderLineData) ) {
            $OrderLinePrice     =    $OrderLineData["unit_price"];
            //====================================================================//
            // Verify Price Changed
            if ( $this->OrderItem->getPrice() !== $OrderLinePrice["ht"] ) {
                $this->OrderItem
                    ->setPrice($OrderLinePrice["ht"])
                    ->setBasePrice($OrderLinePrice["ht"])
                    ->setBaseOriginalPrice($OrderLinePrice["ht"])
                    ->setOriginalPrice($OrderLinePrice["ht"]);
                $this->OrderItemUpdate  = True;
                $this->UpdateTotals     = True;                
            }
            //====================================================================//
            // Verify Tax Rate Changed
            if ( $this->OrderItem->getTaxPercent() !== $OrderLinePrice["vat"] ) {
                $this->OrderItem->setTaxPercent($OrderLinePrice["vat"]);
                $this->OrderItemUpdate  = True;
                $this->UpdateTotals     = True;                
            }
        }
        //====================================================================//
        // Update Discount Informations
        if ( array_key_exists("discount_percent", $OrderLineData) ) {
            //====================================================================//
            // Verify Discount Percent Changed
            if ( !self::Float_Compare($this->OrderItem->getDiscountPercent(), $OrderLineData["discount_percent"]) ) {
                $this->OrderItem->setDiscountPercent($OrderLineData["discount_percent"]);
                $this->OrderItemUpdate  = True;
                $this->UpdateTotals     = True;                
            }
            
        }
        
    }

    /**
     *  @abstract     Add or Update Given Order Shipping Informations
     * 
     *  @param        array     $OrderLineData          OrderLine Data Array
     * 
     *  @return         none
     */
    private function setShipping($OrderLineData) 
    {
        //====================================================================//
        // Update Shipping Description
        if ( array_key_exists("name", $OrderLineData) ) {
            //====================================================================//
            // Verify Discount Changed
            if ( $this->Object->getShippingDescription() !== $OrderLineData["name"] ) {
                $this->Object->setShippingDescription($OrderLineData["name"]);
                $this->update = True;
            }
        }
        
        //====================================================================//
        // Update Price Informations
        if ( array_key_exists("unit_price", $OrderLineData) ) {
            $OrderLinePrice     =    $OrderLineData["unit_price"];
            //====================================================================//
            // Verify HT Price Changed
            if ( $this->Object->getShippingAmount() !== $OrderLinePrice["ht"] ) {
                $this->Object
                    ->setShippingAmount($OrderLinePrice["ht"])
                    ->setBaseShippingAmount($OrderLinePrice["ht"]);
                $this->update           = True;
                $this->UpdateTotals     = True;                
            }
            //====================================================================//
            // Verify Tax Rate Changed
            $TaxAmount =  $OrderLinePrice["ttc"] - $OrderLinePrice['ht'];
            if ( $this->OrderItem->getShippingTaxAmount() !== $TaxAmount ) {
                $this->OrderItem->setShippingTaxAmount($TaxAmount);
                $this->update           = True;
                $this->UpdateTotals     = True;                
            }
        }
    }
    
    /**
     *  @abstract     Add or Update Given Order Line Informations
     * 
     *  @param        array     $OrderLineData          OrderLine Data Array
     * 
     *  @return         none
     */
    private function setProductLineTotals() 
    {
        //====================================================================//
        // Update Row Total
        $SubTotalHt     =   $this->OrderItem->getPrice() * $this->OrderItem->getQtyOrdered();
        $DiscountAmount =   ( $this->OrderItem->getDiscountPercent() * $SubTotalHt ) / 100;
        $TotalHt        =   $SubTotalHt - $DiscountAmount;
        $TaxAmount      =   $TotalHt * $this->OrderItem->getTaxPercent() / 100;
        $DiscountTax    =   $DiscountAmount * $this->OrderItem->getTaxPercent() / 100;
        $TotalTtc       =   $TotalHt * ( 1 + $this->OrderItem->getTaxPercent() / 100 );
        
        //====================================================================//
        // Verify Total Changed
        if ( $this->OrderItem->getRowTotal() !== $TotalHt ) {
            $this->OrderItem
                // ROW Totals
                ->setRowTotal($TotalHt)
                ->setBaseRowTotal($TotalHt)
                ->setRowTotalInclTax($TotalTtc)
                ->setBaseRowTotalInclTax($TotalTtc)
                // ROW Tax Amounts
                ->setTaxAmount($TaxAmount)
                ->setBaseTaxAmount($TaxAmount)
                // ROW Discounts Amounts
                ->setBaseDiscountAmount($DiscountAmount)
                ->setDiscountAmount($DiscountAmount)
                ->setBaseDiscountTaxAmount($DiscountTax)
                ->setDiscountTaxAmount($DiscountTax);
            $this->OrderItemUpdate  = True;
            $this->UpdateTotals     = True;                
        }
        
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

    /**
     *   @abstract   Check if this Order was Created by Splash
     * 
     *   @return     bool 
     */
    private function isSplash() {
        return ( $this->Object->getExtOrderId() === SplashOrder::SPLASH_LABEL )? True:False;
    }     

    /**
     *   @abstract   Update Order Status
     * 
     *   @param      string     $Status         Schema.org Order Status String
     * 
     *   @return     bool 
     */
    private function setOrderStatus($Status) {
        
        if ( !$this->isSplash() ) {
            Splash::Log()->Deb("You Cannot Change Status of Orders Created on Magento");  
            return True;
        }        
        //====================================================================//
        // Generate Magento Order State from Schema.org orderStatus
        switch ($Status)
        {
            case "OrderPaymentDue":
                $MagentoState   =   MageOrder::STATE_PENDING_PAYMENT;
                break;
            case "OrderProcessing":
            case "OrderInTransit":
            case "OrderPickupAvailable":
                $MagentoState   =   MageOrder::STATE_PROCESSING;
                break;
            case "OrderDelivered":
                $MagentoState   =   MageOrder::STATE_COMPLETE;
                break;
            case "OrderReturned":
                $MagentoState   =   MageOrder::STATE_CLOSED;
                break;
            case "OrderCancelled":
                $MagentoState   =   MageOrder::STATE_CANCELED;
                break;
            case "OrderProblem":
                $MagentoState   =   MageOrder::STATE_HOLDED;
                break;
        }        
        //====================================================================//
        // Update Order State if Requiered
        if ( $this->Object->getState() != $MagentoState ) {
            
//            try {
//                //====================================================================//
//                // EXECUTE SYSTEM ACTIONS if Necessary
//                switch ($MagentoState)
//                {
//                    case "OrderPaymentDue":
//                        $MagentoState   =   MageOrder::STATE_PENDING_PAYMENT;
//                        break;
//                    case "OrderProcessing":
//                    case "OrderInTransit":
//                    case "OrderPickupAvailable":
//                        $MagentoState   =   MageOrder::STATE_PROCESSING;
//                        break;
//                    case "OrderDelivered":
//                        $MagentoState   =   MageOrder::STATE_COMPLETE;
//                        break;
//                    case MageOrder::STATE_CLOSED:
//                        $MagentoState   =   MageOrder::STATE_CLOSED;
//                        break;
//                    case MageOrder::STATE_CANCELED:
//                        $this->Object->registerCancellation('Updated by SplashSync Module',False);
//                        break;
//                    case MageOrder::STATE_HOLDED:
//                        $this->Object->hold();
//                        break;
//                }        
//                
//            } catch (Exception $exc) {
//                Splash::Log()->War($exc->getMessage());  
//            }
            
            //====================================================================//
            // Update Order State if Requiered
            try {
                $this->Object->setState($MagentoState, True, 'Updated by SplashSync Module',True);
            } catch (Exception $exc) {
                Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,$exc->getMessage());
            }            
            $this->update = True;
        }  
                
        return True;
    }    
    
    /**
     *   @abstract   Update Order Totals
     * 
     *   @return     bool 
     */
    private function setUpdateTotals() {
        
        //====================================================================//
        // Exit if NOT Needed
        if ( !$this->UpdateTotals ) {
            return True;
        } 
        if ( !$this->isSplash() ) {
            return True;
        }        
        //====================================================================//
        // Init Prices Counters 
        $tax_amount         = 0;
        $subtotal           = 0;
        $subtotal_incl_tax  = 0;
        $total_qty_ordered  = 0;
        //====================================================================//
        // Counts For Products Lines 
        $Products = $this->Object->getAllItems();
        if ( is_array($Products) ) {
            foreach ($Products as $ProductLine) {
                //====================================================================//
                // Fill Order Quantity Count
                $total_qty_ordered  += $ProductLine->getQtyOrdered();
                //====================================================================//
                // Fill Order Product Costs
                $subtotal           += $ProductLine->getRowTotal();
                $subtotal_incl_tax  += $ProductLine->getRowTotalInclTax();
                $tax_amount         += $ProductLine->getTaxAmount();
            }
        }        
        //====================================================================//
        // Update Subtotals
        $this->Object->setBaseTotalQtyOrdered($total_qty_ordered);
        $this->Object->setTotalQtyOrdered($total_qty_ordered);

        $this->Object->setBaseSubtotal($subtotal);
        $this->Object->setSubtotal($subtotal);

        $this->Object->setBaseSubtotalInclTax($subtotal_incl_tax);
        $this->Object->setSubtotalInclTax($subtotal_incl_tax);
        
        $this->Object->setBaseTaxAmount($tax_amount);
        $this->Object->setTaxAmount($tax_amount);
        
        //====================================================================//
        // Fill Order Grand Total
        $this->Object->setBaseGrandTotal( $subtotal_incl_tax + $this->Object->getShippingAmount() );
        $this->Object->setGrandTotal( $subtotal_incl_tax + $this->Object->getShippingAmount() );

        $this->UpdateTotals     = False;
        $this->update           = True;
    }
        
}

?>
