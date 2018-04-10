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
trait ItemsTrait
{
    
    protected static $SHIPPING_LABEL             =   "__Shipping__";
    
    /**
    *   @abstract     Build Address Fields using FieldFactory
    */
    private function buildProductsLineFields()
    {
        
        $ListId =   "items";
        $ListName = "" ;
        
        //====================================================================//
        // Order Line Label
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("sku")
                ->InList("items")
                ->Name($ListName . Mage::helper('sales')->__('Sku'))
                ->MicroData("http://schema.org/partOfInvoice", "name")
                ->Association("name@items", "qty@items", "unit_price@items");
        
        //====================================================================//
        // Order Line Description
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("name")
                ->InList("items")
                ->Name($ListName . Mage::helper('sales')->__('Description Message'))
                ->MicroData("http://schema.org/partOfInvoice", "description")
                ->Association("name@items", "qty@items", "unit_price@items");

        //====================================================================//
        // Order Line Product Identifier
        $this->FieldsFactory()->Create(self::Objects()->Encode("Product", SPL_T_ID))
                ->Identifier("product_id")
                ->InList("items")
                ->Name($ListName . Mage::helper('sales')->__('Product'))
                ->MicroData("http://schema.org/Product", "productID")
                ->Association("name@items", "qty@items", "unit_price@items");

        //====================================================================//
        // Order Line Quantity
        $this->FieldsFactory()->Create(SPL_T_INT)
                ->Identifier("qty")
                ->InList("items")
                ->Name($ListName . Mage::helper('sales')->__('Qty Invoiced'))
                ->MicroData("http://schema.org/QuantitativeValue", "value")
                ->Association("name@items", "qty@items", "unit_price@items");

        //====================================================================//
        // Order Line Discount
        $this->FieldsFactory()->Create(SPL_T_DOUBLE)
                ->Identifier("discount_percent")
                ->InList("items")
                ->Name($ListName . Mage::helper('sales')->__('Discount (%s)'))
                ->MicroData("http://schema.org/Order", "discount")
                ->Association("name@items", "qty@items", "unit_price@items");

        //====================================================================//
        // Order Line Unit Price
        $this->FieldsFactory()->Create(SPL_T_PRICE)
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
    private function getShippingLineFields($Key, $FieldName)
    {
        //====================================================================//
        // Check if List field & Init List Array
        $FieldId = self::Lists()->InitOutput($this->Out, "items", $FieldName);
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
                    $Value = self::Prices()->Encode(
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
        self::Lists()->Insert($this->Out, "items", $FieldName, count($this->Products), $Value);
        
        $NoUse = $Key;
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
        $FieldId = self::Lists()->InitOutput($this->Out, "items", $FieldName);
        if (!$FieldId) {
            return;
        }
        //====================================================================//
        // Fill List with Data
        foreach ($this->Products as $Index => $Product) {
            //====================================================================//
            // Do Fill List with Data
            self::Lists()->Insert($this->Out, "items", $FieldName, $Index, $this->getProductsLineValue($Product, $FieldId));
        }
        unset($this->In[$Key]);
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
                if ($Product->getPriceInclTax() && $Product->getQty()) {
                    return (double) 100 * $Product->getDiscountAmount() / ($Product->getPriceInclTax() * $Product->getQty());
                }
                return 0;
                
            case 'qty':
                return (int) ( $Product->getHasChildren() ? 0 : $Product->getData($FieldId) );
                
            //====================================================================//
            // Invoice Line Product Id
            case 'product_id':
                return self::Objects()->Encode("Product", $Product->getData($FieldId));
                
            //====================================================================//
            // Invoice Line Unit Price
            case 'unit_price':
                //====================================================================//
                // Read Current Currency Code
                $CurrencyCode   =   $this->Object->getOrderCurrencyCode();
                //====================================================================//
                // Build Price Array
                return self::Prices()->Encode(
                    (double)    $Product->getPrice(),
                    (double)    $Product->getOrderItem()->getTaxPercent(),
                    null,
                    $CurrencyCode,
                    Mage::app()->getLocale()->currency($CurrencyCode)->getSymbol(),
                    Mage::app()->getLocale()->currency($CurrencyCode)->getName()
                );
                
            default:
                return Null;
        }
        return Null;
    }    
}
