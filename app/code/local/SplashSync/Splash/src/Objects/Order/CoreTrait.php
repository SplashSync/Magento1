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

namespace Splash\Local\Objects\Order;

use Mage;

/**
 * @abstract    Magento 1 Order Core Fields Access
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
        $this->fieldsFactory()->Create(self::objects()->Encode("ThirdParty", SPL_T_ID))
                ->Identifier("customer_id")
                ->Name('Customer')
                ->MicroData("http://schema.org/Organization", "ID")
                ->isRequired();
        
        //====================================================================//
        // Reference
        $this->fieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("increment_id")
                ->Name('Reference')
                ->MicroData("http://schema.org/Order", "orderNumber")
                ->isListed();

        //====================================================================//
        // Order Date
        $this->fieldsFactory()->Create(SPL_T_DATE)
                ->Identifier("created_at")
                ->Name("Date")
                ->MicroData("http://schema.org/Order", "orderDate")
                ->isListed();
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
            // Customer Object Id Readings
            case 'customer_id':
                $this->Out[$FieldName] = self::objects()->Encode("ThirdParty", $this->Object->getData($FieldName));
                break;
            //====================================================================//
            // Order Official Date
            case 'created_at':
                $this->Out[$FieldName] = date(SPL_T_DATECAST, Mage::getModel("core/date")->timestamp($this->Object->getData($FieldName)));
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
                $this->setData($FieldName, $Data);
                break;
            
            //====================================================================//
            // Order Official Date
            case 'created_at':
                $CurrentDate = date(SPL_T_DATECAST, Mage::getModel("core/date")->timestamp($this->Object->getData($FieldName)));
                if ($CurrentDate != $Data) {
                    $this->Object->setData($FieldName, Mage::getModel("core/date")->gmtDate(null, $Data));
                    $this->needUpdate();
                }
                break;
                    
            //====================================================================//
            // Order Company Id
            case 'customer_id':
                $CustomerId = self::objects()->Id($Data);
                if ($this->Object->getCustomerId() != $CustomerId) {
                    //====================================================================//
                    // Load Customer Object
                    $NewCustomer = Mage::getModel("customer/customer")->load($CustomerId);
                    //====================================================================//
                    //Update Customer Id
                    $this->Object->setCustomer(Mage::getModel("customer/customer")->load($CustomerId));
                    //====================================================================//
                    //Update Customer Infos
                    $this->Object->setCustomerEmail($NewCustomer->getEmail());
                    $this->Object->setCustomerFirstname($NewCustomer->getFirstname());
                    $this->Object->setCustomerLastname($NewCustomer->getLastname());
                    $this->Object->setCustomerMiddlename($NewCustomer->getMiddlename());
                    $this->Object->setCustomerPrefix($NewCustomer->getPrefix());
                    $this->Object->setCustomerSufix($NewCustomer->getSufix());
                    
                    $this->needUpdate();
                }
                break;
                
            default:
                return;
        }
        unset($this->In[$FieldName]);
    }
}
