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

namespace Splash\Local\Objects\Order;

use Exception;
use Mage;
use Mage_Catalog_Model_Product;
use Mage_Sales_Model_Order      as MageOrder;
use Mage_Sales_Model_Order_Item;
use Splash\Core\SplashCore      as Splash;
use Splash\Local\Local;

/**
 * @abstract    Magento 1 Order Items Fields Access
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
     * @var array
     */
    private $orderItems = array();

    /**
     * @var Mage_Sales_Model_Order_Item
     */
    private $orderItem;

    /**
     * @var bool
     */
    private $updateTotals = false;

    /**
     * @var bool
     */
    private $orderItemUpdate = false;

    /**
     * Read Order Bundled Product Price
     *
     * @param mixed $product
     *
     * @return float
     */
    public static function getBundleItemOriginPrice($product)
    {
        $productOptions = $product->getProductOptions();
        //====================================================================//
        // Check Bundle Product Options are Here
        if (!isset($productOptions["bundle_selection_attributes"])
            || !is_scalar($productOptions["bundle_selection_attributes"])) {
            return 0.0;
        }
        $bundleOptions = unserialize((string) $productOptions["bundle_selection_attributes"]);
        //====================================================================//
        // Check Bundle Product Base qty is Here
        if (isset($bundleOptions["qty"]) && is_numeric($bundleOptions["qty"]) && ($bundleOptions["qty"] > 0)) {
            $qty = $bundleOptions["qty"];
        } else {
            $qty = 1;
        }
        //====================================================================//
        // Check Bundle Product Base Price Here
        if (isset($bundleOptions["price"]) && is_numeric($bundleOptions["price"])) {
            return (float) $bundleOptions["price"] / $qty;
        }

        return 0.0;
    }

    /**
     * Build Address Fields using FieldFactory
     */
    protected function buildItemsFields(): void
    {
        $listName = "";

        //====================================================================//
        // Order Line Label
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("sku")
            ->InList("lines")
            ->Name($listName."Label")
            ->MicroData("http://schema.org/partOfInvoice", "name")
            ->Association("name@lines", "qty_ordered@lines", "unit_price@lines")
        ;

        //====================================================================//
        // Order Line Description
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("name")
            ->InList("lines")
            ->Name($listName."Description")
            ->MicroData("http://schema.org/partOfInvoice", "description")
            ->Association("name@lines", "qty_ordered@lines", "unit_price@lines")
        ;

        //====================================================================//
        // Order Line Product identifier
        $this->fieldsFactory()->create((string) self::objects()->Encode("Product", SPL_T_ID))
            ->identifier("product_id")
            ->InList("lines")
            ->Name($listName."Product ID")
            ->MicroData("http://schema.org/Product", "productID")
            ->Association("qty_ordered@lines", "unit_price@lines")
            ->isNotTested()
        ;

        //====================================================================//
        // Order Line Quantity
        $this->fieldsFactory()->create(SPL_T_INT)
            ->identifier("qty_ordered")
            ->InList("lines")
            ->Name($listName."Quantity")
            ->MicroData("http://schema.org/QuantitativeValue", "value")
            ->Association("name@lines", "qty_ordered@lines", "unit_price@lines")
        ;
        $this->fieldsFactory()->isRequired();

        //====================================================================//
        // Order Line Discount
        $this->fieldsFactory()->create(SPL_T_DOUBLE)
            ->identifier("discount_percent")
            ->InList("lines")
            ->Name($listName."Discount (%)")
            ->MicroData("http://schema.org/Order", "discount")
            ->Association("name@lines", "qty_ordered@lines", "unit_price@lines")
        ;

        //====================================================================//
        // Order Line Unit Price
        $this->fieldsFactory()->create(SPL_T_PRICE)
            ->identifier("unit_price")
            ->InList("lines")
            ->Name($listName."Price")
            ->MicroData("http://schema.org/PriceSpecification", "price")
            ->Association("name@lines", "qty_ordered@lines", "unit_price@lines")
        ;
    }

    /**
     * Read requested Field
     *
     * @param string $key Input List Key
     * @param string $fieldName Field identifier / Name
     *
     * @return void
     */
    protected function getItemsFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // Check if List field & Init List Array
        $fieldId = self::lists()->initOutput($this->out, "lines", $fieldName);
        if (!$fieldId) {
            return;
        }
        //====================================================================//
        // Verify List is Not Empty
        $products = $this->object->getAllItems();
        if (!is_array($products)) {
            return;
        }
        //====================================================================//
        // Fill List with Data
        foreach ($products as $index => $product) {
            //====================================================================//
            // Do Fill List with Data
            self::lists()->Insert($this->out, "lines", $fieldName, $index, $this->getItemsValues($product, $fieldId));
        }
        unset($this->in[$key]);
    }

    /**
     * Read requested Field
     *
     * @param string $key Input List Key
     * @param string $fieldName Field identifier / Name
     *
     * @return void
     */
    protected function getShippingLineFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // Check if List field & Init List Array
        $fieldId = self::lists()->InitOutput($this->out, "lines", $fieldName);
        if (!$fieldId || !empty(Splash::input("SPLASH_TRAVIS"))) {
            return;
        }

        //====================================================================//
        // READ Fields
        switch ($fieldId) {
            //====================================================================//
            // Order Line Direct Reading Data
            case 'name':
                $data = $this->object->getShippingDescription();

                break;
            case 'qty_ordered':
                $data = 1;

                break;
            case 'discount_percent':
                $data = 0;

                break;
            //====================================================================//
            // Order Line Direct Reading Data
            case 'sku':
                $data = static::$SHIPPING_LABEL;

                break;
            //====================================================================//
            // Order Line Product Id
            case 'product_id':
                $data = null;

                break;
            //====================================================================//
            // Order Line Unit Price
            case 'unit_price':
                $data = $this->getShippingPrice();

                break;
            default:
                return;
        }
        //====================================================================//
        // Do Fill List with Data
        self::lists()->Insert($this->out, "lines", $fieldName, count($this->object->getAllItems()), $data);

        unset($this->in[$key]);
    }

    /**
     * Read requested Field
     *
     * @param string $key Input List Key
     * @param string $fieldName Field identifier / Name
     *
     * @return void
     */
    protected function getMoneyPointsLineFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // Check if List field & Init List Array
        // Check if Money Points where Used
        $fieldId = self::lists()->InitOutput($this->out, "lines", $fieldName);
        if (!$fieldId || empty($this->object->getMoneyForPoints())) {
            return;
        }
        //====================================================================//
        // Get Money Points Data
        $pointsUsed = $this->object->getPointsBalanceChange();
        //====================================================================//
        // READ Fields
        switch ($fieldId) {
            //====================================================================//
            // Order Line Direct Reading Data
            case 'sku':
                $value = static::$MONEYPOINTS_LABEL;

                break;
            //====================================================================//
            // Order Line Product Id
            case 'product_id':
                $value = null;

                break;
            //====================================================================//
            // Order Line Direct Reading Data
            case 'name':
                $value = "Money Points" ;

                break;
            case 'qty_ordered':
                $value = $pointsUsed;

                break;
            case 'discount_percent':
                $value = 0;

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
        self::lists()->Insert($this->out, "lines", $fieldName, count($this->object->getAllItems()) + 1, $value);

        unset($this->in[$key]);
    }

    /**
     * Write Given Fields
     *
     * @param string $fieldName Field identifier / Name
     * @param mixed  $data      Field Data
     *
     * @return void
     */
    protected function setItemsFields(string $fieldName, $data): void
    {
        //====================================================================//
        // Safety Check
        if ("lines" !== $fieldName) {
            return;
        }
        if (!$this->isSplash()) {
            Splash::log()->deb("You Cannot Edit Orders created on Magento");
            unset($this->in[$fieldName]);

            return;
        }

        //====================================================================//
        // Get Original Order Items List
        $this->orderItems = $this->object->getAllItems();
        //====================================================================//
        // Verify Lines List & Update if Needed
        foreach ($data as $lineData) {
            //====================================================================//
            // Detect Shipping Informations => Product Label === Order::SHIPPING_LABEL
            if (array_key_exists("sku", $lineData)
                && (self::SHIPPING_LABEL === $lineData["sku"])) {
                $this->setShipping($lineData);

                continue;
            }
            //====================================================================//
            // Update Shipping Informations
            $this->setOrderLineInit();
            //====================================================================//
            // Update Line Product/Infos/Totals
            $this->setProductLine($lineData);
            $this->setProductLineQty($lineData);
            $this->setProductLinePrice($lineData);
            $this->setProductLineDiscount($lineData);
            $this->setProductLineTotals();
            //====================================================================//
            // Save Changes
            if ($this->orderItemUpdate) {
                $this->orderItem->save();
                Splash::log()->deb("Order Item Saved");
                $this->orderItemUpdate = false;
                $this->needUpdate();
            }
        }
        //====================================================================//
        // Delete Remaining Lines
        foreach ($this->orderItems as $orderItem) {
            //====================================================================//
            // Perform Line Delete
            $orderItem->delete();
            $this->needUpdate();
        }
        unset($this->in[$fieldName]);
    }

    /**
     * Update Order Totals
     *
     * @return bool
     */
    protected function updateOrderTotals(): bool
    {
        //====================================================================//
        // Exit if NOT Needed
        if (!$this->updateTotals) {
            return true;
        }
        if (!$this->isSplash()) {
            return true;
        }
        //====================================================================//
        // Init Prices Counters
        $taxAmount = 0;
        $subtotal = 0;
        $subtotalInclTax = 0;
        $totalQtyOrdered = 0;
        //====================================================================//
        // Counts For Products Lines
        $products = $this->object->getAllItems();
        if (is_array($products)) {
            foreach ($products as $productLine) {
                //====================================================================//
                // Fill Order Quantity Count
                $totalQtyOrdered += $productLine->getQtyOrdered();
                //====================================================================//
                // Fill Order Product Costs
                $subtotal += $productLine->getRowTotal();
                $subtotalInclTax += $productLine->getRowTotalInclTax();
                $taxAmount += $productLine->getTaxAmount();
            }
        }
        //====================================================================//
        // Update Subtotals
        $this->object->setBaseTotalQtyOrdered($totalQtyOrdered);
        $this->object->setTotalQtyOrdered($totalQtyOrdered);

        $this->object->setBaseSubtotal($subtotal);
        $this->object->setSubtotal($subtotal);

        $this->object->setBaseSubtotalInclTax($subtotalInclTax);
        $this->object->setSubtotalInclTax($subtotalInclTax);

        $this->object->setBaseTaxAmount($taxAmount);
        $this->object->setTaxAmount($taxAmount);

        //====================================================================//
        // Fill Order Grand Total
        $this->object->setBaseGrandTotal($subtotalInclTax + $this->object->getShippingAmount());
        $this->object->setGrandTotal($subtotalInclTax + $this->object->getShippingAmount());

        $this->updateTotals = false;
        $this->needUpdate();

        return true;
    }

    /**
     * Read requested Item Values
     *
     * @param mixed $product
     * @param string $fieldId
     *
     * @return mixed
     */
    private function getItemsValues($product,string $fieldId)
    {
        switch ($fieldId) {
            //====================================================================//
            // Order Line Direct Reading Data
            case 'sku':
            case 'name':
                return $product->getData($fieldId);
            //====================================================================//
            // In Bundles Advanced Prices Mode, we Take Discount from Parent Bundle
            case 'discount_percent':
                return $this->isProductInBundlePriceMode($product)
                    ? $this->getItemsDiscount($product->getParentItem())
                    : $this->getItemsDiscount($product);

            //====================================================================//
            // Qty Always 0 for Bundles, Else Normal Reading
            case 'qty_ordered':
                return $product->getHasChildren() ? 0 : (int) $product->getData($fieldId);
            //====================================================================//
            // Order Line Product Id
            case 'product_id':
                return self::objects()->encode("Product", $product->getData($fieldId));
            //====================================================================//
            // Order Line Unit Price
            case 'unit_price':
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
     * @throws Exception
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
        if (!empty($product->getParentItemId())) {
            return true;
        }

        return false;
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

    /**
     * Read Order Product Price
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
        $itemTax = (float) $product->getTaxPercent();
        //====================================================================//
        // Collect Item Price at Bundle Options Level
        if ($this->isProductInBundlePriceMode($product)) {
            $htPrice = null;
            $ttcPrice = (float) $this->getBundleItemOriginPrice($product);
            $itemTax = (float) $product->getParentItem()->getTaxPercent();
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
     * Read requested Field
     *
     * @return array|string
     */
    private function getShippingPrice()
    {
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

        return self::prices()->encode(
            (float)    $shipAmount,
            (float)    round($shipTaxPercent, 2),
            null,
            $currencyCode,
            Mage::app()->getLocale()->currency($currencyCode)->getSymbol(),
            Mage::app()->getLocale()->currency($currencyCode)->getName()
        );
    }

    /**
     * Add or Update Given Product Order Line Data
     *
     * @param array $orderLineData OrderLine Data Array
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function setProductLine($orderLineData): void
    {
        //====================================================================//
        // Detect & Verify Product Id
        if (array_key_exists("product_id", $orderLineData)) {
            $productId = self::objects()->id($orderLineData["product_id"]);
            /** @var Mage_Catalog_Model_Product $model */
            $model = Mage::getModel('catalog/product');
            /** @var false|Mage_Catalog_Model_Product $product */
            $product = $model->load((int) $productId);
            //====================================================================//
            // Verify Product Id Is Valid
            if (!$product || ($product->getEntityId() !== $productId)) {
                $product = null;
            }
        } else {
            $product = null;
            $productId = null;
        }
        //====================================================================//
        // If Valid Product Given => Update Product Informations
        if ($product) {
            //====================================================================//
            // Verify Product Id Changed
            if ($this->orderItem->getProductId() !== $productId) {
                //====================================================================//
                // Update Order Item
                $this->orderItem
                    ->setProductId($product->getEntityId())
                    ->setProductType($product->getTypeId())
                    ->setName($product->getName())
                    ->setSku($product->getSku());
                $this->orderItemUpdate = true;
                Splash::log()->deb("Product Order Item Updated");
            }
            //====================================================================//
        // Update Line Without Product Id
        } else {
            if (array_key_exists("sku", $orderLineData)
                && ($this->orderItem->getName() !== $orderLineData["sku"])) {
                //====================================================================//
                // Update Order Item
                $this->orderItem
                    ->setProductId(0)
                    ->setProductType("")
                    ->setSku($orderLineData["sku"]);
                $this->orderItemUpdate = true;
            }
            if (array_key_exists("name", $orderLineData)
                && ($this->orderItem->getName() !== $orderLineData["name"])) {
                //====================================================================//
                // Update Order Item
                $this->orderItem
                    ->setProductId(0)
                    ->setProductType("")
                    ->setName($orderLineData["name"]);
                $this->orderItemUpdate = true;
                Splash::log()->deb("Custom Order Item Updated");
            }
        }
    }

    /**
     * Add or Update Given Order Line Informations
     *
     * @param array $orderLineData OrderLine Data Array
     *
     * @return void
     */
    private function setProductLineQty($orderLineData): void
    {
        //====================================================================//
        // Update Quantity Informations
        if (array_key_exists("qty_ordered", $orderLineData)) {
            //====================================================================//
            // Verify Qty Changed
            if ($this->orderItem->getQtyOrdered() !== $orderLineData["qty_ordered"]) {
                $this->orderItem->setQtyBackordered(0)
                    ->setTotalQtyOrdered($orderLineData["qty_ordered"])
                    ->setQtyOrdered($orderLineData["qty_ordered"])
                ;

                $shippedStates = array(MageOrder::STATE_PROCESSING, MageOrder::STATE_COMPLETE, MageOrder::STATE_CLOSED);
                if (in_array($this->object->getState(), $shippedStates, true)) {
                    $this->orderItem->setQtyShipped($orderLineData["qty_ordered"]);
                } else {
                    $this->orderItem->setQtyShipped(0);
                }

                $invoicedStates = array(MageOrder::STATE_COMPLETE, MageOrder::STATE_CLOSED);
                if (in_array($this->object->getState(), $invoicedStates, true)) {
                    $this->orderItem->setQtyInvoiced($orderLineData["qty_ordered"]);
                } else {
                    $this->orderItem->setQtyInvoiced(0);
                }

                $this->orderItemUpdate = true;
                $this->updateTotals = true;
            }
        }
    }

    /**
     * Add or Update Given Order Line Informations
     *
     * @param array $orderLineData OrderLine Data Array
     *
     * @return void
     */
    private function setProductLinePrice($orderLineData): void
    {
        //====================================================================//
        // Update Price Informations
        if (array_key_exists("unit_price", $orderLineData)) {
            $orderLinePrice = $orderLineData["unit_price"];
            //====================================================================//
            // Verify Price Changed
            if ($this->orderItem->getPrice() !== $orderLinePrice["ht"]) {
                $this->orderItem
                    ->setPrice($orderLinePrice["ht"])
                    ->setBasePrice($orderLinePrice["ht"])
                    ->setBaseOriginalPrice($orderLinePrice["ht"])
                    ->setOriginalPrice($orderLinePrice["ht"])
                ;
                $this->orderItemUpdate = true;
                $this->updateTotals = true;
            }
            //====================================================================//
            // Verify Tax Rate Changed
            if ($this->orderItem->getTaxPercent() !== $orderLinePrice["vat"]) {
                $this->orderItem->setTaxPercent($orderLinePrice["vat"]);
                $this->orderItemUpdate = true;
                $this->updateTotals = true;
            }
        }
    }

    /**
     * Add or Update Given Order Line Informations
     *
     * @param array $orderLineData OrderLine Data Array
     *
     * @return void
     */
    private function setProductLineDiscount($orderLineData): void
    {
        //====================================================================//
        // Update Discount Informations
        if (array_key_exists("discount_percent", $orderLineData)) {
            //====================================================================//
            // Verify Discount Percent Changed
            $delta = (float) $this->orderItem->getDiscountPercent() - (float) $orderLineData["discount_percent"];
            if (abs($delta) > 1E-3) {
                $this->orderItem->setDiscountPercent($orderLineData["discount_percent"]);
                $this->orderItemUpdate = true;
                $this->updateTotals = true;
            }
        }
    }

    /**
     * Add or Update Given Order Line Informations
     *
     * @return void
     */
    private function setProductLineTotals(): void
    {
        //====================================================================//
        // Update Row Total
        $subTotalHt = $this->orderItem->getPrice() * $this->orderItem->getQtyOrdered();
        $discountAmount = ($this->orderItem->getDiscountPercent() * $subTotalHt) / 100;
        $totalHt = $subTotalHt - $discountAmount;
        $taxAmount = $totalHt * $this->orderItem->getTaxPercent() / 100;
        $discountTax = $discountAmount * $this->orderItem->getTaxPercent() / 100;
        $totalTtc = $totalHt * (1 + $this->orderItem->getTaxPercent() / 100);

        //====================================================================//
        // Verify Total Changed
        if ($this->orderItem->getRowTotal() !== $totalHt) {
            $this->orderItem
                // ROW Totals
                ->setRowTotal($totalHt)
                ->setBaseRowTotal($totalHt)
                ->setRowTotalInclTax($totalTtc)
                ->setBaseRowTotalInclTax($totalTtc)
                // ROW Tax Amounts
                ->setTaxAmount($taxAmount)
                ->setBaseTaxAmount($taxAmount)
                // ROW Discounts Amounts
                ->setBaseDiscountAmount($discountAmount)
                ->setDiscountAmount($discountAmount)
                ->setBaseDiscountTaxAmount($discountTax)
                ->setDiscountTaxAmount($discountTax)
            ;
            $this->orderItemUpdate = true;
            $this->updateTotals = true;
        }
    }

    /**
     * Init Given Order Line Data For Update
     *
     * @return void
     */
    private function setOrderLineInit(): void
    {
        //====================================================================//
        // Read Next Order Product Line
        $this->orderItem = array_shift($this->orderItems);
        //====================================================================//
        // Empty => create New Line
        if (!$this->orderItem) {
            /** @var Mage_Sales_Model_Order_Item $model */
            $model = Mage::getModel('sales/order_item');
            //====================================================================//
            // create New Order Item
            $this->orderItem = $model
                ->setStoreId($this->object->getStore()->getStoreId())
                ->setQuoteItemId(0)
                ->setQuoteParentItemId(0)
                ->setOrder($this->object);
            //====================================================================//
            // Add Item to Order
            $this->object->addItem($this->orderItem);
            Splash::log()->deb("New Order Item created");
        }
    }

    /**
     * Add or Update Given Order Shipping Informations
     *
     * @param array $orderLineData OrderLine Data Array
     *
     * @return void
     */
    private function setShipping($orderLineData): void
    {
        //====================================================================//
        // Update Shipping Description
        if (array_key_exists("name", $orderLineData)) {
            //====================================================================//
            // Verify Discount Changed
            if ($this->object->getShippingDescription() !== $orderLineData["name"]) {
                $this->object->setShippingDescription($orderLineData["name"]);
                $this->needUpdate();
            }
        }

        //====================================================================//
        // Update Price Informations
        if (array_key_exists("unit_price", $orderLineData)) {
            $orderLinePrice = $orderLineData["unit_price"];
            //====================================================================//
            // Verify HT Price Changed
            if ($this->object->getShippingAmount() !== $orderLinePrice["ht"]) {
                $this->object
                    ->setShippingAmount($orderLinePrice["ht"])
                    ->setBaseShippingAmount($orderLinePrice["ht"]);
                $this->updateTotals = true;
                $this->needUpdate();
            }
            //====================================================================//
            // Verify Tax Rate Changed
            $taxAmount = $orderLinePrice["ttc"] - $orderLinePrice['ht'];
            if ($this->orderItem->getShippingTaxAmount() !== $taxAmount) {
                $this->orderItem->setShippingTaxAmount($taxAmount);
                $this->updateTotals = true;
                $this->needUpdate();
            }
        }
    }
}
