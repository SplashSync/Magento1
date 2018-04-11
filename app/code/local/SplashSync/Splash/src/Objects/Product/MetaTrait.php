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
use Mage_Catalog_Model_Product_Status;

/**
 * @abstract    Magento 1 Products Meta Fields Access
 */
trait MetaTrait
{
    

    /**
    *   @abstract     Build Meta Fields using FieldFactory
    */
    private function buildMetaFields()
    {
        
        //====================================================================//
        // Active => Product Is Enables & Visible
        $this->FieldsFactory()->Create(SPL_T_BOOL)
                ->Identifier("status")
                ->Group("Meta")
                ->Name("Enabled")
                ->MicroData("http://schema.org/Product", "offered")
                ->isListed();
        
        //====================================================================//
        // Active => Product Is available_for_order
        $this->FieldsFactory()->Create(SPL_T_BOOL)
                ->Identifier("available_for_order")
                ->Name("Available for order")
                ->Group("Meta")
                ->isReadOnly();
        
        //====================================================================//
        // On Sale
        $this->FieldsFactory()->Create(SPL_T_BOOL)
                ->Identifier("on_special")
                ->Name("On Sale")
                ->Group("Meta")
                ->MicroData("http://schema.org/Product", "onsale")
                ->isReadOnly();
    }

    /**
     *  @abstract     Read requested Field
     *
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     *
     *  @return         none
     */
    private function getMetaFields($Key, $FieldName)
    {

        //====================================================================//
        // READ Fields
        switch ($FieldName) {
            case 'status':
                $this->Out[$FieldName] = !$this->Object->isDisabled();
                break;
            case 'available_for_order':
                $this->Out[$FieldName] = $this->Object->getData("status") && $this->Object->getStockItem()->getIsInStock();
                break;
            case 'on_special':
                $Current    = new \DateTime();
                $From       = Mage::getModel("core/date")->timestamp($this->Object->getData("special_from_date"));
                if ($Current->getTimestamp() < $From) {
                    $this->Out[$FieldName] = false;
                    break;
                }
                $toTimestamp    = Mage::getModel("core/date")->timestamp($this->Object->getData("special_to_date"));
                if ($Current->getTimestamp() < $toTimestamp) {
                    $this->Out[$FieldName] = false;
                    break;
                }
                $this->Out[$FieldName] = true;
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
    private function setMetaFields($FieldName, $Data)
    {
        //====================================================================//
        // WRITE Field
        switch ($FieldName) {
            //====================================================================//
            // Direct Writtings
            case 'status':
                if ($this->Object->isDisabled() && $Data) {
                    $this->Object->setData($FieldName, Mage_Catalog_Model_Product_Status::STATUS_ENABLED);
                    $this->needUpdate();
                } elseif (!$this->Object->isDisabled() && !$Data) {
                    $this->Object->setData($FieldName, Mage_Catalog_Model_Product_Status::STATUS_DISABLED);
                    $this->needUpdate();
                }
                break;
                
            default:
                return;
        }
        unset($this->In[$FieldName]);
    }
}
