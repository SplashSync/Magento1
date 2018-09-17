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

use Splash\Core\SplashCore as Splash;
use Splash\Local\Objects\Order;

use Mage;

/**
 * @abstract    Magento 1 Invoice Items Fields Access
 */
trait ItemsTrait
{
    
    protected static $SHIPPING_LABEL             =   "__Shipping__";
    protected static $MONEYPOINTS_LABEL          =   "__Money_For_Points__";
    
    /**
    *   @abstract     Build Address Fields using FieldFactory
    */
    private function buildProductsLineFields()
    {
        $ListName = "" ;
        
        //====================================================================//
        // Order Line Label
        $this->fieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("sku")
                ->InList("items")
                ->Name($ListName . Mage::helper('sales')->__('Sku'))
                ->MicroData("http://schema.org/partOfInvoice", "name")
                ->Association("name@items", "qty@items", "unit_price@items");
        
        //====================================================================//
        // Order Line Description
        $this->fieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("name")
                ->InList("items")
                ->Name($ListName . Mage::helper('sales')->__('Description Message'))
                ->MicroData("http://schema.org/partOfInvoice", "description")
                ->Association("name@items", "qty@items", "unit_price@items");

        //====================================================================//
        // Order Line Product Identifier
        $this->fieldsFactory()->Create(self::objects()->Encode("Product", SPL_T_ID))
                ->Identifier("product_id")
                ->InList("items")
                ->Name($ListName . Mage::helper('sales')->__('Product'))
                ->MicroData("http://schema.org/Product", "productID")
                ->Association("name@items", "qty@items", "unit_price@items");

        //====================================================================//
        // Order Line Quantity
        $this->fieldsFactory()->Create(SPL_T_INT)
                ->Identifier("qty")
                ->InList("items")
                ->Name($ListName . Mage::helper('sales')->__('Qty Invoiced'))
                ->MicroData("http://schema.org/QuantitativeValue", "value")
                ->Association("name@items", "qty@items", "unit_price@items");

        //====================================================================//
        // Order Line Discount
        $this->fieldsFactory()->Create(SPL_T_DOUBLE)
                ->Identifier("discount_percent")
                ->InList("items")
                ->Name($ListName . Mage::helper('sales')->__('Discount (%s)'))
                ->MicroData("http://schema.org/Order", "discount")
                ->Association("name@items", "qty@items", "unit_price@items");

        //====================================================================//
        // Order Line Unit Price
        $this->fieldsFactory()->Create(SPL_T_PRICE)
                ->Identifier("unit_price")
                ->InList("items")
                ->Name($ListName . Mage::helper('sales')->__('Price'))
                ->MicroData("http://schema.org/PriceSpecification", "price")
                ->Association("name@items", "qty@items", "unit_price@items");
    }

    /**
     *  @abstract     Read requested Field
     *
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     *
     *  @return         none
     */
    private function getProductsLineFields($Key, $FieldName)
    {
        //====================================================================//
        // Check if List field & Init List Array
        $FieldId = self::lists()->InitOutput($this->Out, "items", $FieldName);
        if (!$FieldId) {
            return;
        }
        //====================================================================//
        // Fill List with Data
        foreach ($this->Products as $Index => $Product) {
            //====================================================================//
            // Do Fill List with Data
            self::lists()->Insert($this->Out, "items", $FieldName, $Index, $this->getProductsLineValue($Product, $FieldId));
        }
        if ( isset($this->In[$Key]) ) {
            unset($this->In[$Key]);
        }
    }
    
