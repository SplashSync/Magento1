<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2021 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Splash\Local\Objects\Invoice;

use Mage;
use Mage_Sales_Model_Order_Creditmemo_Item as CreditItem;
use Mage_Sales_Model_Order_Invoice_Item as InvoiceItem;
use Splash\Core\SplashCore as Splash;
use Splash\Local\Local;
use Splash\Local\Objects\Order;

/**
 * Magento 1 Invoice Items Fields Access
 */
trait ItemsTrait
{
    /**
     * @var string
     */
    protected static $SHIPPING_LABEL = "__Shipping__";

    /**
     * @var string
     */
    protected static $MONEYPOINTS_LABEL = "__Money_For_Points__";

    /**
     * Build Address Fields using FieldFactory
     */
    protected function buildProductsLineFields(): void
    {
        $listName = "" ;

        //====================================================================//
        // Order Line Label
        $this->fieldsFactory()->Create(SPL_T_VARCHAR)
            ->Identifier("sku")
            ->InList("items")
            ->Name($listName.Mage::helper('sales')->__('Sku'))
            ->MicroData("http://schema.org/partOfInvoice", "name")
            ->Association("name@items", "qty@items", "unit_price@items");

        //====================================================================//
        // Order Line Description
        $this->fieldsFactory()->Create(SPL_T_VARCHAR)
            ->Identifier("name")
            ->InList("items")
            ->Name($listName.Mage::helper('sales')->__('Description Message'))
            ->MicroData("http://schema.org/partOfInvoice", "description")
            ->Association("name@items", "qty@items", "unit_price@items");

        //====================================================================//
        // Order Line Product Identifier
        $this->fieldsFactory()->Create((string) self::objects()->encode("Product", SPL_T_ID))
            ->Identifier("product_id")
            ->InList("items")
            ->Name($listName.Mage::helper('sales')->__('Product'))
            ->MicroData("http://schema.org/Product", "productID")
            ->Association("name@items", "qty@items", "unit_price@items");

        //====================================================================//
        // Order Line Quantity
        $this->fieldsFactory()->Create(SPL_T_INT)
            ->Identifier("qty")
            ->InList("items")
            ->Name($listName.Mage::helper('sales')->__('Qty Invoiced'))
            ->MicroData("http://schema.org/QuantitativeValue", "value")
            ->Association("name@items", "qty@items", "unit_price@items");

        //====================================================================//
        // Order Line Discount
        $this->fieldsFactory()->Create(SPL_T_DOUBLE)
            ->Identifier("discount_percent")
            ->InList("items")
            ->Name($listName.Mage::helper('sales')->__('Discount (%s)'))
            ->MicroData("http://schema.org/Order", "discount")
            ->Association("name@items", "qty@items", "unit_price@items");

        //====================================================================//
        // Order Line Unit Price
        $this->fieldsFactory()->Create(SPL_T_PRICE)
            ->Identifier("unit_price")
            ->InList("items")
            ->Name($listName.Mage::helper('sales')->__('Price'))
            ->MicroData("http://schema.org/PriceSpecification", "price")
            ->Association("name@items", "qty@items", "unit_price@items");
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    protected function getProductsLineFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // Check if List field & Init List Array
        $fieldId = self::lists()->InitOutput($this->out, "items", $fieldName);
        if (!$fieldId) {
            return;
        }
        //====================================================================//
        // Fill List with Data
        foreach ($this->products as $index => $product) {
            //====================================================================//
            // Do Fill List with Data
            $value = $this->getProductsLineValue($product, $fieldId);
            self::lists()->insert($this->out, "items", $fieldName, $index, $value);
        }
        if (isset($this->in[$key])) {
            unset($this->in[$key]);
        }
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    protected function getShippingLineFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // Check if List field & Init List Array
        $fieldId = self::lists()->InitOutput($this->out, "items", $fieldName);
        if (!$fieldId) {
            return;
        }
        //====================================================================//
        // READ Fields
        switch ($fieldId) {
            //====================================================================//
            // Order Line Direct Reading Data
            case 'sku':
                $value = static::$SHIPPING_LABEL;

                break;
            //====================================================================//
            // Order Line Direct Reading Data
            case 'name':
                $value = $this->object->getOrder()->getShippingDescription();

                break;
            case 'qty':
                $value = 1;

                break;
            case 'discount_percent':
                $value = 0;

                break;
            //====================================================================//
            // Order Line Product Id
            case 'product_id':
                $value = null;

                break;
            //====================================================================//
            // Order Line Unit Price
            case 'unit_price':
                //====================================================================//
                // Read Current Currency Code
                $currencyCode = $this->object->getOrderCurrencyCode();
                $shipAmount = $this->object->getShippingAmount();
                //====================================================================//
                // Compute Shipping Tax Percent
                if ($shipAmount > 0) {
                    $shipTaxPercent = 100 * $this->object->getShippingTaxAmount() / $shipAmount;
                } else {
                    $shipTaxPercent = 0;
                }
                $value = self::prices()->encode(
                    (float)    $shipAmount,
                    (float)    round($shipTaxPercent, 2),
                    null,
                    $currencyCode,
                    Mage::app()->getLocale()->currency($currencyCode)->getSymbol(),
                    Mage::app()->getLocale()->currency($currencyCode)->getName()
                );

                break;
            default:
                return;
        }
        //====================================================================//
        // Do Fill List with Data
        self::lists()->insert($this->out, "items", $fieldName, count($this->products), $value);

        unset($this->in[$key]);
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    protected function getMoneyPointsLineFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // Check if List field & Init List Array
        // Check if Money Points where Used
        $fieldId = self::lists()->initOutput($this->out, "items", $fieldName);
        if (!$fieldId || empty($this->object->getMoneyForPoints())) {
            return;
        }
        //====================================================================//
        // Get Money Points Data
        $pointsUsed = $this->object->getOrder()->getPointsBalanceChange();
        //====================================================================//
        // READ Fields
        switch ($fieldId) {
            //====================================================================//
            // Order Line Direct Reading Data
            case 'sku':
                $value = static::$MONEYPOINTS_LABEL;

                break;
            //====================================================================//
            // Order Line Direct Reading Data
            case 'name':
                $value = "Money Points";

                break;
            case 'qty':
                $value = $pointsUsed;

                break;
            case 'discount_percent':
                $value = 0;

                break;
            //====================================================================//
            // Order Line Product Id
            case 'product_id':
                $value = null;

                break;
            //====================================================================//
            // Order Line Unit Price
            case 'unit_price':
                //====================================================================//
                // Read Current Currency Code
                $currencyCode = $this->object->getOrderCurrencyCode();
                //====================================================================//
                // Encode Discount Price
                $value = self::prices()->encode(
                    (float)    -1 * abs(0.1),
                    (float)    20,
                    null,
                    $currencyCode,
                    Mage::app()->getLocale()->currency($currencyCode)->getSymbol(),
                    Mage::app()->getLocale()->currency($currencyCode)->getName()
                );

                break;
            default:
                return;
        }
        //====================================================================//
        // Do Fill List with Data
        self::lists()->insert($this->out, "items", $fieldName, count($this->products) + 1, $value);

        unset($this->in[$key]);
    }

