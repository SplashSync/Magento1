<?php
/**
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

namespace Splash\Local\Objects\Order;

// Splash Namespaces
use Splash\Core\SplashCore      as Splash;

// Magento Namespaces
use Mage;
use Mage_Sales_Model_Order      as MageOrder;

/**
 * @abstract    Magento 1 Order Items Fields Access
 */
trait ItemsTrait
{
    private $OrderItems         =   array();
    private $OrderItem          =   null;
    private $UpdateTotals       =   false;
    private $OrderItemUpdate    =   false;
    
    protected static $SHIPPING_LABEL             =   "__Shipping__";    
    protected static $MONEYPOINTS_LABEL          =   "__Money_For_Points__";
    
    /**
    *   @abstract     Build Address Fields using FieldFactory
    */
    private function buildItemsFields()
    {
        
        $ListName = "";
        
        //====================================================================//
        // Order Line Label
        $this->fieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("sku")
                ->InList("lines")
                ->Name($ListName . "Label")
                ->MicroData("http://schema.org/partOfInvoice", "name")
                ->Association("name@lines", "qty_ordered@lines", "unit_price@lines");
        
        //====================================================================//
        // Order Line Description
        $this->fieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("name")
                ->InList("lines")
                ->Name($ListName . "Description")
                ->MicroData("http://schema.org/partOfInvoice", "description")
                ->Association("name@lines", "qty_ordered@lines", "unit_price@lines");

        //====================================================================//
        // Order Line Product Identifier
        $this->fieldsFactory()->Create(self::objects()->Encode("Product", SPL_T_ID))
                ->Identifier("product_id")
                ->InList("lines")
                ->Name($ListName . "Product ID")
                ->MicroData("http://schema.org/Product", "productID")
                ->Association("qty_ordered@lines", "unit_price@lines")
                ->isNotTested()
                ;

        //====================================================================//
        // Order Line Quantity
        $this->fieldsFactory()->Create(SPL_T_INT)
                ->Identifier("qty_ordered")
                ->InList("lines")
                ->Name($ListName . "Quantity")
                ->MicroData("http://schema.org/QuantitativeValue", "value")
                ->Association("name@lines", "qty_ordered@lines", "unit_price@lines");
            $this->fieldsFactory()->isRequired();
        
        //====================================================================//
        // Order Line Discount
        $this->fieldsFactory()->Create(SPL_T_DOUBLE)
                ->Identifier("discount_percent")
                ->InList("lines")
                ->Name($ListName . "Discount (%)")
                ->MicroData("http://schema.org/Order", "discount")
                ->Association("name@lines", "qty_ordered@lines", "unit_price@lines");

        //====================================================================//
        // Order Line Unit Price
        $this->fieldsFactory()->Create(SPL_T_PRICE)
                ->Identifier("unit_price")
                ->InList("lines")
                ->Name($ListName . "Price")
                ->MicroData("http://schema.org/PriceSpecification", "price")
                ->Association("name@lines", "qty_ordered@lines", "unit_price@lines");
    }

    /**
     *  @abstract     Read requested Field
     *
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     *
     *  @return         none
     */
    private function getItemsFields($Key, $FieldName)
    {
        // Check if List field & Init List Array
        $FieldId = self::lists()->InitOutput($this->Out, "lines", $FieldName);
        if (!$FieldId) {
            return;
        }
        //====================================================================//
        // Verify List is Not Empty
        $Products = $this->Object->getAllItems();
        if (!is_array($Products)) {
            return true;
        }
        
        //====================================================================//
        // Fill List with Data
        foreach ($Products as $Index => $Product) {
            //====================================================================//
            // Do Fill List with Data
            self::lists()->Insert($this->Out, "lines", $FieldName, $Index, $this->getItemsValues($Product, $FieldId));
        }
        unset($this->In[$Key]);
    }
    
