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

use Mage_Sales_Model_Order                          as MageOrder;
use Mage_Sales_Model_Order_Invoice                  as MageInvoice;
use Mage_Sales_Model_Order_Payment_Transaction      as Transaction;

/**
 * @abstract    Splash PHP Module For Magento 1 - Invoice Object Intégration SubClass
 * @author      B. Paquier <contact@splashsync.com>
 */
class Setters extends ObjectBase
{
   
    //====================================================================//
    // General Class Variables	
    //====================================================================//
    
    protected   $Order          = Null;
    protected   $Products       = Null;
    protected   $Payments       = Null;

    protected static $ITEM_FILTERS = array(
            "qty"                   => "qty_invoiced",
            "tax_amount"            => "tax_invoiced",
            "base_tax_amount"       => "base_tax_invoiced",
            "discount_amount"       => "discount_invoiced",
            "base_discount_amount"  => "base_discount_invoiced",
            "row_total"             => "row_invoiced",
            "base_row_total"        => "base_row_invoiced",
            );

    protected static $ORDER_FILTERS = array(
            "grand_total"           => "total_invoiced",
            "base_grand_total"      => "base_total_invoiced",
        
            "subtotal"              => "total_invoiced",
            "base_subtotal"         => "base_total_invoiced",
        
            "tax_amount"            => "tax_invoiced",
            "base_tax_amount"       => "base_tax_invoiced",

            "shipping_tax_amount"       => "shipping_tax_invoiced",
            "base_shipping_tax_amount"  => "base_shipping_tax_invoiced",
        
            "shipping_amount"       => "shipping_invoiced",
            "base_shipping_amount"  => "base_shipping_invoiced",
       
            "discount_amount"       => "discount_invoiced",
            "base_discount_amount"  => "base_discount_invoiced",
        
            );

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
        // Init Linked Objects 
        $this->Order        = $this->Object->getOrder();
        $this->Products     = $this->Object->getAllItems(); 
        $this->Payment      = $this->Order->getPayment();
        //====================================================================//
        // Iterate All Requested Fields
        //====================================================================//
        $Fields = is_a($this->In, "ArrayObject") ? $this->In->getArrayCopy() : $this->In;        
        foreach ($Fields as $FieldName => $Data) {
            //====================================================================//
            // Write Requested Fields
            $this->setCoreFields($FieldName,$Data);
//            $this->setMainFields($FieldName,$Data);
//            $this->setOrderLineFields($FieldName,$Data);
//            $this->setMetaFields($FieldName,$Data);
        }
        //====================================================================//
        // Create/Update Object if Requiered
//        $InvoiceId = $this->setSaveObject();        
        //====================================================================//
        // Iterate All Requested Fields
        //====================================================================//
        foreach ($this->In as $FieldName => $Data) {
            //====================================================================//
            // Write Requested Fields
//            $this->setAddressFields($FieldName,$Data);
            $this->setProducts($FieldName,$Data);
            $this->setPayments($FieldName,$Data);
        }
        foreach ($this->In as $FieldName => $Data) {
            //====================================================================//
            // Write Requested Fields
            $this->setMainFields($FieldName,$Data);
//            $this->setOrderLineFields($FieldName,$Data);
//            $this->setMetaFields($FieldName,$Data);
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
        // Update Order Totals if Needed
//        $this->setUpdateTotals();
        
        //====================================================================//
        // Update Object if Requiered
        if ( $this->update ) {
            return $this->setSaveObject();        
        } 
        
        return $InvoiceId; 
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
        // Init Object 
        
        //====================================================================//
        // If $id Given => Load Object From DataBase
        //====================================================================//
        if ( !empty($id) )
        {
            $this->Object = Mage::getModel('sales/order_invoice')->load($id);
            if ( $this->Object->getEntityId() != $id )   {
                return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__," Unable to load Customer Invoice (" . $id . ").");
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
            if ( empty($this->In["order_id"]) ) {
                return Splash::Log()->Err("ErrLocalFieldMissing",__CLASS__,__FUNCTION__,"order_id");
            }
            //====================================================================//
            // Create Customer Invoice From Order
            $Order  =   Mage::getModel('sales/order')->load($this->In["order_id"]);
            if(!$Order->canInvoice()) {
//                return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,Mage::helper('core')->__('Cannot create an invoice.'));
            }
            $this->Object = Mage::getModel('sales/service_order', $Order)->prepareInvoice();
//                        // Set Order Initial Status
//            $this->Object->setState(Mage_Sales_Model_Order::STATE_NEW, "pending", 'Just Created by SplashSync Module',True);
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
            // Parent Order Id 
            case 'order_id':
                $OrderId = self::ObjectId_DecodeId( $Data );
                if ( $this->Object->getOrder()->getId() == $OrderId ) {
                    break;
                }                    
                //====================================================================//
                // Load Order Object 
                $NewOrder = Mage::getModel('sales/order')->load($OrderId);
                if ( $NewOrder->getEntityId() !== $OrderId) {
                    break;
                }
                //====================================================================//
                //Update Customer Id 
                $this->Object->setOrder($NewOrder);
                $this->update = True;              
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
            // INVOICE STATUS
            //====================================================================//   
            case 'state':
                $this->setInvoiceStatus($Data);
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
        if ( $FieldName !== "items" ) {
            return True;
        }
        if ( !$this->isSplash() ) {
            Splash::Log()->Deb("You Cannot Edit Invoices Created on Magento");  
            unset($this->In[$FieldName]);            
            return True;
        }        
        //====================================================================//
        // Get Original Order Items List
        $this->Products     =   $this->Object->getAllItems();
        //====================================================================//
        // Verify Lines List & Update if Needed 
        foreach ($Data as $LineData) {
            //====================================================================//
            // Detect Shipping Informations => Product Label === self::$SHIPPING_LABEL
            if ( array_key_exists("sku", $LineData)
                &&  ($LineData["sku"] === SplashInvoice::SHIPPING_LABEL) ) {
                $this->setShipping($LineData); 
                continue;
            }
            //====================================================================//
            // Init Product Informations
            if ( !$this->setProductInitItem() ) {
                break;
            }
            //====================================================================//
            // Update Line Product Descriptions
            $this->setProductDescription($LineData);
            
            //====================================================================//
            // Update Line Product Billing Infos & Totals
            if ( $this->isProductItemModified($LineData) ) {
                $this->setProductQty($LineData);
                $this->setProductPrices($LineData);
                $this->setProductTotals();
                $this->setProductOrderItem();
            }
            
            
            //====================================================================//
            // Save Changes
            if ( $this->ProductUpdate ) {  
                $this->Product->save();
                Splash::Log()->Deb("Order Item Saved");                      
                $this->ProductUpdate = False;
                $this->update = True;
            }        
            
        } 
        //====================================================================//
        // Delete Remaining Lines
        foreach ($this->Products as $Product) {
            //====================================================================//
            // Perform Line Delete
            $Product->delete();
            $this->update = True;
        }        
        //====================================================================//
        // Update Invoice & Order Totals
        $this->collectTotals();
        $this->impactOrderTotals();        
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
    private function setPayments($FieldName,$Data) 
    {
        //====================================================================//
        // Safety Check
        if ( $FieldName !== "payments" ) {
            return True;
        }
        if ( !$this->isSplash() ) {
            Splash::Log()->Deb("You Cannot Edit Invoices Created on Magento");  
            unset($this->In[$FieldName]);            
            return True;
        }        
        
        //====================================================================//
        // Retrieve List of Order Transactions
//        $this->Transactions = Mage::getModel('sales/order_payment_transaction')->getCollection()
//                    ->setOrderFilter($this->Object->getOrder())
//                    ->addPaymentIdFilter($this->Payment->getId())
//                    ->addTxnTypeFilter(Transaction::TYPE_PAYMENT)
//                    ->setOrder('created_at', Varien_Data_Collection::SORT_ORDER_DESC)
//                    ->setOrder('transaction_id', Varien_Data_Collection::SORT_ORDER_DESC);        
        
        //====================================================================//
        // Verify Lines List & Update if Needed 
        foreach ($Data as $LineData) {
            
            if ( !array_key_exists("number", $LineData) ) {
                continue;
            }
            //====================================================================//
            // Init Transaction Informations
            $Transaction = $this->Payment->getTransaction($LineData["number"]);
            
            if ( !$Transaction ) {
                $this->Payment->setTransactionId($LineData["number"]);
                $Transaction = $this->Payment->addTransaction(Transaction::TYPE_PAYMENT,null,True);
                if ( !$Transaction ) {
                    continue;
                }
                $this->TxnUpdate    =   True;
            }
            //====================================================================//
            // Update Transaction Datas
            if ( array_key_exists("date", $LineData) ) {
                //====================================================================//
                // Verify Date Changed
                $CurrentDate = date( SPL_T_DATECAST, Mage::getModel("core/date")->timestamp($Transaction->getCreatedAt()));
                if ( $CurrentDate !== $LineData["date"] ) {
                    $Transaction->setCreatedAt($LineData["date"]);
                    $this->TxnUpdate    =   True;
                }  
            }
            if ( array_key_exists("amount", $LineData) ) {
                //====================================================================//
                // Verify Amount Changed
                $CurrentInfos = $Transaction->getAdditionalInformation(Transaction::RAW_DETAILS);
                if ( !is_array($CurrentInfos) ) {
                    $Transaction->setAdditionalInformation(Transaction::RAW_DETAILS, array("Amount" => $LineData["amount"] ));
                    $this->TxnUpdate    =   True;
                } elseif ( !isset($CurrentInfos["Amount"]) || ($CurrentInfos["Amount"] != $LineData["amount"] ) ) {
                    $CurrentInfos["Amount"] = $LineData["amount"];
                    $Transaction->setAdditionalInformation(Transaction::RAW_DETAILS, $CurrentInfos);
                    $this->TxnUpdate    =   True;
                }  
            }
            //====================================================================//
            // Save Changes
            if ( $this->TxnUpdate ) {  
                $Transaction->save();
                $this->TxnUpdate = False;
                $this->update = True;
            }        
            
        } 
        //====================================================================//
        // Delete Remaining Lines
//        foreach ($this->Products as $Product) {
//            //====================================================================//
//            // Perform Line Delete
//            $Product->delete();
//            $this->update = True;
//        }        
        unset($this->In[$FieldName]);
    } 
    
    /**
     *  @abstract     Save Object after Writting Fields
     */
    private function setSaveObject() 
    {
        //====================================================================//
        // Safety Check
//        if (!$this->Object->getTotalQty()) {
//            return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,Mage::helper('core')->__('Cannot create an invoice without products.'));
//        }

        //====================================================================//
        // Do Generic Magento Object Save & Verify Update was Ok
        $Save = Splash::Local()->ObjectSave($this->Object, $this->update , "Customer Invoice");
        if ( $Save !== False ) {
            $this->update = False;
            Splash::Log()->Deb("Invoice Saved");
        }
        return $Save;
    }    
    
    /**
     *  @abstract     Init Given Order Line Data For Update
     * 
     *  @param        array     $OrderLineData          OrderLine Data Array
     * 
     *  @return         none
     */
    private function setProductInitItem() 
    {
        //====================================================================//
        // Read Next Order Product Line
        $this->Product = array_shift($this->Products);
        //====================================================================//
        // Empty => Create New Line
        if ( !$this->Product ) {
            //====================================================================//
            // Add Attached Order Item
            $OrderItem = Mage::getModel('sales/order_item')
                    ->setOrder($this->Object->getOrder())
                    ->save();
            
            //====================================================================//
            // Create New Order Item
            $this->Product = Mage::getModel('sales/order_invoice_item')
                ->setStoreId($this->Object->getStore()->getStoreId())
                ->setQuoteItemId(NULL)
                ->setParentItemId($this->Object->getEntityId())
                ->setOrder($this->Object->getOrder())
                ->setOrderItem($OrderItem);
            
            //====================================================================//
            // Add Item to Invoice
            $this->Object->addItem($this->Product);
            Splash::Log()->Deb("New Invoice Item Created");            
        }
        
        return True;
    }
    
    /**
     *  @abstract     Add or Update Given Product Order Line Data
     * 
     *  @param        array     $OrderLineData          OrderLine Data Array
     * 
     *  @return         none
     */
    private function setProductDescription($OrderLineData) 
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
            if ( $this->Product->getProductId() !== $ProductId ) {
                //====================================================================//
                // Update Order Item
                $this->Product
                        ->setProductId($Product->getEntityId())
                        ->setProductType($Product->getTypeId())
                        ->setName($Product->getName())
                        ->setSku($Product->getSku());
                $this->ProductUpdate = True;
                Splash::Log()->Deb("Product Invoice Item Updated");            
            }
        //====================================================================//
        // Update Line Without Product Id
        } else {
            if (  array_key_exists("sku", $OrderLineData) 
                &&  ($this->Product->getName() !== $OrderLineData["sku"] ) ) {
                //====================================================================//
                // Update Order Item
                $this->Product
                        ->setProductId(Null)
                        ->setProductType(Null)
                        ->setSku($OrderLineData["sku"]);
                $this->ProductUpdate = True;
            }
            if (  array_key_exists("name", $OrderLineData) 
                &&  ($this->Product->getName() !== $OrderLineData["name"] ) ) {
                //====================================================================//
                // Update Order Item
                $this->Product
                        ->setProductId(Null)
                        ->setProductType(Null)
                        ->setName($OrderLineData["name"]);
                $this->ProductUpdate = True;
                Splash::Log()->Deb("Custom Invoice Item Updated");
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
    private function setProductPrices($OrderLineData) 
    {
        //====================================================================//
        // Compute Current Discount Percent
        $NoDiscountTotal    =    $this->Product->getPriceInclTax() * $this->Product->getQty();
        $this->Product->DiscountPercent = 100 * $this->Product->getDiscountAmount() / $NoDiscountTotal;
        //====================================================================//
        // Update Discount Informations
        if ( array_key_exists("discount_percent", $OrderLineData) ) {
            //====================================================================//
            // Verify Discount Percent Changed
            $DiscountAmount = ( $this->Product->getPriceInclTax() * $this->Product->getQty() * $OrderLineData["discount_percent"] ) / 100;
            if ( !self::Float_Compare($this->Product->DiscountPercent, $DiscountAmount) ) {
                //====================================================================//
                // Update Discount Amount
                $this->Product->DiscountPercent = $OrderLineData["discount_percent"];
                $this->ProductUpdate    = True;
                $this->UpdateTotals     = True;                
            }
        }

        //====================================================================//
        // Update Price Informations
        if ( array_key_exists("unit_price", $OrderLineData) ) {
            $OrderLinePrice     =    $OrderLineData["unit_price"];
            //====================================================================//
            // Verify Price Changed
            if ( $this->Product->getPrice() !== $OrderLinePrice["ht"] ) {
                $this->Product
                    ->setPrice($OrderLinePrice["ht"])
                    ->setBasePrice($OrderLinePrice["ht"])
                    ->setOriginalPrice($OrderLinePrice["ht"]);
                $this->ProductUpdate    = True;
                $this->UpdateTotals     = True;                
            }
            //====================================================================//
            // Verify Price Include Tax Changed
            if ( $this->Product->getPriceInclTax() !== $OrderLinePrice["ttc"] ) {
                $this->Product
                    ->setPriceInclTax($OrderLinePrice["ttc"])
                    ->setBasePriceInclTax($OrderLinePrice["ttc"]);
                $this->ProductUpdate    = True;
                $this->UpdateTotals     = True;                
            }
            //====================================================================//
            // Verify Tax Rate Changed
            if ( $this->Product->getTaxAmount() !== $OrderLinePrice["tax"] ) {
                $this->Product->setTaxAmount($OrderLinePrice["tax"]);
                $this->ProductUpdate    = True;
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
    private function setProductQty($OrderLineData) 
    {
        //====================================================================//
        // Safety Checks
        if ( !$this->isSplash() ) {
            return True;
        }    
        if ( !array_key_exists("qty", $OrderLineData)) {
            return True;
        }
        //====================================================================//
        // No Changes => Exit 
        if ( $this->Product->getQty() == $OrderLineData["qty"] ) {
            return;
        }
        //====================================================================//
        // Update Quantity Informations
        $this->Product->setData("qty",$OrderLineData["qty"]);
        //====================================================================//
        // Update Current Qty from Invoice Item 
        $this->ProductUpdate    = True;
        $this->UpdateTotals     = True;            
        
//        try {
//            //====================================================================//
//            // If Item Linked to Order Item
//            if ( $this->Product->getOrderItem() ) {
//                //====================================================================//
//                // Remove Current Qty from Order Item 
//                $this->Product->getOrderItem()->setQtyInvoiced( $this->Product->getOrderItem()->getQtyInvoiced() - $this->Product->getQty() )->save();
//                
//                //====================================================================//
//                // Set Qty to Invoice & Order Item 
//                $this->Product->setQty($Qty);
//                $this->Product->register();
//            //====================================================================//
//            // If Item NOT Linked to Order Item
//            } else {
//                $this->Product->setData("qty",$Qty);
//            }
//            //====================================================================//
//            // Update Current Qty from Invoice Item 
//            $this->ProductUpdate    = True;
//            $this->UpdateTotals     = True;                
//        } catch (Exception $exc) {
//             Splash::Log()->War("ErrLocalTpl",__CLASS__,__FUNCTION__,$exc->getMessage());
//        }
    
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
            // Verify TTC Price Changed
            if ( $this->Object->getShippingInclTax() !== $OrderLinePrice["ttc"] ) {
                $this->Object->setShippingInclTax($OrderLinePrice["ttc"]);
                $this->update           = True;
                $this->UpdateTotals     = True;                
            }
            //====================================================================//
            // Verify Tax Amount Changed
            if ( $this->Object->getShippingTaxAmount() !== $OrderLinePrice["tax"] ) {
                $this->Object->setShippingTaxAmount($OrderLinePrice["tax"]);
                $this->update           = True;
                $this->UpdateTotals     = True;                
            }
        }
    }
    
    /**
     *  @abstract     Add or Update Given Order Line Informations
     * 
     *  @return         none
     */
    private function setProductTotals() 
    {
        if ( !$this->UpdateTotals ) {
            return;
        }
        
        //====================================================================//
        // Update Row Total
        $TotalHt     =   $this->Product->getPrice() * $this->Product->getQty();
        $TaxAmount      =   $TotalHt * $this->Product->getOrderItem()->getTaxPercent() / 100;
        $TotalTtc       =   $TotalHt + $TaxAmount;
        $DiscountAmount =   ( $TotalTtc * $this->Product->DiscountPercent ) / 100;
        //====================================================================//
        // Verify Total Changed
        if ( $this->Product->getRowTotal() !== $TotalHt ) {
            $this->Product
                    
                ->setDiscountAmount($DiscountAmount)
                ->setBaseDiscountAmount($DiscountAmount)
                    
                ->setTaxAmount($TaxAmount)
                ->setBaseTaxAmount($TaxAmount)

                ->setRowTotal($TotalHt)
                ->setBaseRowTotal($TotalHt);
            
            $this->ProductUpdate = True;
            Splash::Log()->Deb("Order Item Total Updated");  
        }
    }  

    /**
     *  @abstract     Impat Invoice Item's Order Item Informations
     * 
     *  @return         none
     */
    private function setProductOrderItem() 
    {
        //====================================================================//
        // Safety Checks
        if ( !$this->isSplash() || $this->Object->getState() != Mage_Sales_Model_Order_Invoice::STATE_OPEN ) {
            return $this;
        }          

        //====================================================================//
        // Get Order Item
        $OrderItem = $this->Product->getOrderItem();
        //====================================================================//
        // Compute Object Changes
        $Changes    =   Splash::Local()->ObjectChanges($this->Product);
        //====================================================================//
        // Impact Data Changes to Order Item
        foreach (self::$ITEM_FILTERS as $InvoiceKey => $OrderKey) {
            //====================================================================//
            // Impact Data Changes
            if ($Changes[$InvoiceKey]) {
                $OrderItem->setData($OrderKey, $OrderItem->getData($OrderKey) + $Changes[$InvoiceKey]);
            } 
        }
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
        return ( $this->Object->getOrder()->getExtOrderId() === SplashInvoice::SPLASH_LABEL )? True:False;
    }     
    
    /**
     *  @abstract     Check if Qty / Price Update is Needed for this Product Line 
     * 
     *  @param        array     $OrderLineData          OrderLine Data Array
     * 
     *  @return         none
     */
    private function isProductItemModified($OrderLineData) 
    {
        //====================================================================//
        // Compare Price Informations
        if ( array_key_exists("unit_price", $OrderLineData) ) {
            $OrderLinePrice     =    $OrderLineData["unit_price"];
            //====================================================================//
            // Verify Price Changed
            if ( $this->Product->getPrice() !== $OrderLinePrice["ht"] ) {
                return True;                
            }
            //====================================================================//
            // Verify Price Include Tax Changed
            if ( $this->Product->getPriceInclTax() !== $OrderLinePrice["ttc"] ) {
                return True;                
            }
            //====================================================================//
            // Verify Tax Rate Changed
            if ( $this->Product->getTaxAmount() !== $OrderLinePrice["tax"] ) {
                return True;                
            }
        }
        
        //====================================================================//
        // Compare Discount Informations
        if ( array_key_exists("discount_percent", $OrderLineData) ) {
            //====================================================================//
            // Verify Discount Percent Changed
            $DiscountAmount = ( $this->Product->getPriceInclTax() * $this->Product->getQty() * $OrderLineData["discount_percent"] ) / 100;
            if ( !self::Float_Compare($this->Product->getDiscountAmount(), $DiscountAmount) ) {
                return True;                
            }
        }
        
        //====================================================================//
        // Compare Qty Informations
        if ( !array_key_exists("qty", $OrderLineData)) {
            if ( $this->Product->getQty() != $OrderLineData["qty"] ) {
                return True;   
            }
        }        
        return False;   
    }
    
    /**
     *   @abstract   Update Invoice Status
     * 
     *   @param      string     $Status         Schema.org Order Status String
     * 
     *   @return     bool 
     */
    private function setInvoiceStatus($Status) {
        //====================================================================//
        // Safety Checks
        if ( !$this->isSplash() ) {
            Splash::Log()->Deb("You Cannot Change Status of Invoice Created on Magento");  
            return True;
        }        

        try {                
            //====================================================================//
            // Generate Magento Invoice State from Schema.org orderStatus
            switch ($Status)
            {

                //====================================================================//
                // Invoice Cancelled
                case "PaymentCanceled":
                    if ( !$this->Object->isCanceled() && $this->Object->canCancel() ) {
                        $this->Object->cancel();
                        $this->update = True;
                    }
                    break;
                //====================================================================//
                // Invoice Completed
                case "PaymentComplete":
                    if ($this->Object->getState() != Mage_Sales_Model_Order_Invoice::STATE_PAID) {
                        //====================================================================//
                        // Update Order Paid Totals
                        $this->Object->pay();
                        $this->Object->getOrder()->save();
                        $this->update = True;
                    }
                    break;
                case "PaymentDue":
                case "PaymentDeclined":
                case "PaymentPastDue":
                    if ($this->Object->getState() == Mage_Sales_Model_Order_Invoice::STATE_OPEN) {
                        break;
                    }
                    //====================================================================//
                    // Update Order Paid Totals
                    $this->Order->setTotalPaid(
                        $this->Order->getTotalPaid() - $this->Object->getGrandTotal()
                    );
                    $this->Order->setBaseTotalPaid(
                        $this->Order->getBaseTotalPaid() - $this->Object->getBaseGrandTotal()
                    );
                    $this->Order->save();
                    
                    $this->Object->setState(Mage_Sales_Model_Order_Invoice::STATE_OPEN);
                    $this->update = True;
                    
                    break;
            }        
        } catch (Exception $exc) {
            Splash::Log()->War($exc->getMessage());  
        }
        return True;
    }     
    
    /**
     * Collect invoice subtotal
     */
    public function collectTotals()
    {
        //====================================================================//
        // Safety Checks
        if ( !$this->isSplash() ) {
            return $this;
        }          
        
        $qty                    = 0;
        
        $subtotal               = 0;
        $baseSubtotal           = 0;
        $subtotalInclTax        = 0;
        $baseSubtotalInclTax    = 0;
        
        $Discount               = 0;
        $baseDiscount           = 0;

        $Tax                    = 0;
        $baseTax                = 0;
        
        $totalWeeeDiscount      = 0;
        $totalBaseWeeeDiscount  = 0;

        /**
         * Sum all Objects Data.
         */
        foreach ($this->Object->getAllItems() as $item) {
            if ($item->getOrderItem()->isDummy()) {
                continue;
            }

            $qty                    += $item->getQty();

            $subtotal               += $item->getRowTotal();
            $baseSubtotal           += $item->getBaseRowTotal();
            $subtotalInclTax        += $item->getRowTotalInclTax();
            $baseSubtotalInclTax    += $item->getBaseRowTotalInclTax();
            $totalWeeeDiscount      += $item->getOrderItem()->getDiscountAppliedForWeeeTax();
            $totalBaseWeeeDiscount  += $item->getOrderItem()->getBaseDiscountAppliedForWeeeTax();
            
            $Discount               += $item->getDiscountAmount();
            $baseDiscount           += $item->getBaseDiscountAmount();
            
            $Tax                    += $item->getTaxAmount();
            $baseTax                += $item->getBaseTaxAmount();
            
        }

        /**
         * Add shipping
         */
        $Tax                        += $this->Object->getShippingTaxAmount();
        $baseTax                    += $this->Object->getBaseShippingTaxAmount();
        
//        $includeShippingTax = true;
//        foreach ($invoice->getOrder()->getInvoiceCollection() as $previousInvoice) {
//            if ($previousInvoice->getShippingAmount() && !$previousInvoice->isCanceled()) {
//                $includeShippingTax = false;
//                break;
//            }
//        }


        /**
         * Set Totals
         */
        $this->Object->setTotalQty($qty);
        
        $this->Object->setDiscountAmount($Discount);
        $this->Object->setBaseDiscountAmount($baseDiscount);

        $this->Object->setTaxAmount($Tax);
        $this->Object->setBaseTaxAmount($baseTax);

        $this->Object->setSubtotal($subtotal);
        $this->Object->setBaseSubtotal($baseSubtotal);
        $this->Object->setSubtotalInclTax($subtotalInclTax);
        $this->Object->setBaseSubtotalInclTax($baseSubtotalInclTax);

        $this->Object->setGrandTotal($subtotal + $Tax - $Discount + $this->Object->getShippingAmount() );
        $this->Object->setBaseGrandTotal($baseSubtotal + $baseTax - $baseDiscount  + $this->Object->getBaseShippingAmount());
        return $this;
    }    
    
    /**
     * Impact Changes to Order
     */
    public function impactOrderTotals() {
        
        //====================================================================//
        // Safety Checks
        if ( !$this->isSplash() || $this->Object->getState() != Mage_Sales_Model_Order_Invoice::STATE_OPEN ) {
            return $this;
        }          
        //====================================================================//
        // Get Order 
        $Order      =   $this->Object->getOrder();
        $Changes    =   Splash::Local()->ObjectChanges($this->Object);
        //====================================================================//
        // Impact Data Changes to Order
        foreach (self::$ORDER_FILTERS as $InvoiceKey => $OrderKey) {
            //====================================================================//
            // Impact Data Changes
            if ($Changes[$InvoiceKey]) {
                $Order->setData($OrderKey, $Order->getData($OrderKey) + $Changes[$InvoiceKey]);
                $Save = True;
            } 
        }
        //====================================================================//
        // Save Changes to Order
        if ($Save) {
            $Order->save();
        }
    }
        
}



?>
