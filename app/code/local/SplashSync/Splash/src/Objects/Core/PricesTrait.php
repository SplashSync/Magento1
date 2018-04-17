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

namespace Splash\Local\Objects\Core;

// Magento Namespaces
use Mage;

use Splash\Core\SplashCore      as Splash;

/**
 * @abstract    Magento 1 Object Prices Getter & Setters
 */
trait PricesTrait
{
    
    /**
     *  @abstract     Check if Magento Prices Include VAT or Not
     *
     *  @param        string    $Context                Context ( Products / Shipping )
     *
     *  @return       bool
     */
    private function isVatIncluded($Context = 'Products')
    {
        switch ($Context) {
            case 'Shipping':
                return (bool)   Mage::getStoreConfig('tax/calculation/shipping_includes_tax');
                
            case 'Products':
            default:
                return (bool)   Mage::getStoreConfig('tax/calculation/price_includes_tax');
        }
    }
    
    
    /**
     *  @abstract     Encode Magento Product Price
     *
     *  @return       array
     */
    public function getProductPrice()
    {
        //====================================================================//
        // Load Product Appliable Tax Rate
        $Store              =   Mage::app()->getStore($this->Object->getStore());
        $TaxCalculation     =   Mage::getModel('tax/calculation');
        $TaxRequest         =   $TaxCalculation->getRateRequest(null, null, null, $Store);
        $Tax                =   (double)  $TaxCalculation->getRate(
            $TaxRequest->setProductClassId($this->Object->getTaxClassId())
        );

        //====================================================================//
        // Read Current Currency Code
        $CurrencyCode   =   Mage::app()->getStore()->getCurrentCurrencyCode();
        //====================================================================//
        // Read Price
        if ($this->isVatIncluded()) {
            $PriceTTC   = (double)  $this->Object->getPrice();
            $PriceHT    = null;
        } else {
            $PriceTTC   = null;
            $PriceHT    = (double)  $this->Object->getPrice();
        }

        //====================================================================//
        // Build Price Array
        return self::prices()->encode(
            $PriceHT,
            $Tax,
            $PriceTTC,
            $CurrencyCode,
            Mage::app()->getLocale()->currency($CurrencyCode)->getSymbol(),
            Mage::app()->getLocale()->currency($CurrencyCode)->getName()
        );
    }
    
    
    /**
     * @abstract     Update Magento Product Price
     *
     * @param   array   $SplashPrice        Splash Price Array Description
     *
     * @return       array
     */
    public function setProductPrice($SplashPrice)
    {
        //====================================================================//
        // Read Current Product Price (Via Out Buffer)
        $CurrentPrice   =   $this->getProductPrice();
        
        //====================================================================//
        // Compare Prices
        if (self::prices()->Compare($CurrentPrice, $SplashPrice)) {
            return;
        }
        
        //====================================================================//
        // Update Product Price if Required
        if ($this->isVatIncluded()) {
            $OldPrice   = (double)  self::prices()->TaxIncluded($CurrentPrice);
            $NewPrice   = (double)  self::prices()->TaxIncluded($SplashPrice);
        } else {
            $OldPrice   = (double)  self::prices()->TaxExcluded($CurrentPrice);
            $NewPrice   = (double)  self::prices()->TaxExcluded($SplashPrice);
        }
        if (abs($OldPrice - $NewPrice) > 1E-6) {
            $this->Object->setPrice($NewPrice);
            $this->needUpdate();
        }

        //====================================================================//
        // Update Product Tax Class if Required
        $OldTaxId     = $this->identifyPriceTaxClass(self::prices()->TaxPercent($CurrentPrice));
        $NewTaxId     = $this->identifyPriceTaxClass(self::prices()->TaxPercent($SplashPrice));
        if ($OldTaxId != $NewTaxId) {
            $this->Object->setTaxClassId($NewTaxId);
            $this->needUpdate();
        }
    }
    
    
    /**
     *  @abstract     Identify Tax Class Id from Tax Percentile
     *
     *  @return       int
     */
    private function identifyPriceTaxClass($Tax_Percent = 0)
    {
        //====================================================================//
        // No Tax Rate Applied
        if ($Tax_Percent == 0) {
            return 0;
        }
        
        //====================================================================//
        // Load Products Appliable Tax Rates
        $Store              =   Mage::app()->getStore($this->Object->getStore());
        $TaxCalculation     =   Mage::getModel('tax/calculation');
        $TaxRequest         =   $TaxCalculation->getRateRequest(null, null, null, $Store);
        $AvailableTaxes     =   $TaxCalculation->getRatesForAllProductTaxClasses($TaxRequest);

        //====================================================================//
        // For Each Additionnal Tax Class
        $BestId     =   0;
        $BestRate   =   0;
        foreach ($AvailableTaxes as $TaxClassId => $TaxRate) {
            if (abs($Tax_Percent - $TaxRate) <  abs($Tax_Percent - $BestRate)) {
                $BestId     =   $TaxClassId;
                $BestRate   =   $TaxRate;
            }
        }
        
        return $BestId;
    }
}