    /**
     * Extract Product Line item Value
     *
     * @param CreditItem|InvoiceItem $product
     * @param string                 $fieldId
     *
     * @return null|mixed
     */
    private function getProductsLineValue(object $product, string $fieldId)
    {
        //====================================================================//
        // READ Fields
        switch ($fieldId) {
            //====================================================================//
            // Invoice Line Direct Reading Data
            case 'sku':
            case 'name':
                return $product->getData($fieldId);
            //====================================================================//
            // In Bundles Advanced Prices Mode, we Take Discount from Parent Bundle
            case 'discount_percent':
                return $this->isProductInBundlePriceMode($product)
                    ? $this->getItemsDiscount($product->getOrderItem()->getParentItem())
                    : $this->getItemsDiscount($product);

            //====================================================================//
            // Qty Always 0 for Bundles, Else Normal Reading
            case 'qty':
                return $product->getOrderItem()->getHasChildren() ? 0 : (int) $product->getData($fieldId);
            //====================================================================//
            // Invoice Line Product Id
            case 'product_id':
                return self::objects()->encode("Product", $product->getData($fieldId));
            //====================================================================//
            // Invoice Line Unit Price
            case 'unit_price':
                //====================================================================//
                // Build Price Array
                return $this->getItemsPrice($product);
            default:
                return null;
        }
    }

    /**
     * Check If Item is a Bundle in Bundle Components Price Mode
     *
     * @param mixed $product
     *
     * @return bool
     */
    private function isProductInBundlePriceMode($product): bool
    {
        //====================================================================//
        // If Bundle Prices Mode NOT Enabled
        /** @var Local $local */
        $local = Splash::local();
        if (!$local->isBundleComponantsPricesMode()) {
            return false;
        }
        //====================================================================//
        // If Product has Parent => is a Bundle Component
        if (!empty($product->getOrderItem()->getParentItemId())) {
            return true;
        }

        return false;
    }

    /**
     * Read Invoice Product Price
     *
     * @param mixed $product
     *
     * @return array|string
     */
    private function getItemsPrice($product)
    {
        //====================================================================//
        // Read Item Regular Price
        $htPrice = (float) $product->getPrice();
        $ttcPrice = null;
        $itemTax = (float) $product->getOrderItem()->getTaxPercent();
        //====================================================================//
        // Collect Item Price at Bundle Options Level
        if ($this->isProductInBundlePriceMode($product)) {
            $htPrice = null;
            $ttcPrice = (float) Order::getBundleItemOriginPrice($product->getOrderItem());
            $itemTax = (float) $product->getOrderItem()->getParentItem()->getTaxPercent();
        }
        //====================================================================//
        // Read Current Currency Code
        $currencyCode = $this->object->getOrderCurrencyCode();
        //====================================================================//
        // Build Price Array
        return self::prices()->encode(
            $htPrice,
            $itemTax,
            $ttcPrice,
            $currencyCode,
            Mage::app()->getLocale()->currency($currencyCode)->getSymbol(),
            Mage::app()->getLocale()->currency($currencyCode)->getName()
        );
    }

    /**
     * Read requested Item Discount Pourcent
     *
     * @param mixed $product
     *
     * @return float|int
     */
    private function getItemsDiscount($product)
    {
        if (!empty($product->getData('discount_percent'))) {
            return (float) $product->getData('discount_percent');
        }

        if (($product->getPriceInclTax() > 0) && ($product->getQty() > 0)) {
            return (float) 100 * $product->getDiscountAmount() / ($product->getPriceInclTax() * $product->getQty());
        }

        return 0;
    }
}
