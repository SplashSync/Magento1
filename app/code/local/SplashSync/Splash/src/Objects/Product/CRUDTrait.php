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
use Mage_Catalog_Exception;
use Mage_Catalog_Model_Product;
use Mage_Catalog_Model_Product_Status;
use Mage_Catalog_Model_Product_Type;
use Splash\Core\SplashCore      as Splash;

/**
 * Magento 1 Customers CRUD Functions
 */
trait CRUDTrait
{
    //====================================================================//
    // General Class Variables
    //====================================================================//

    /**
     * Magento Product Class Id
     *
     * @var int
     */
    protected $productId;

    /**
     * Magento Product Attribute Class Id
     *
     * @var int
     */
    protected $attributeId;

    /**
     * Load Request Object
     *
     * @param string $objectId Object id
     *
     * @return false|object
     */
    public function load($objectId)
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace();
        $product = false;
        //====================================================================//
        // Decode Product Id
        $this->productId = self::getId((int) $objectId);
        $this->attributeId = self::getAttribute((int) $objectId);
        //====================================================================//
        // Safety Checks
        if (empty($objectId) || empty($this->productId)) {
            return Splash::log()->errTrace("Missing Product Id.");
        }
        //====================================================================//
        // If $id Given => Load Product Object From DataBase
        //====================================================================//
        if (!empty($this->productId)) {
            //====================================================================//
            // Init Object
            /** @var Mage_Catalog_Model_Product $model */
            $model = Mage::getModel('catalog/product');
            /** @var false|Mage_Catalog_Model_Product $product */
            $product = $model->load($this->productId);
            if (!$product || ($product->getEntityId() != $this->productId)) {
                return Splash::log()->errTrace("Unable to fetch Product (".$this->productId.")");
            }
        }

        return $product;
    }

    /**
     * Create Request Object
     *
     * @return false|object New Object
     */
    public function create()
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace();
        //====================================================================//
        // Check Required Fields
        if (!$this->verifyRequiredFields()) {
            return false;
        }
        //====================================================================//
        // Init Product Class
        /** @var Mage_Catalog_Model_Product $product */
        $product = Mage::getModel('catalog/product');
        $product
            // Setup Product in default website
            ->setWebsiteIds($this->getSplashOriginWebsiteIds())
            // Setup Product Status
            ->setStatus(Mage_Catalog_Model_Product_Status::STATUS_ENABLED);
        //====================================================================//
        // Init Product Entity
        $product->setAttributeSetId(Mage::getStoreConfig('splashsync_splash_options/products/attribute_set'));
        //====================================================================//
        // Init Product Type => Always Simple when Created formOutside Magento
        $product->setTypeId((Mage_Catalog_Model_Product_Type::TYPE_SIMPLE));
        $product->setData("sku", $this->in["sku"]);
        //====================================================================//
        // Save Object
        try {
            $product->save();
        } catch (Mage_Catalog_Exception $ex) {
            Splash::log()->report($ex);

            return false;
        }
        $this->productId = $product->getEntityId();

        return $product;
    }

    /**
     * Update Request Object
     *
     * @param bool $needed Is This Update Needed
     *
     * @return false|string Object Id
     */
    public function update($needed)
    {
        return $this->coreUpdate($needed);
    }

    /**
     * Delete requested Object
     *
     * @param string $objectId Object Id.  If NULL, Object needs to be created.
     *
     * @return bool
     */
    public function delete($objectId = null)
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace();
        //====================================================================//
        // Decode Product Id
        if (!empty($objectId)) {
            $this->productId = $this->getId((int) $objectId);
            $this->attributeId = $this->getAttribute((int) $objectId);
        } else {
            return Splash::log()->err("ErrSchWrongObjectId", __FUNCTION__);
        }
        //====================================================================//
        // Execute Generic Magento Delete Function ...
        return $this->coreDelete('catalog/product', $this->productId);
    }

    //====================================================================//
    // Product COMMON Local Functions
    //====================================================================//

    /**
     * Convert id_product & id_product_attribute pair
     *
     * @param int $productId   Product Identifier (Int10)
     * @param int $attributeId Product Combinaison Identifier (Int10)
     *
     * @return int 0 if KO, >0 if OK (Int32)
     */
    public function getUnikId($productId = null, $attributeId = 0)
    {
        if (is_null($productId)) {
            return $this->productId + ($this->attributeId << 20);
        }

        return $productId + ($attributeId << 20);
    }

    /**
     * Revert Unique Id to decode id_product
     *
     * @param int $objectId Product Unique Id (Int32)
     *
     * @return int 0 if KO, >0 if OK (Int10)
     */
    public static function getId($objectId)
    {
        return $objectId & 0xFFFFF;
    }

    /**
     * Revert Unique Id to decode id_product_attribute
     *
     * @param int $objectId Product Unique Id (Int32)
     *
     * @return int 0 if KO, >0 if OK (Int10)
     */
    public static function getAttribute($objectId)
    {
        return $objectId >> 20;
    }
}
