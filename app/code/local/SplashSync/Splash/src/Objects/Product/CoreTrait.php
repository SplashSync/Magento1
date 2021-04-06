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

/**
 * Magento 1 Products Core Fields Access
 */
trait CoreTrait
{
    /**
     * Build Core Fields using FieldFactory
     */
    protected function buildCoreFields(): void
    {
        //====================================================================//
        // Reference
        $this->fieldsFactory()->Create(SPL_T_VARCHAR)
            ->Identifier("sku")
            ->Name('Reference - SKU')
            ->isListed()
            ->MicroData("http://schema.org/Product", "model")
            ->isRequired();

        //====================================================================//
        // Product Type Id
        $this->fieldsFactory()->Create(SPL_T_INT)
            ->Identifier("type_id")
            ->Name('Type Identifier')
            ->Description('Product Type Identifier')
            ->MicroData("http://schema.org/Product", "type")
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
    protected function getCoreFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // MAIN INFORMATIONS
            //====================================================================//
            case 'sku':
            case 'type_id':
                $this->getData($fieldName);

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
    protected function setCoreFields(string $fieldName, $data): void
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            //====================================================================//
            // MAIN INFORMATIONS
            //====================================================================//
            case 'sku':
                $this->setData($fieldName, $data);

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }
}
