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
                case 'weight':
                    $this->Out[$FieldName] = (double) $this->Object->getData($FieldName);                
                    break;
                
                //====================================================================//
                // PRICE INFORMATIONS
                //====================================================================//
                case 'price':
                    $this->Out[$FieldName] = $this->getProductPrice();
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
                $this->setProductPrice($Data);
                break;   
                
            default:
                return;
        }
        unset($this->In[$FieldName]);
    }
    
}