    /**
     *  @abstract     Read requested Item Values
     *  @return       mixed
     */
    private function getItemsValues($Product, $FieldId)
    {
        switch ($FieldId) {
            //====================================================================//
            // Order Line Direct Reading Data
            case 'sku':
            case 'name':
                return $Product->getData($FieldId);
                
            //====================================================================//
            // In Bundles Advanced Prices Mode, we Take Discount from Parent Bundle
            case 'discount_percent':
                return $this->isProductInBundlePriceMode($Product) 
                    ? $this->getItemsDiscount($Product->getParentItem())
                    : $this->getItemsDiscount($Product);
                
            //====================================================================//
            // Qty Always 0 for Bundles, Else Normal Reading
            case 'qty_ordered':            
                return $Product->getHasChildren() ? 0 : (int) $Product->getData($FieldId);
                
            //====================================================================//
            // Order Line Product Id
            case 'product_id':
                return self::objects()->encode("Product", $Product->getData($FieldId));
                
            //====================================================================//
            // Order Line Unit Price
            case 'unit_price':
                return $this->getItemsPrice($Product);
                
            default:
                return Null;
        }
        
        return Null;
    }    
    
    /**
     *  @abstract     Check If Item is a Bundle in Bundle Componants Price Mode 
     *  @return       bool
     */
    private function isProductInBundlePriceMode($Product)
    {
        //====================================================================//
        // If Bundle Prices Mode NOT Enabled
        if( !Splash::local()->isBundleComponantsPricesMode() ) {
            return false;
        }
        //====================================================================//
        // If Product has Parent => is a Bundle Componant
        if( !empty($Product->getParentItemId()) ) {
            return true;
        }
        return false;
    }
    
    /**
     *  @abstract     Read requested Item Discount Pourcentile
     *  @return       mixed
     */    
    private function getItemsDiscount($Product)
    {
        if (!empty($Product->getData('discount_percent'))) {
            return $Product->getData('discount_percent');
        } elseif ($Product->getPriceInclTax() && $Product->getQty()) {
            return (double) 100 * $Product->getDiscountAmount() / ($Product->getPriceInclTax() * $Product->getQty());
        }
        return 0;
    }
    
    /**
     *  @abstract     Read Order Product Price
     */        
    private function getItemsPrice($Product)
    {
        //====================================================================//
        // Read Item Regular Price 
        $HtPrice    =   (double) $Product->getPrice();
        $TtcPrice   =   null;
        $ItemTax    =   (double) $Product->getTaxPercent();
        //====================================================================//
        // Collect Item Price at Bundle Options Level
        if ($this->isProductInBundlePriceMode($Product)) {
            $HtPrice    =   null;
            $TtcPrice   =   (double) $this->getBundleItemOriginPrice($Product);
            $ItemTax    =   (double) $Product->getParentItem()->getTaxPercent();
        }
        //====================================================================//
        // Read Current Currency Code
        $CurrencyCode   =   $this->Object->getOrderCurrencyCode();
        //====================================================================//
        // Build Price Array
        return self::prices()->encode(
            $HtPrice,
            $ItemTax,
            $TtcPrice,
            $CurrencyCode,
            Mage::app()->getLocale()->currency($CurrencyCode)->getSymbol(),
            Mage::app()->getLocale()->currency($CurrencyCode)->getName()
        );
    }
    
    /**
     *  @abstract     Read Order Bundled Product Price
     */        
    public static function getBundleItemOriginPrice($Product)
    {
        $ProductOptions  =   $Product->getProductOptions();
        //====================================================================//
        // Check Bundle Product Options are Here
        if (!isset($ProductOptions["bundle_selection_attributes"]) || !is_scalar($ProductOptions["bundle_selection_attributes"])) {
            return 0.0;
        }
        $BundleOptions   = unserialize($ProductOptions["bundle_selection_attributes"]);
        //====================================================================//
        // Check Bundle Product Base Qty is Here
        if (isset($BundleOptions["qty"]) && is_numeric($BundleOptions["qty"]) && ($BundleOptions["qty"] > 0)) {
            $Qty    =   $BundleOptions["qty"];
        } else {
            $Qty    =   1;
        }
        //====================================================================//
        // Check Bundle Product Base Price Here
        if (isset($BundleOptions["price"]) && is_numeric($BundleOptions["price"])) {
            return (double) $BundleOptions["price"] / $Qty;
        }
        return 0.0;
    }    
        
