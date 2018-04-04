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

use Mage;

/**
 * @abstract    Magento 1 Products Stocks Fields Access
 */
trait StocksTrait {
    

    /**
    *   @abstract     Build Fields using FieldFactory
    */
    private function buildStocksFields() {
        
        //====================================================================//
        // PRODUCT STOCKS
        //====================================================================//
        
        //====================================================================//
        // Stock Reel
        $this->FieldsFactory()->Create(SPL_T_INT)
                ->Identifier("qty")
                ->Name("Stock")
                ->Group("Stocks")
                ->MicroData("http://schema.org/Offer","inventoryLevel")
                ->isListed();

        //====================================================================//
        // Out of Stock Flag
        $this->FieldsFactory()->Create(SPL_T_BOOL)
                ->Identifier("outofstock")
                ->Name('Out of stock')
                ->Group("Stocks")
                ->MicroData("http://schema.org/ItemAvailability","OutOfStock")
                ->ReadOnly();
                
        //====================================================================//
        // Minimum Order Quantity
        $this->FieldsFactory()->Create(SPL_T_INT)
                ->Identifier("min_sale_qty")
                ->Name('Min. Order Quantity')
                ->Group("Stocks")
                ->MicroData("http://schema.org/Offer","eligibleTransactionVolume");
        
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
    private function getStocksFields($Key,$FieldName) {
        //====================================================================//
        // READ Fields
        switch ($FieldName)
        {
            //====================================================================//
            // PRODUCT STOCKS
            //====================================================================//
            // Stock Reel
            case 'qty':
            //====================================================================//
            // Minimum Order Quantity
            case 'min_sale_qty':
                $this->Out[$FieldName] = (int) $this->Object->getStockItem()->getData($FieldName);                
                break;
            //====================================================================//
            // Out Of Stock
            case 'outofstock':
                $this->Out[$FieldName] = $this->Object->getStockItem()->getIsInStock() ? False : True;
                break;
            default:
                return;
        }
        
        unset($this->In[$Key]);
    }    


    /**
     *  @abstract     Write Given Fields
     * 
     *  @param        string    $FieldName              Field Identifier / Name
     *  @param        mixed     $Data                   Field Data
     * 
     *  @return         none
     */
    private function setStocksFields($FieldName,$Data) 
    {
        $UpdateStock    = False;
        //====================================================================//
        // WRITE Field
        switch ($FieldName)
        {
            //====================================================================//
            // PRODUCT STOCKS
            case 'qty':
            case 'min_sale_qty':
                //====================================================================//
                // If New PRODUCT => Reload Product to get Stock Item 
                if ( empty($this->Object->getStockItem()) ) {
                    $StockItem = Mage::getModel('catalog/product')->load($this->ProductId)->getStockItem();
                } else {
                    $StockItem      = $this->Object->getStockItem();
                }                
                //====================================================================//
                // Get Stock Item 
                if ( empty($StockItem) ) {
                    break;
                }
                if ( $StockItem->getData($FieldName) != $Data ) {
                    $StockItem->setData($FieldName, $Data);
                    $UpdateStock = True;
                }  
                break;

            default:
                return;
        }
        
        unset($this->In[$FieldName]);
        
        //====================================================================//
        // UPDATE PRODUCT STOCK 
        //====================================================================//
        if ( !$UpdateStock ) {
            return;
        }
        //====================================================================//
        // If New PRODUCT => Set Stock/Warehouse Id 
        if (!$StockItem->getStockId()) {
            $StockItem->setStockId(Mage::getStoreConfig('splashsync_splash_options/products/default_stock'));
        } else {
            $StockItem->setStockId($StockItem->getStockId());
        }
        //====================================================================//
        // Save PRODUCT Stock Item     
        $StockItem->save();
        //====================================================================//
        // Verify Item Saved
        if ( $StockItem->_hasDataChanges ) {  
            return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,"Unable to Update Stocks (" . $this->Object->getEntityId() . ").");
        }
    }


    
}
