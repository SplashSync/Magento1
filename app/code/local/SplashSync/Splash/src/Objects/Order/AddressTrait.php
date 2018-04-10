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

// Magento Namespaces
use Mage;

/**
 * @abstract    Magento 1 Order Address Fields Access
 */
trait AddressTrait
{
    
    /**
    *   @abstract     Build Address Fields using FieldFactory
    */
    private function buildAddressFields()
    {
        
        //====================================================================//
        // Billing Address
        $this->FieldsFactory()->Create(self::Objects()->Encode("Address", SPL_T_ID))
                ->Identifier("billing_address_id")
                ->Name('Billing Address ID')
                ->MicroData("http://schema.org/Order", "billingAddress")
                ->isRequired();
        
        //====================================================================//
        // Shipping Address
        $this->FieldsFactory()->Create(self::Objects()->Encode("Address", SPL_T_ID))
                ->Identifier("shipping_address_id")
                ->Name('Shipping Address ID')
                ->MicroData("http://schema.org/Order", "orderDelivery");
    }
    

    /**
     *  @abstract     Read requested Field
     *
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     *
     *  @return         none
     */
    private function getAddressFields($Key, $FieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($FieldName) {
            //====================================================================//
            // Billing/Shipping Address Object Id Readings
            case 'billing_address_id':
            case 'shipping_address_id':
                if ($FieldName == "billing_address_id") {
                    $Address    =  $this->Object->getBillingAddress();
                } else {
                    $Address    =  $this->Object->getShippingAddress();
                }
                if ($Address && $Address->getCustomerAddressId()) {
                    $this->Out[$FieldName] = self::Objects()->Encode("Address", $Address->getCustomerAddressId());
                    break;
                }
                $this->Out[$FieldName] = null;
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
    private function setAddressFields($FieldName, $Data)
    {
        //====================================================================//
        // WRITE Field
        switch ($FieldName) {
            //====================================================================//
            // Billing/Shipping Address Writting
            case 'billing_address_id':
                $this->setAddressContents('billing', self::Objects()->Id($Data));
                break;
            case 'shipping_address_id':
                //====================================================================//
                // Retrieve Address Object Id
                $AdressId = self::Objects()->Id($Data);
                //====================================================================//
                // Setup Address Object & Set Order as "Non Virtual" => With Shipping
                if ($AdressId > 0) {
                    $this->setAddressContents('shipping', self::Objects()->Id($Data));
                    $this->Object->setIsVirtual(false);
                //====================================================================//
                // No Address Setup & Set Order as "Virtual" => No Shipping
                } else {
                    $this->Object->setIsVirtual(true);
                }
                break;
                
            default:
                return;
        }
        unset($this->In[$FieldName]);
    }
    

    /**
     *  @abstract     Set Given Order Address
     *
     *  @return         none
     */
    private function setAddressContents($Type, $AddressId)
    {
        
        //====================================================================//
        // Read Original Billing/Shipping Order Address
        if ($Type === "billing") {
            $Address    = $this->Object->getBillingAddress();
        } elseif ($Type === "shipping") {
            $Address    = $this->Object->getShippingAddress();
        } else {
            return false;
        }
        //====================================================================//
        // Empty => Create Order Address
        if (!$Address) {
            $Address    =   Mage::getModel('sales/order_address')
                    ->setOrder($this->Object)
                    ->setAddressType($Type);
        }

        //====================================================================//
        // Check For Changes
        if ($Address->getCustomerAddressId() == $AddressId) {
            return false;
        }
        //====================================================================//
        // Load Customer Address
        $CustomerAddress = Mage::getModel('customer/address')->load($AddressId);
        if ($CustomerAddress->getEntityId() != $AddressId) {
            return false;
        }
        //====================================================================//
        // Update Address
        $Address
            ->setCustomerAddressId($AddressId)
            ->setFirstname($CustomerAddress->getFirstname())
            ->setMiddlename($CustomerAddress->getMiddlename())
            ->setLastname($CustomerAddress->getLastname())
            ->setSuffix($CustomerAddress->getSuffix())
            ->setCompany($CustomerAddress->getCompany())
            ->setStreet($CustomerAddress->getStreet())
            ->setCity($CustomerAddress->getCity())
            ->setCountry_id($CustomerAddress->getCountry_id())
            ->setRegion($CustomerAddress->getRegion())
            ->setRegion_id($CustomerAddress->getRegion_id())
            ->setPostcode($CustomerAddress->getPostcode())
            ->setTelephone($CustomerAddress->getTelephone())
            ->setFax($CustomerAddress->getFax())
            ->save();
        $this->update = true;
//        Splash::Log()->www("Address After", $Address->getData());
        //====================================================================//
        // Update Order Address Collection
        if ($Type === "billing") {
            $this->Object->setBillingAddress($Address);
        } elseif ($Type === "shipping") {
            $this->Object->setShippingAddress($Address);
        }
        return true;
    }
}