    /**
     *  @abstract     Read requested Field
     *
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     *
     *  @return         none
     */
    private function getShippingLineFields($Key, $FieldName)
    {
        //====================================================================//
        // Check if List field & Init List Array
        $FieldId = self::lists()->InitOutput($this->Out, "lines", $FieldName);
        if (!$FieldId || !empty(Splash::input("SPLASH_TRAVIS")) ) {
            return;
        }
        
        //====================================================================//
        // READ Fields
        switch ($FieldId) {
            //====================================================================//
            // Order Line Direct Reading Data
            case 'name':
                $Data = $this->Object->getShippingDescription();
                break;
            case 'qty_ordered':
                $Data = 1;
                break;
            case 'discount_percent':
                $Data = 0;
                break;
            //====================================================================//
            // Order Line Direct Reading Data
            case 'sku':
                $Data = static::$SHIPPING_LABEL;
                break;
            //====================================================================//
            // Order Line Product Id
            case 'product_id':
                $Data = null;
                break;
            //====================================================================//
            // Order Line Unit Price
            case 'unit_price':
                $Data = $this->getShippingPrice();
                break;
            default:
                return;
        }
        //====================================================================//
        // Do Fill List with Data
        self::lists()->Insert($this->Out, "lines", $FieldName, count($this->Object->getAllItems()), $Data);
        
        unset($this->In[$Key]);
    }
    
    /**
     *  @abstract     Read requested Field
     *  @return       array
     */
    private function getShippingPrice()
    {
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
        return self::prices()->encode(
            (double)    $ShipAmount,
            (double)    round($ShipTaxPercent,2),
            null,
            $CurrencyCode,
            Mage::app()->getLocale()->currency($CurrencyCode)->getSymbol(),
            Mage::app()->getLocale()->currency($CurrencyCode)->getName()
        );
    }
        
    /**
     *  @abstract     Read requested Field
     *
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     *
     *  @return         none
     */
    private function getMoneyPointsLineFields($Key, $FieldName)
    {
        //====================================================================//
        // Check if List field & Init List Array
        // Check if Money Points where Used
        $FieldId = self::lists()->InitOutput($this->Out, "lines", $FieldName);
        if (!$FieldId || empty($this->Object->getMoneyForPoints())) {
            return;
        }
        //====================================================================//
        // Get Money Points Data
        $PointsUsed =   $this->Object->getPointsBalanceChange();
        //====================================================================//
        // READ Fields
        switch ($FieldId) {
            //====================================================================//
            // Order Line Direct Reading Data
            case 'sku':
                $Value = static::$MONEYPOINTS_LABEL;
                break;
            //====================================================================//
            // Order Line Product Id
            case 'product_id':
                $Value = null;
                break;
            //====================================================================//
            // Order Line Direct Reading Data
            case 'name':
                $Value = "Money Points" ;
                break;
            case 'qty_ordered':
                $Value = $PointsUsed;
                break;
            case 'discount_percent':
                $Value = 0;
                break;
            //====================================================================//
            // Order Line Unit Price
            case 'unit_price':
                //====================================================================//
                // Read Current Currency Code
                $CurrencyCode   =   $this->Object->getOrderCurrencyCode();
                //====================================================================//
                // Encode Discount Price
                $Value = self::prices()->encode(
                    (double)    -1 * abs(0.1),
                    (double)    20,
                    null,
                    $CurrencyCode,
                    Mage::app()->getLocale()->currency($CurrencyCode)->getSymbol(),
                    Mage::app()->getLocale()->currency($CurrencyCode)->getName()
                );
                break;
            default:
                return;
        }
        //====================================================================//
        // Do Fill List with Data
        self::lists()->Insert($this->Out, "lines", $FieldName, count($this->Object->getAllItems()) + 1, $Value);
        
        unset($this->In[$Key]);
    }   
    
