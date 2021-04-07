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

/**
 * Magento 1 Products Main Fields Access
 */
trait MainTrait
{
    /**
     * Build Fields using FieldFactory
     */
    protected function buildMainFields(): void
    {
        //====================================================================//
        // PRODUCT SPECIFICATIONS
        //====================================================================//

        //====================================================================//
        // Weight
        $this->fieldsFactory()->create(SPL_T_DOUBLE)
            ->identifier("weight")
            ->name("Weight")
            ->microData("http://schema.org/Product", "weight")
        ;

        //====================================================================//
        // PRICES INFORMATIONS
        //====================================================================//

        //====================================================================//
        // Product Selling Price
        $this->fieldsFactory()->create(SPL_T_PRICE)
            ->identifier("price")
            ->name("Selling Price HT"." (".Mage::app()->getStore()->getCurrentCurrencyCode().")")
            ->microData("http://schema.org/Product", "price")
            ->isListed()
        ;
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    protected function getMainFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
                //====================================================================//
                // PRODUCT SPECIFICATIONS
                //====================================================================//
            case 'weight':
                $this->out[$fieldName] = (float) $this->object->getData($fieldName);

                break;
                //====================================================================//
                // PRICE INFORMATIONS
                //====================================================================//
            case 'price':
                $this->out[$fieldName] = $this->getProductPrice();

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
     * @param mixed  $data      Field Data
     *
     * @return void
     */
    protected function setMainFields(string $fieldName, $data): void
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            //====================================================================//
            // PRODUCT SPECIFICATIONS
            //====================================================================//
            case 'weight':
                if (abs((float) $this->object->getData($fieldName) - (float) $data) > 1E-3) {
                    $this->object->setData($fieldName, $data);
                    $this->needUpdate();
                }

                break;
            //====================================================================//
            // PRICES INFORMATIONS
            //====================================================================//
            case 'price':
                $this->setProductPrice($data);

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }
}
