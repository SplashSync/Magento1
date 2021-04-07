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
 * Magento 1 Products Extra Fields Access
 */
trait ExtrasTrait
{
    /**
     * Build Fields using FieldFactory
     */
    protected function buildExtraFields(): void
    {
        //====================================================================//
        // Ean
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("ean")
            ->name("[OPT] Ean")
            ->microData("http://schema.org/Product", "gtin13")
            ->isReadOnly()
        ;
        //====================================================================//
        // Truncated Product Description
        $this->fieldsFactory()->create(SPL_T_TEXT)
            ->identifier("shorten_description")
            ->name("[OPT] Desc.")
            ->description("[OPTION] Truncated Short Description")
            ->group("Description")
            ->setMultilang(self::getDefaultLanguage())
            ->isReadOnly()
        ;
        //====================================================================//
        // Product Cost Price
        $this->fieldsFactory()->create(SPL_T_PRICE)
            ->identifier("cost")
            ->name("[OPT]  Cost HT"." (".Mage::app()->getStore()->getCurrentCurrencyCode().")")
            ->microData("http://schema.org/Product", "wholesalePrice")
            ->isReadOnly()
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
    protected function getExtraFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            case 'ean':
                $this->getData($fieldName);

                break;
            case 'shorten_description':
                $this->out[$fieldName] = substr((string) $this->object->getData('short_description'), 0, 128);

                break;
            case 'cost':
                //====================================================================//
                // Read Current Currency Code
                $currencyCode = Mage::app()->getStore()->getCurrentCurrencyCode();
                //====================================================================//
                // Build Price Array
                $this->out[$fieldName] = self::prices()->encode(
                    (float)  $this->object->getData('cost'),
                    $this->getProductTaxRate(),
                    null,
                    $currencyCode,
                    Mage::app()->getLocale()->currency($currencyCode)->getSymbol(),
                    Mage::app()->getLocale()->currency($currencyCode)->getName()
                );

                break;
            default:
                return;
        }

        unset($this->in[$key]);
    }
}