    /**
     *  @abstract     Write Given Fields
     *
     *  @param        string    $FieldName              Field Identifier / Name
     *  @param        mixed     $Data                   Field Data
     *
     *  @return         none
     */
    private function setItemsFields($FieldName, $Data)
    {
        //====================================================================//
        // Safety Check
        if ($FieldName !== "lines") {
            return true;
        }
        if (!$this->isSplash()) {
            Splash::log()->deb("You Cannot Edit Orders Created on Magento");
            unset($this->In[$FieldName]);
            return true;
        }

        //====================================================================//
        // Get Original Order Items List
        $this->OrderItems   =   $this->Object->getAllItems();
        //====================================================================//
        // Verify Lines List & Update if Needed
        foreach ($Data as $LineData) {
            //====================================================================//
            // Detect Shipping Informations => Product Label === Order::SHIPPING_LABEL
            if (array_key_exists("sku", $LineData)
                &&  ($LineData["sku"] === self::SHIPPING_LABEL) ) {
                $this->setShipping($LineData);
                continue;
            }
            //====================================================================//
            // Update Shipping Informations
            $this->setOrderLineInit();
            //====================================================================//
            // Update Line Product/Infos/Totals
            $this->setProductLine($LineData);
            $this->setProductLineQty($LineData);
            $this->setProductLinePrice($LineData);
            $this->setProductLineDiscount($LineData);
            $this->setProductLineTotals();
            //====================================================================//
            // Save Changes
            if ($this->OrderItemUpdate) {
                $this->OrderItem->save();
                Splash::log()->deb("Order Item Saved");
                $this->OrderItemUpdate = false;
                $this->needUpdate();
            }
        }
        //====================================================================//
        // Delete Remaining Lines
        foreach ($this->OrderItems as $OrderItem) {
            //====================================================================//
            // Perform Line Delete
            $OrderItem->delete();
            $this->needUpdate();
        }
        unset($this->In[$FieldName]);
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
        if (array_key_exists("product_id", $OrderLineData)) {
            $ProductId  = self::objects()->Id($OrderLineData["product_id"]);
            $Product    = Mage::getModel('catalog/product')
                    ->load($ProductId);
            //====================================================================//
            // Verify Product Id Is Valid
            if ($Product->getEntityId() !== $ProductId) {
                $Product = null;
            }
        } else {
            $Product    = null;
            $ProductId  = null;
        }
        
        //====================================================================//
        // If Valid Product Given => Update Product Informations
        if ($Product) {
            //====================================================================//
            // Verify Product Id Changed
            if ($this->OrderItem->getProductId() !== $ProductId) {
                //====================================================================//
                // Update Order Item
                $this->OrderItem
                        ->setProductId($Product->getEntityId())
                        ->setProductType($Product->getTypeId())
                        ->setName($Product->getName())
                        ->setSku($Product->getSku());
                $this->OrderItemUpdate = true;
                Splash::log()->deb("Product Order Item Updated");
            }
        //====================================================================//
        // Update Line Without Product Id
        } else {
            if (array_key_exists("sku", $OrderLineData)
                &&  ($this->OrderItem->getName() !== $OrderLineData["sku"] ) ) {
                //====================================================================//
                // Update Order Item
                $this->OrderItem
                        ->setProductId(null)
                        ->setProductType(null)
                        ->setSku($OrderLineData["sku"]);
                $this->OrderItemUpdate = true;
            }
            if (array_key_exists("name", $OrderLineData)
                &&  ($this->OrderItem->getName() !== $OrderLineData["name"] ) ) {
                //====================================================================//
                // Update Order Item
                $this->OrderItem
                        ->setProductId(null)
                        ->setProductType(null)
                        ->setName($OrderLineData["name"]);
                $this->OrderItemUpdate = true;
                Splash::log()->deb("Custom Order Item Updated");
            }
        }
    }
    
    /**
     *  @abstract     Add or Update Given Order Line Informations
     *  @param        array     $OrderLineData          OrderLine Data Array
     *  @return       void
     */
    private function setProductLineQty($OrderLineData)
    {
        //====================================================================//
        // Update Quantity Informations
        if (array_key_exists("qty_ordered", $OrderLineData)) {
            //====================================================================//
            // Verify Qty Changed
            if ($this->OrderItem->getQtyOrdered() !== $OrderLineData["qty_ordered"]) {
                $this->OrderItem->setQtyBackordered(null)
                    ->setTotalQtyOrdered($OrderLineData["qty_ordered"])
                    ->setQtyOrdered($OrderLineData["qty_ordered"])
                            ;
                
                if (in_array($this->Object->getState(), array(MageOrder::STATE_PROCESSING, MageOrder::STATE_COMPLETE, MageOrder::STATE_CLOSED))) {
                    $this->OrderItem->setQtyShipped($OrderLineData["qty_ordered"]);
                } else {
                    $this->OrderItem->setQtyShipped(0);
                }
                if (in_array($this->Object->getState(), array(MageOrder::STATE_COMPLETE, MageOrder::STATE_CLOSED))) {
                    $this->OrderItem->setQtyInvoiced($OrderLineData["qty_ordered"]);
                } else {
                    $this->OrderItem->setQtyInvoiced(0);
                }
                
                $this->OrderItemUpdate  = true;
                $this->UpdateTotals     = true;
            }
        }
    }

