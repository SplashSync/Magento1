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

namespace Splash\Local\Objects\Core;

use Mage;
use Mage_Tax_Model_Calculation;
use Splash\Core\SplashCore      as Splash;

/**
 * Magento 1 Object Prices Getter & Setters
 */
trait PricesTrait
{
    /**
     * Encode Magento Product Price
     *
     * @return array|string
     */
    protected function getProductPrice()
    {
        //====================================================================//
        // Read Current Currency Code
        $currencyCode = Mage::app()->getStore()->getCurrentCurrencyCode();
        //====================================================================//
        // Read Price
        if ($this->isVatIncluded()) {
            $priceTTC = (float)  $this->object->getPrice();
            $priceHT = null;
        } else {
            $priceTTC = null;
            $priceHT = (float)  $this->object->getPrice();
        }
        //====================================================================//
        // Build Price Array
        return self::prices()->encode(
            $priceHT,
            $this->getProductTaxRate(),
            $priceTTC,
            $currencyCode,
            Mage::app()->getLocale()->currency($currencyCode)->getSymbol(),
            Mage::app()->getLocale()->currency($currencyCode)->getName()
        );
    }

    /**
     * Update Magento Product Price
     *
     * @param array $splashPrice Splash Price Array Description
     *
     * @return void
     */
    protected function setProductPrice($splashPrice): void
    {
        //====================================================================//
        // Read Current Product Price (Via Out Buffer)
        /** @var array $currentPrice */
        $currentPrice = $this->getProductPrice();

        //====================================================================//
        // Compare Prices
        if (self::prices()->compare($currentPrice, $splashPrice)) {
            return;
        }

        //====================================================================//
        // Update Product Price if Required
        if ($this->isVatIncluded()) {
            $oldPrice = (float)  self::prices()->taxIncluded($currentPrice);
            $newPrice = (float)  self::prices()->taxIncluded($splashPrice);
        } else {
            $oldPrice = (float)  self::prices()->taxExcluded($currentPrice);
            $newPrice = (float)  self::prices()->taxExcluded($splashPrice);
        }
        if (abs($oldPrice - $newPrice) > 1E-6) {
            $this->object->setPrice($newPrice);
            $this->needUpdate();
        }

        //====================================================================//
        // Update Product Tax Class if Required
        $oldTaxId = $this->identifyPriceTaxClass((float) self::prices()->taxPercent($currentPrice));
        $newTaxId = $this->identifyPriceTaxClass((float) self::prices()->taxPercent($splashPrice));
        if ($oldTaxId != $newTaxId) {
            $this->object->setTaxClassId($newTaxId);
            $this->needUpdate();
        }
    }

    /**
     * Get Magento Product TYax Rate
     *
     * @return float
     */
    protected function getProductTaxRate(): float
    {
        //====================================================================//
        // Load Product Tax Rate
        $store = Mage::app()->getStore($this->object->getStore());
        /** @var Mage_Tax_Model_Calculation $taxCalculation */
        $taxCalculation = Mage::getModel('tax/calculation');
        $taxRequest = $taxCalculation->getRateRequest(null, null, null, $store);

        return (float)  $taxCalculation->getRate(
        /** @phpstan-ignore-next-line */
            $taxRequest->setProductClassId($this->object->getTaxClassId())
        );
    }

    /**
     * Check if Magento Prices Include VAT or Not
     *
     * @param string $context Context ( Products / Shipping )
     *
     * @return bool
     */
    private function isVatIncluded($context = 'Products'): bool
    {
        switch ($context) {
            case 'Shipping':
                return (bool)   Mage::getStoreConfig('tax/calculation/shipping_includes_tax');
            case 'Products':
            default:
                return (bool)   Mage::getStoreConfig('tax/calculation/price_includes_tax');
        }
    }

    /**
     * Identify Tax Class Id from Tax Percentile
     *
     * @param float $taxRate
     *
     * @return int
     */
    private function identifyPriceTaxClass($taxRate = 0)
    {
        //====================================================================//
        // No Tax Rate Applied
        if (0 == $taxRate) {
            return 0;
        }

        //====================================================================//
        // Load Products Tax Rates
        $store = Mage::app()->getStore($this->object->getStore());
        /** @var Mage_Tax_Model_Calculation $taxCalculation */
        $taxCalculation = Mage::getModel('tax/calculation');
        $taxRequest = $taxCalculation->getRateRequest(null, null, null, $store);
        /** @var float[] $availableTaxes */
        $availableTaxes = $taxCalculation->getRatesForAllProductTaxClasses($taxRequest);

        //====================================================================//
        // For Each Additional Tax Class
        $bestId = 0;
        $bestRate = 0;
        foreach ($availableTaxes as $txClassId => $txRate) {
            if (abs($taxRate - $txRate) < abs($taxRate - $bestRate)) {
                $bestId = $txClassId;
                $bestRate = $txRate;
            }
        }

        return $bestId;
    }
}
