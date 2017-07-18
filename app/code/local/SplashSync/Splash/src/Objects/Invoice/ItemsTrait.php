<?php
/*
 * Copyright (C) 2017   Splash Sync       <contact@splashsync.com>
 * 
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
*/

namespace Splash\Local\Objects\Invoice;

use Mage;

/**
 * @abstract    Magento 1 Invoice Items Fields Access
 */
trait ItemsTrait {
    
    static               $SHIPPING_LABEL             =   "__Shipping__";
    
    /**
    *   @abstract     Build Address Fields using FieldFactory
    */
    private function buildProductsLineFields() {
        
        $ListId =   "items";
        
//        $ListName = Mage::helper('sales')->__('Items') . " => " ;
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
                ->Association("name@items","qty@items","unit_price@items");        
        
        //====================================================================//
        // Order Line Description
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("name")
                ->InList("items")
//                ->ReadOnly()
                ->Name( $ListName . Mage::helper('sales')->__('Description Message'))
                ->MicroData("http://schema.org/partOfInvoice","description")        
                ->Association("name@items","qty@items","unit_price@items");       

        //====================================================================//
        // Order Line Product Identifier
        $this->FieldsFactory()->Create(self::Objects()->Encode( "Product" , SPL_T_ID))        
                ->Identifier("product_id")
                ->InList("items")
//                ->ReadOnly()
                ->Name( $ListName . Mage::helper('sales')->__('Product'))
                ->MicroData("http://schema.org/Product","productID")
                ->Association("name@items","qty@items","unit_price@items");     
//                ->NotTested();        

        //====================================================================//
        // Order Line Quantity
        $this->FieldsFactory()->Create(SPL_T_INT)        
                ->Identifier("qty")
//                ->isRequired()
                ->InList("items")
                ->Name( $ListName . Mage::helper('sales')->__('Qty Invoiced'))                
                ->MicroData("http://schema.org/QuantitativeValue","value")        
                ->Association("name@items","qty@items","unit_price@items"); 

        //====================================================================//
        // Order Line Discount
        $this->FieldsFactory()->Create(SPL_T_DOUBLE)        
                ->Identifier("discount_percent")
                ->InList("items")
//                ->ReadOnly()
//                ->WriteOnly()
                ->Name( $ListName . Mage::helper('sales')->__('Discount (%s)'))                
                ->MicroData("http://schema.org/Order","discount")
                ->Association("name@items","qty@items","unit_price@items"); 

        //====================================================================//
        // Order Line Unit Price
        $this->FieldsFactory()->Create(SPL_T_PRICE)        
                ->Identifier("unit_price")
//                ->isRequired()
//                ->ReadOnly()
                ->InList("items")
                ->Name( $ListName . Mage::helper('sales')->__('Price'))     
                ->MicroData("http://schema.org/PriceSpecification","price")        
                ->Association("name@items","qty@items","unit_price@items");     

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
        // Check if List field & Init List Array
        $FieldId = self::Lists()->InitOutput( $this->Out, "items", $FieldName );
        if ( !$FieldId ) {
            return;
        }            
        
        //====================================================================//
        // READ Fields
        switch ($FieldId)
        {
            //====================================================================//
            // Order Line Direct Reading Data          
            case 'sku':
                $Value = static::$SHIPPING_LABEL;
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
                    $Value = self::Prices()->Encode(
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
        self::Lists()->Insert( $this->Out, "items",$FieldName,count($this->Products),$Value);  
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
        // Check if List field & Init List Array
        $FieldId = self::Lists()->InitOutput( $this->Out, "items", $FieldName );
        if ( !$FieldId ) {
            return;
        }            
        //====================================================================//
        // Fill List with Data
        foreach ($this->Products as $Index => $Product) {
            
            //====================================================================//
            // READ Fields
            switch ($FieldId)
            {
                //====================================================================//
                // Invoice Line Direct Reading Data
                case 'sku':
                case 'name':
                    $Value = $Product->getData($FieldId);
                    break;
                case 'discount_percent':
                    if ( $Product->getPriceInclTax() && $Product->getQty() ) {
                        $Value = (double) 100 * $Product->getDiscountAmount() / ($Product->getPriceInclTax() * $Product->getQty());
                    } else {
                        $Value = 0;
                    }
                    break;
                case 'qty':
                    $Value = (int) $Product->getData($FieldId);
                    break;
                //====================================================================//
                // Invoice Line Product Id
                case 'product_id':
                    $Value = self::Objects()->Encode( "Product" , $Product->getData($FieldId) );
                    break;
                //====================================================================//
                // Invoice Line Unit Price
                case 'unit_price':
                    //====================================================================//
                    // Read Current Currency Code
                    $CurrencyCode   =   $this->Object->getOrderCurrencyCode();
                    //====================================================================//
                    // Build Price Array
                    $Value = self::Prices()->Encode(
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
            self::Lists()->Insert( $this->Out, "items",$FieldName,$Index,$Value);              
        }
        unset($this->In[$Key]);
    }    
    
    
//    /**
//     *  @abstract     Write Given Fields
//     * 
//     *  @param        string    $FieldName              Field Identifier / Name
//     *  @param        mixed     $Data                   Field Data
//     * 
//     *  @return         none
//     */
//    private function setProducts($FieldName,$Data) 
//    {
//        //====================================================================//
//        // Safety Check
//        if ( $FieldName !== "items" ) {
//            return True;
//        }
//        if ( !$this->isSplash() ) {
//            Splash::Log()->Deb("You Cannot Edit Invoices Created on Magento");  
//            unset($this->In[$FieldName]);            
//            return True;
//        }        
//        //====================================================================//
//        // Get Original Order Items List
//        $this->Products     =   $this->Object->getAllItems();
//        //====================================================================//
//        // Verify Lines List & Update if Needed 
//        foreach ($Data as $LineData) {
//            //====================================================================//
//            // Detect Shipping Informations => Product Label === self::$SHIPPING_LABEL
//            if ( array_key_exists("sku", $LineData)
//                &&  ($LineData["sku"] === SplashInvoice::SHIPPING_LABEL) ) {
//                $this->setShipping($LineData); 
//                continue;
//            }
//            //====================================================================//
//            // Init Product Informations
//            if ( !$this->setProductInitItem() ) {
//                break;
//            }
//            //====================================================================//
//            // Update Line Product Descriptions
//            $this->setProductDescription($LineData);
//            
//            //====================================================================//
//            // Update Line Product Billing Infos & Totals
//            if ( $this->isProductItemModified($LineData) ) {
//                $this->setProductQty($LineData);
//                $this->setProductPrices($LineData);
//                $this->setProductTotals();
//                $this->setProductOrderItem();
//            }
//            
//            
//            //====================================================================//
//            // Save Changes
//            if ( $this->ProductUpdate ) {  
//                $this->Product->save();
//                Splash::Log()->Deb("Order Item Saved");                      
//                $this->ProductUpdate = False;
//                $this->update = True;
//            }        
//            
//        } 
//        //====================================================================//
//        // Delete Remaining Lines
//        foreach ($this->Products as $Product) {
//            //====================================================================//
//            // Perform Line Delete
//            $Product->delete();
//            $this->update = True;
//        }        
//        //====================================================================//
//        // Update Invoice & Order Totals
//        $this->collectTotals();
//        $this->impactOrderTotals();        
//        unset($this->In[$FieldName]);
//    }     
//    
//    /**
//     *  @abstract     Init Given Order Line Data For Update
//     * 
//     *  @param        array     $OrderLineData          OrderLine Data Array
//     * 
//     *  @return         none
//     */
//    private function setProductInitItem() 
//    {
//        //====================================================================//
//        // Read Next Order Product Line
//        $this->Product = array_shift($this->Products);
//        //====================================================================//
//        // Empty => Create New Line
//        if ( !$this->Product ) {
//            //====================================================================//
//            // Add Attached Order Item
//            $OrderItem = Mage::getModel('sales/order_item')
//                    ->setOrder($this->Object->getOrder())
//                    ->save();
//            
//            //====================================================================//
//            // Create New Order Item
//            $this->Product = Mage::getModel('sales/order_invoice_item')
//                ->setStoreId($this->Object->getStore()->getStoreId())
//                ->setQuoteItemId(NULL)
//                ->setParentItemId($this->Object->getEntityId())
//                ->setOrder($this->Object->getOrder())
//                ->setOrderItem($OrderItem);
//            
//            //====================================================================//
//            // Add Item to Invoice
//            $this->Object->addItem($this->Product);
//            Splash::Log()->Deb("New Invoice Item Created");            
//        }
//        
//        return True;
//    }
//    
//    /**
//     *  @abstract     Add or Update Given Product Order Line Data
//     * 
//     *  @param        array     $OrderLineData          OrderLine Data Array
//     * 
//     *  @return         none
//     */
//    private function setProductDescription($OrderLineData) 
//    {
//        //====================================================================//
//        // Detect & Verify Product Id 
//        if ( array_key_exists("product_id", $OrderLineData) ) {
//            $ProductId  = $this->ObjectId_DecodeId($OrderLineData["product_id"]);
//            $Product    = Mage::getModel('catalog/product')
//                    ->load($ProductId);
//            //====================================================================//
//            // Verify Product Id Is Valid
//            if ( $Product->getEntityId() !== $ProductId ) {
//                $Product = Null;
//            }
//        } else {
//            $Product = Null;
//        }
//        
//        //====================================================================//
//        // If Valid Product Given => Update Product Informations
//        if ( $Product ) {
//            //====================================================================//
//            // Verify Product Id Changed
//            if ( $this->Product->getProductId() !== $ProductId ) {
//                //====================================================================//
//                // Update Order Item
//                $this->Product
//                        ->setProductId($Product->getEntityId())
//                        ->setProductType($Product->getTypeId())
//                        ->setName($Product->getName())
//                        ->setSku($Product->getSku());
//                $this->ProductUpdate = True;
//                Splash::Log()->Deb("Product Invoice Item Updated");            
//            }
//        //====================================================================//
//        // Update Line Without Product Id
//        } else {
//            if (  array_key_exists("sku", $OrderLineData) 
//                &&  ($this->Product->getName() !== $OrderLineData["sku"] ) ) {
//                //====================================================================//
//                // Update Order Item
//                $this->Product
//                        ->setProductId(Null)
//                        ->setProductType(Null)
//                        ->setSku($OrderLineData["sku"]);
//                $this->ProductUpdate = True;
//            }
//            if (  array_key_exists("name", $OrderLineData) 
//                &&  ($this->Product->getName() !== $OrderLineData["name"] ) ) {
//                //====================================================================//
//                // Update Order Item
//                $this->Product
//                        ->setProductId(Null)
//                        ->setProductType(Null)
//                        ->setName($OrderLineData["name"]);
//                $this->ProductUpdate = True;
//                Splash::Log()->Deb("Custom Invoice Item Updated");
//            }
//        }
//    }
//    
//    /**
//     *  @abstract     Add or Update Given Order Line Informations
//     * 
//     *  @param        array     $OrderLineData          OrderLine Data Array
//     * 
//     *  @return         none
//     */
//    private function setProductPrices($OrderLineData) 
//    {
//        //====================================================================//
//        // Compute Current Discount Percent
//        $NoDiscountTotal    =    $this->Product->getPriceInclTax() * $this->Product->getQty();
//        $this->Product->DiscountPercent = 100 * $this->Product->getDiscountAmount() / $NoDiscountTotal;
//        //====================================================================//
//        // Update Discount Informations
//        if ( array_key_exists("discount_percent", $OrderLineData) ) {
//            //====================================================================//
//            // Verify Discount Percent Changed
//            $DiscountAmount = ( $this->Product->getPriceInclTax() * $this->Product->getQty() * $OrderLineData["discount_percent"] ) / 100;
//            if ( !self::Float_Compare($this->Product->DiscountPercent, $DiscountAmount) ) {
//                //====================================================================//
//                // Update Discount Amount
//                $this->Product->DiscountPercent = $OrderLineData["discount_percent"];
//                $this->ProductUpdate    = True;
//                $this->UpdateTotals     = True;                
//            }
//        }
//
//        //====================================================================//
//        // Update Price Informations
//        if ( array_key_exists("unit_price", $OrderLineData) ) {
//            $OrderLinePrice     =    $OrderLineData["unit_price"];
//            //====================================================================//
//            // Verify Price Changed
//            if ( $this->Product->getPrice() !== $OrderLinePrice["ht"] ) {
//                $this->Product
//                    ->setPrice($OrderLinePrice["ht"])
//                    ->setBasePrice($OrderLinePrice["ht"])
//                    ->setOriginalPrice($OrderLinePrice["ht"]);
//                $this->ProductUpdate    = True;
//                $this->UpdateTotals     = True;                
//            }
//            //====================================================================//
//            // Verify Price Include Tax Changed
//            if ( $this->Product->getPriceInclTax() !== $OrderLinePrice["ttc"] ) {
//                $this->Product
//                    ->setPriceInclTax($OrderLinePrice["ttc"])
//                    ->setBasePriceInclTax($OrderLinePrice["ttc"]);
//                $this->ProductUpdate    = True;
//                $this->UpdateTotals     = True;                
//            }
//            //====================================================================//
//            // Verify Tax Rate Changed
//            if ( $this->Product->getTaxAmount() !== $OrderLinePrice["tax"] ) {
//                $this->Product->setTaxAmount($OrderLinePrice["tax"]);
//                $this->ProductUpdate    = True;
//                $this->UpdateTotals     = True;                
//            }
//        }
//        
//    }
//
//    /**
//     *  @abstract     Add or Update Given Order Line Informations
//     * 
//     *  @param        array     $OrderLineData          OrderLine Data Array     
//     * 
//     *  @return         none
//     */
//    private function setProductQty($OrderLineData) 
//    {
//        //====================================================================//
//        // Safety Checks
//        if ( !$this->isSplash() ) {
//            return True;
//        }    
//        if ( !array_key_exists("qty", $OrderLineData)) {
//            return True;
//        }
//        //====================================================================//
//        // No Changes => Exit 
//        if ( $this->Product->getQty() == $OrderLineData["qty"] ) {
//            return;
//        }
//        //====================================================================//
//        // Update Quantity Informations
//        $this->Product->setData("qty",$OrderLineData["qty"]);
//        //====================================================================//
//        // Update Current Qty from Invoice Item 
//        $this->ProductUpdate    = True;
//        $this->UpdateTotals     = True;            
//        
////        try {
////            //====================================================================//
////            // If Item Linked to Order Item
////            if ( $this->Product->getOrderItem() ) {
////                //====================================================================//
////                // Remove Current Qty from Order Item 
////                $this->Product->getOrderItem()->setQtyInvoiced( $this->Product->getOrderItem()->getQtyInvoiced() - $this->Product->getQty() )->save();
////                
////                //====================================================================//
////                // Set Qty to Invoice & Order Item 
////                $this->Product->setQty($Qty);
////                $this->Product->register();
////            //====================================================================//
////            // If Item NOT Linked to Order Item
////            } else {
////                $this->Product->setData("qty",$Qty);
////            }
////            //====================================================================//
////            // Update Current Qty from Invoice Item 
////            $this->ProductUpdate    = True;
////            $this->UpdateTotals     = True;                
////        } catch (Exception $exc) {
////             Splash::Log()->War("ErrLocalTpl",__CLASS__,__FUNCTION__,$exc->getMessage());
////        }
//    
//    }    
//    
//    /**
//     *  @abstract     Add or Update Given Order Shipping Informations
//     * 
//     *  @param        array     $OrderLineData          OrderLine Data Array
//     * 
//     *  @return         none
//     */
//    private function setShipping($OrderLineData) 
//    {
//        //====================================================================//
//        // Update Price Informations
//        if ( array_key_exists("unit_price", $OrderLineData) ) {
//            $OrderLinePrice     =    $OrderLineData["unit_price"];
//            //====================================================================//
//            // Verify HT Price Changed
//            if ( $this->Object->getShippingAmount() !== $OrderLinePrice["ht"] ) {
//                $this->Object
//                    ->setShippingAmount($OrderLinePrice["ht"])
//                    ->setBaseShippingAmount($OrderLinePrice["ht"]);
//                $this->update           = True;
//                $this->UpdateTotals     = True;                
//            }
//            //====================================================================//
//            // Verify TTC Price Changed
//            if ( $this->Object->getShippingInclTax() !== $OrderLinePrice["ttc"] ) {
//                $this->Object->setShippingInclTax($OrderLinePrice["ttc"]);
//                $this->update           = True;
//                $this->UpdateTotals     = True;                
//            }
//            //====================================================================//
//            // Verify Tax Amount Changed
//            if ( $this->Object->getShippingTaxAmount() !== $OrderLinePrice["tax"] ) {
//                $this->Object->setShippingTaxAmount($OrderLinePrice["tax"]);
//                $this->update           = True;
//                $this->UpdateTotals     = True;                
//            }
//        }
//    }
//    
//    /**
//     *  @abstract     Add or Update Given Order Line Informations
//     * 
//     *  @return         none
//     */
//    private function setProductTotals() 
//    {
//        if ( !$this->UpdateTotals ) {
//            return;
//        }
//        
//        //====================================================================//
//        // Update Row Total
//        $TotalHt     =   $this->Product->getPrice() * $this->Product->getQty();
//        $TaxAmount      =   $TotalHt * $this->Product->getOrderItem()->getTaxPercent() / 100;
//        $TotalTtc       =   $TotalHt + $TaxAmount;
//        $DiscountAmount =   ( $TotalTtc * $this->Product->DiscountPercent ) / 100;
//        //====================================================================//
//        // Verify Total Changed
//        if ( $this->Product->getRowTotal() !== $TotalHt ) {
//            $this->Product
//                    
//                ->setDiscountAmount($DiscountAmount)
//                ->setBaseDiscountAmount($DiscountAmount)
//                    
//                ->setTaxAmount($TaxAmount)
//                ->setBaseTaxAmount($TaxAmount)
//
//                ->setRowTotal($TotalHt)
//                ->setBaseRowTotal($TotalHt);
//            
//            $this->ProductUpdate = True;
//            Splash::Log()->Deb("Order Item Total Updated");  
//        }
//    }  
//
//    /**
//     *  @abstract     Impat Invoice Item's Order Item Informations
//     * 
//     *  @return         none
//     */
//    private function setProductOrderItem() 
//    {
//        //====================================================================//
//        // Safety Checks
//        if ( !$this->isSplash() || $this->Object->getState() != Mage_Sales_Model_Order_Invoice::STATE_OPEN ) {
//            return $this;
//        }          
//
//        //====================================================================//
//        // Get Order Item
//        $OrderItem = $this->Product->getOrderItem();
//        //====================================================================//
//        // Compute Object Changes
//        $Changes    =   Splash::Local()->ObjectChanges($this->Product);
//        //====================================================================//
//        // Impact Data Changes to Order Item
//        foreach (self::$ITEM_FILTERS as $InvoiceKey => $OrderKey) {
//            //====================================================================//
//            // Impact Data Changes
//            if ($Changes[$InvoiceKey]) {
//                $OrderItem->setData($OrderKey, $OrderItem->getData($OrderKey) + $Changes[$InvoiceKey]);
//            } 
//        }
//    }      
    
}
