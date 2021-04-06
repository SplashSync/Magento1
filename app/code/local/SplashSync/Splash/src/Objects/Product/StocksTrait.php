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

namespace Splash\Local\Objects\Product;

use Mage;
use Mage_Catalog_Model_Product;
use Splash\Core\SplashCore      as Splash;

/**
 * Magento 1 Products Stocks Fields Access
 */
trait StocksTrait
{
    /**
     * Build Fields using FieldFactory
     */
    protected function buildStocksFields(): void
    {
        //====================================================================//
        // PRODUCT STOCKS
        //====================================================================//

        //====================================================================//
        // Stock Reel
        $this->fieldsFactory()->create(SPL_T_INT)
            ->identifier("qty")
            ->name("Stock")
            ->group("Stocks")
            ->microData("http://schema.org/Offer", "inventoryLevel")
        ;
        //====================================================================//
        // Out of Stock Flag
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->identifier("outofstock")
            ->name('Out of stock')
            ->group("Stocks")
            ->microData("http://schema.org/ItemAvailability", "OutOfStock")
            ->isReadOnly()
        ;
        //====================================================================//
        // Minimum Order Quantity
        $this->fieldsFactory()->Create(SPL_T_INT)
            ->Identifier("min_sale_qty")
            ->Name('Min. Order Quantity')
            ->Group("Stocks")
            ->MicroData("http://schema.org/Offer", "eligibleTransactionVolume")
        ;
    }

    /**
     * Read requested Field
     *
     * @param string $key Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    protected function getStocksFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // PRODUCT STOCKS
            //====================================================================//
            // Stock Reel
            case 'qty':
            //====================================================================//
            // Minimum Order Quantity
            case 'min_sale_qty':
                $this->out[$fieldName] = (int) $this->object->getStockItem()->getData($fieldName);

                break;
            //====================================================================//
            // Out Of Stock
            case 'outofstock':
                $this->out[$fieldName] = $this->object->getStockItem()->getIsInStock() ? false : true;

                break;
            default:
                return;
        }

        unset($this->in[$key]);
    }

    /**
     * Write Given Fields
     *
     * @param string $fieldName Field Identifier / Name
     * @param mixed $data Field Data
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function setStocksFields(string $fieldName, $data): void
    {
        $updateStock = false;
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            //====================================================================//
            // PRODUCT STOCKS
            case 'qty':
            case 'min_sale_qty':
                //====================================================================//
                // If New PRODUCT => Reload Product to get Stock Item
                if (empty($this->object->getStockItem())) {
                    /** @var Mage_Catalog_Model_Product $model */
                    $model = Mage::getModel('catalog/product');
                    /** @var false|Mage_Catalog_Model_Product $product */
                    $product = $model->load($this->productId);
                    $stockItem = $product ? $product->getStockItem() : 0;
                } else {
                    $stockItem = $this->object->getStockItem();
                }
                //====================================================================//
                // Get Stock Item
                if (empty($stockItem)) {
                    break;
                }
                if ($stockItem->getData($fieldName) != $data) {
                    $stockItem->setData($fieldName, $data);
                    $updateStock = true;
                }

                break;
            default:
                return;
        }

        unset($this->in[$fieldName]);

        //====================================================================//
        // UPDATE PRODUCT STOCK
        //====================================================================//
        if (!$updateStock) {
            return;
        }
        //====================================================================//
        // If New PRODUCT => Set Stock/Warehouse Id
        if (!$stockItem->getStockId()) {
            $stockItem->setStockId(Mage::getStoreConfig('splashsync_splash_options/products/default_stock'));
        } else {
            $stockItem->setStockId($stockItem->getStockId());
        }
        //====================================================================//
        // Save PRODUCT Stock Item
        $stockItem->save();
        //====================================================================//
        // Verify Item Saved
        if ($stockItem->_hasDataChanges) {
            Splash::log()->errTrace("Unable to Update Stocks (".$this->object->getEntityId().").");
        }
    }
}
