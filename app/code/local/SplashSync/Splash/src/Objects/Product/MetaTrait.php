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
use Mage_Catalog_Model_Product_Status;
use Mage_Core_Model_Date;

/**
 * Magento 1 Products Meta Fields Access
 */
trait MetaTrait
{
    /**
     * Build Meta Fields using FieldFactory
     */
    protected function buildMetaFields(): void
    {
        //====================================================================//
        // Active => Product Is Enables & Visible
        $this->fieldsFactory()->Create(SPL_T_BOOL)
            ->Identifier("status")
            ->Group("Meta")
            ->Name("Enabled")
            ->MicroData("http://schema.org/Product", "offered")
            ->isListed();

        //====================================================================//
        // Active => Product Is available_for_order
        $this->fieldsFactory()->Create(SPL_T_BOOL)
            ->Identifier("available_for_order")
            ->Name("Available for order")
            ->Group("Meta")
            ->isReadOnly();

        //====================================================================//
        // On Sale
        $this->fieldsFactory()->Create(SPL_T_BOOL)
            ->Identifier("on_special")
            ->Name("On Sale")
            ->Group("Meta")
            ->MicroData("http://schema.org/Product", "onsale")
            ->isReadOnly();
    }

    /**
     * Read requested Field
     *
     * @param string $key Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    protected function getMetaFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            case 'status':
                $this->out[$fieldName] = !$this->object->isDisabled();

                break;
            case 'available_for_order':
                $this->out[$fieldName] = $this->object->getData("status")
                    && $this->object->getStockItem()->getIsInStock();

                break;
            case 'on_special':
                $current = new \DateTime();
                /** @var Mage_Core_Model_Date $model */
                $model = Mage::getModel('core/date');
                $from = $model->timestamp($this->object->getData("special_from_date"));
                if ($current->getTimestamp() < $from) {
                    $this->out[$fieldName] = false;

                    break;
                }
                $toTimestamp = $model->timestamp($this->object->getData("special_to_date"));
                if ($current->getTimestamp() < $toTimestamp) {
                    $this->out[$fieldName] = false;

                    break;
                }
                $this->out[$fieldName] = true;

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
     */
    protected function setMetaFields(string $fieldName, $data): void
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            //====================================================================//
            // Direct Writings
            case 'status':
                if ($this->object->isDisabled() && $data) {
                    $this->object->setData($fieldName, Mage_Catalog_Model_Product_Status::STATUS_ENABLED);
                    $this->needUpdate();
                } elseif (!$this->object->isDisabled() && !$data) {
                    $this->object->setData($fieldName, Mage_Catalog_Model_Product_Status::STATUS_DISABLED);
                    $this->needUpdate();
                }

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }
}
