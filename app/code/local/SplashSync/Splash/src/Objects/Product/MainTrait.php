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

namespace Splash\Local\Objects\Product;

// Magento Namespaces
use Mage;

/**
 * @abstract    Magento 1 Products Main Fields Access
 */
trait MainTrait {
    
    
    
    
    /**
    *   @abstract     Build Address Fields using FieldFactory
    */
    private function buildMainFields() {
        //====================================================================//
        // PRODUCT SPECIFICATIONS
        //====================================================================//

        //====================================================================//
        // Weight
        $this->FieldsFactory()->Create(SPL_T_DOUBLE)
                ->Identifier("weight")
                ->Name("Weight")
                ->MicroData("http://schema.org/Product","weight");
        
        //====================================================================//
        // PRICES INFORMATIONS
        //====================================================================//
        
        //====================================================================//
        // Product Selling Price
        $this->FieldsFactory()->Create(SPL_T_PRICE)
                ->Identifier("price")
                ->Name("Selling Price HT" . " (" . Mage::app()->getStore()->getCurrentCurrencyCode() . ")")
                ->MicroData("http://schema.org/Product","price")
                ->isListed();
        
        //====================================================================//
        // WholeSale Price
//        $this->FieldsFactory()->Create(SPL_T_PRICE)
//                ->Identifier("price-wholesale")
//                ->Name($this->spl->l("Supplier Price") . " (" . $this->Currency->sign . ")")
//                ->MicroData("http://schema.org/Product","wholesalePrice");
                
        return;
    }

    /**
     *  @abstract     Read requested Field
     * 
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     * 
     *  @return         none
     */
    private function getMainFields($Key,$FieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($FieldName)
        {
            
                //====================================================================//
                // PRODUCT SPECIFICATIONS
                //====================================================================//
//                case 'height':
//                case 'depth':
//                case 'width':
                case 'weight':
                    $this->Out[$FieldName] = (double) $this->Object->getData($FieldName);                
                    break;
                
                //====================================================================//
                // PRICE INFORMATIONS
                //====================================================================//

                case 'price':
                    //====================================================================//
                    // Load Product Appliable Tax Rate
                    $Store              =   Mage::app()->getStore($this->Object->getStore());
                    $TaxCalculation     =   Mage::getModel('tax/calculation');
                    $TaxRequest         =   $TaxCalculation->getRateRequest(null, null, null, $Store);
                    $Tax                =   (double)  $TaxCalculation->getRate(
                            $TaxRequest->setProductClassId($this->Object->getTaxClassId())
                    );                    
                    //====================================================================//
                    // Read HT Price
                    $PriceHT    = (double)  $this->Object->getPrice();
                    //====================================================================//
                    // Read Current Currency Code
                    $CurrencyCode   =   Mage::app()->getStore()->getCurrentCurrencyCode();
                    //====================================================================//
                    // Build Price Array
                    $this->Out[$FieldName] = self::Prices()->Encode(
                            $PriceHT,$Tax,Null,
                            $CurrencyCode,
                            Mage::app()->getLocale()->currency($CurrencyCode)->getSymbol(),
                            Mage::app()->getLocale()->currency($CurrencyCode)->getName());
                    break;
                
            default:
                return;
        }
        
        if (!is_null($Key)) {
            unset($this->In[$Key]);
        }
    }
    
    
    /**
     *  @abstract     Write Given Fields
     * 
     *  @param        string    $FieldName              Field Identifier / Name
     *  @param        mixed     $Data                   Field Data
     * 
     *  @return         none
     */
    private function setMainFields($FieldName,$Data) 
    {
        //====================================================================//
        // WRITE Field
        switch ($FieldName)
        {
            //====================================================================//
            // PRODUCT SPECIFICATIONS
            //====================================================================//
            case 'weight':
                if ( abs ( (double) $this->Object->getData($FieldName) - (double) $Data ) > 1E-3 ) {
                    $this->Object->setData($FieldName, $Data);
                    $this->needUpdate();
                }  
                break;
            //====================================================================//
            // PRICES INFORMATIONS
            //====================================================================//
            case 'price':
                //====================================================================//
                // Read Current Product Price (Via Out Buffer)
                $this->getMainFields(Null,"price");
                //====================================================================//
                // Compare Prices
                if ( $this->Prices()->Compare($this->Out["price"],$Data) ) {
                    break; 
                }
                //====================================================================//
                // Update HT Price if Required
                if ( abs ( (double) $this->Out["price"]["ht"] - (double) $Data["ht"] ) > 1E-6 ) {
                    $this->Object->setPrice($Data["ht"]);
                    $this->needUpdate();
                }  
                break;   
                
            default:
                return;
        }
        unset($this->In[$FieldName]);
    }
    
}