    private function getProductsLineValue($Product, $FieldId)
    {
        //====================================================================//
        // READ Fields
        switch ($FieldId) {
            //====================================================================//
            // Invoice Line Direct Reading Data
            case 'sku':
            case 'name':
                return $Product->getData($FieldId);
                
            case 'discount_percent':
                return $this->isProductInBundlePriceMode($Product) 
                    ? $this->getItemsDiscount($Product->getOrderItem()->getParentItem())
                    : $this->getItemsDiscount($Product);
                
            case 'qty':
                return (int) $Product->getData($FieldId);
                
            //====================================================================//
            // Invoice Line Product Id
            case 'product_id':
                return self::objects()->Encode("Product", $Product->getData($FieldId));
                
            //====================================================================//
            // Invoice Line Unit Price
            case 'unit_price':
                //====================================================================//
                // Build Price Array
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
    private function isBundleInPriceMode($Product)
    {
        //====================================================================//
        // If Bundle Prices Mode NOT Enabled
        if( !Splash::Local()->isBundleComponantsPricesMode() ) {
            return false;
        }
        //====================================================================//
        // If Product has Childrens => is a Bundle
        return (bool) $Product->getOrderItem()->getHasChildren();
    }
    
    /**
     *  @abstract     Check If Item is a Bundle in Bundle Componants Price Mode 
     *  @return       bool
     */
    private function isProductInBundlePriceMode($Product)
    {
        //====================================================================//
        // If Bundle Prices Mode NOT Enabled
        if( !Splash::Local()->isBundleComponantsPricesMode() ) {
            return false;
        }
        //====================================================================//
        // If Product has Parent => is a Bundle Componant
        if( !empty($Product->getOrderItem()->getParentItemId()) ) {
            return true;
        }
        return false;
    }
    
    /**
     *  @abstract     Read Invoice Product Price
     */    
    private function getItemsPrice($Product)
    {
        //====================================================================//
        // Read Item Regular Price 
        $HtPrice    =   (double) $Product->getPrice();
        $TtcPrice   =   null;
        $ItemTax    =   (double) $Product->getOrderItem()->getTaxPercent();
        //====================================================================//
        // Override Item Price for Bundle Products
        if ($this->isBundleInPriceMode($Product)) {
            $HtPrice  =   $ItemTax    =   0.0;
        } elseif ($this->isProductInBundlePriceMode($Product)) {
            $HtPrice    =   null;
            $TtcPrice   =   (double) Order::getBundleItemsPrice($Product->getOrderItem());
            $ItemTax    =   (double) $Product->getOrderItem()->getParentItem()->getTaxPercent();            
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
        $FieldId = self::lists()->InitOutput($this->Out, "items", $FieldName);
        if (!$FieldId) {
            return;
        }
        
        //====================================================================//
        // READ Fields
        switch ($FieldId) {
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
                $Value = null;
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
                    $Value = self::prices()->encode(
                        (double)    $ShipAmount,
                        (double)    $ShipTaxPercent,
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
        self::lists()->Insert($this->Out, "items", $FieldName, count($this->Products), $Value);
        
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
    private function getMoneyPointsLineFields($Key, $FieldName)
    {
        //====================================================================//
        // Check if List field & Init List Array
        // Check if Money Points where Used
        $FieldId = self::lists()->InitOutput($this->Out, "items", $FieldName);
        if (!$FieldId || empty($this->Object->getMoneyForPoints())) {
            return;
        }
        //====================================================================//
        // Get Money Points Data
        $Amount     =   $this->Object->getMoneyForPoints(); 
        $PointsUsed =   $this->Object->getOrder()->getPointsBalanceChange();
        //====================================================================//
        // READ Fields
        switch ($FieldId) {
            //====================================================================//
            // Order Line Direct Reading Data
            case 'sku':
                $Value = static::$MONEYPOINTS_LABEL;
                break;
            //====================================================================//
            // Order Line Direct Reading Data
            case 'name':
                $Value = "Used " . $PointsUsed . " Money Points" ;
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
                $Value = null;
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
                    (double)    $Amount,
                    (double)    0,
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
        self::lists()->Insert($this->Out, "items", $FieldName, count($this->Products) + 1, $Value);
        
        unset($this->In[$Key]);
    }    
}
