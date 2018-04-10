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

namespace Splash\Local\Objects\Invoice;

use Mage;

/**
 * @abstract    Magento 1 Invoice Core Fields Access
 */
trait CoreTrait
{
    
    /**
    *   @abstract     Build Core Fields using FieldFactory
    */
    private function buildCoreFields()
    {
        
        //====================================================================//
        // Customer Object
        $this->FieldsFactory()->Create(self::Objects()->Encode("ThirdParty", SPL_T_ID))
                ->Identifier("customer_id")
                ->Name('Customer')
                ->MicroData("http://schema.org/Invoice", "customer")
                ->ReadOnly();

        //====================================================================//
        // Customer Name
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("customer_name")
                ->Name('Customer Name')
                ->MicroData("http://schema.org/Invoice", "customer")
                ->isListed()
                ->ReadOnly();

        
        //====================================================================//
        // Order Object
        $this->FieldsFactory()->Create(self::Objects()->Encode("Order", SPL_T_ID))
                ->Identifier("order_id")
                ->Name('Order')
                ->MicroData("http://schema.org/Invoice", "referencesOrder")
                ->isRequired();
        
        //====================================================================//
        // Invoice Reference
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("increment_id")
                ->Name('Number')
                ->MicroData("http://schema.org/Invoice", "confirmationNumber")
                ->IsListed();

        //====================================================================//
        // Order Reference
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("reference")
                ->Name('Reference')
                ->MicroData("http://schema.org/Order", "orderNumber")
                ->ReadOnly();

        //====================================================================//
        // Order Date
        $this->FieldsFactory()->Create(SPL_T_DATE)
                ->Identifier("created_at")
                ->Name("Date")
                ->MicroData("http://schema.org/Order", "orderDate")
                ->isRequired()
                ->IsListed();
    }
    
    /**
     *  @abstract     Read requested Field
     *
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     *
     *  @return         none
     */
    private function getCoreFields($Key, $FieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($FieldName) {
            //====================================================================//
            // Direct Readings
            case 'increment_id':
                $this->getData($FieldName);
                break;
            //====================================================================//
            // Order Reference Number
            case 'number':
                $this->Out[$FieldName] = $this->Object->getOrderIncrementId();
                break;
            //====================================================================//
            // Customer Object Id Readings
            case 'customer_id':
                $this->Out[$FieldName] = self::Objects()->Encode("ThirdParty", $this->Object->getOrder()->getData($FieldName));
                break;
            //====================================================================//
            // Customer Name
            case 'customer_name':
                $this->Out[$FieldName] = $this->Object->getOrder()->getCustomerName();
                break;
            //====================================================================//
            // Object Object Id Readings
            case 'order_id':
                $this->Out[$FieldName] = self::Objects()->Encode("Order", $this->Object->getData($FieldName));
                break;
            //====================================================================//
            // Order Official Date
            case 'created_at':
                $this->Out[$FieldName] = date(SPL_T_DATECAST, Mage::getModel("core/date")->timestamp($this->Object->getData($FieldName)));
                break;
            case 'reference':
                $this->getSimple($FieldName, "Order");
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
    private function setCoreFields($FieldName, $Data)
    {
        //====================================================================//
        // WRITE Field
        switch ($FieldName) {
            //====================================================================//
            // Direct Readings
            case 'increment_id':
                if ($this->Object->getData($FieldName) != $Data) {
                    $this->Object->setData($FieldName, $Data);
                    $this->update = true;
                }
                break;
            
            //====================================================================//
            // Order Official Date
            case 'created_at':
                $CurrentDate = date(SPL_T_DATECAST, Mage::getModel("core/date")->timestamp($this->Object->getData($FieldName)));
                if ($CurrentDate != $Data) {
                    $this->Object->setData($FieldName, $Data);
                    $this->update = true;
                }
                break;
                    
            //====================================================================//
            // Parent Order Id
            case 'order_id':
                $OrderId = self::ObjectId_DecodeId($Data);
                if ($this->Object->getOrder()->getId() == $OrderId) {
                    break;
                }
                //====================================================================//
                // Load Order Object
                $NewOrder = Mage::getModel('sales/order')->load($OrderId);
                if ($NewOrder->getEntityId() !== $OrderId) {
                    break;
                }
                //====================================================================//
                //Update Customer Id
                $this->Object->setOrder($NewOrder);
                $this->update = true;
                break;
                
            default:
                return;
        }
        unset($this->In[$FieldName]);
    }
}