    /**
     *  @abstract     Add or Update Given Order Line Informations
     *  @param        array     $OrderLineData          OrderLine Data Array
     *  @return       void
     */
    private function setProductLinePrice($OrderLineData)
    {
        //====================================================================//
        // Update Price Informations
        if (array_key_exists("unit_price", $OrderLineData)) {
            $OrderLinePrice     =    $OrderLineData["unit_price"];
            //====================================================================//
            // Verify Price Changed
            if ($this->OrderItem->getPrice() !== $OrderLinePrice["ht"]) {
                $this->OrderItem
                    ->setPrice($OrderLinePrice["ht"])
                    ->setBasePrice($OrderLinePrice["ht"])
                    ->setBaseOriginalPrice($OrderLinePrice["ht"])
                    ->setOriginalPrice($OrderLinePrice["ht"]);
                $this->OrderItemUpdate  = true;
                $this->UpdateTotals     = true;
            }
            //====================================================================//
            // Verify Tax Rate Changed
            if ($this->OrderItem->getTaxPercent() !== $OrderLinePrice["vat"]) {
                $this->OrderItem->setTaxPercent($OrderLinePrice["vat"]);
                $this->OrderItemUpdate  = true;
                $this->UpdateTotals     = true;
            }
        }
    }

    /**
     *  @abstract     Add or Update Given Order Line Informations
     *  @param        array     $OrderLineData          OrderLine Data Array
     *  @return       void
     */
    private function setProductLineDiscount($OrderLineData)
    {
        //====================================================================//
        // Update Discount Informations
        if (array_key_exists("discount_percent", $OrderLineData)) {
            //====================================================================//
            // Verify Discount Percent Changed
            if (abs((double) $this->OrderItem->getDiscountPercent() - (double) $OrderLineData["discount_percent"]) > 1E-3) {
                $this->OrderItem->setDiscountPercent($OrderLineData["discount_percent"]);
                $this->OrderItemUpdate  = true;
                $this->UpdateTotals     = true;
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
        if ($this->OrderItem->getRowTotal() !== $TotalHt) {
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
            $this->OrderItemUpdate  = true;
            $this->UpdateTotals     = true;
        }
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
        if (!$this->OrderItem) {
            //====================================================================//
            // Create New Order Item
            $this->OrderItem = Mage::getModel('sales/order_item')
                ->setStoreId($this->Object->getStore()->getStoreId())
                ->setQuoteItemId(null)
                ->setQuoteParentItemId(null)
                ->setOrder($this->Object);
            //====================================================================//
            // Add Item to Order
            $this->Object->addItem($this->OrderItem);
            Splash::log()->deb("New Order Item Created");
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
        if (array_key_exists("name", $OrderLineData)) {
            //====================================================================//
            // Verify Discount Changed
            if ($this->Object->getShippingDescription() !== $OrderLineData["name"]) {
                $this->Object->setShippingDescription($OrderLineData["name"]);
                $this->needUpdate();
            }
        }
        
        //====================================================================//
        // Update Price Informations
        if (array_key_exists("unit_price", $OrderLineData)) {
            $OrderLinePrice     =    $OrderLineData["unit_price"];
            //====================================================================//
            // Verify HT Price Changed
            if ($this->Object->getShippingAmount() !== $OrderLinePrice["ht"]) {
                $this->Object
                    ->setShippingAmount($OrderLinePrice["ht"])
                    ->setBaseShippingAmount($OrderLinePrice["ht"]);
                $this->UpdateTotals     = true;
                $this->needUpdate();
            }
            //====================================================================//
            // Verify Tax Rate Changed
            $TaxAmount =  $OrderLinePrice["ttc"] - $OrderLinePrice['ht'];
            if ($this->OrderItem->getShippingTaxAmount() !== $TaxAmount) {
                $this->OrderItem->setShippingTaxAmount($TaxAmount);
                $this->UpdateTotals     = true;
                $this->needUpdate();
            }
        }
    }
        
    /**
     *   @abstract   Update Order Totals
     *
     *   @return     bool
     */
    private function _UpdateTotals()
    {
        
        //====================================================================//
        // Exit if NOT Needed
        if (!$this->UpdateTotals) {
            return true;
        }
        if (!$this->isSplash()) {
            return true;
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
        if (is_array($Products)) {
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
        $this->Object->setBaseGrandTotal($subtotal_incl_tax + $this->Object->getShippingAmount());
        $this->Object->setGrandTotal($subtotal_incl_tax + $this->Object->getShippingAmount());

        $this->UpdateTotals     = false;
        $this->needUpdate();
    }
}
