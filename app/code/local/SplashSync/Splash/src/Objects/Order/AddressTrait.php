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

namespace Splash\Local\Objects\Order;

use Mage;
use Mage_Customer_Model_Address;
use Mage_Sales_Model_Order_Address;

/**
 * Magento 1 Order Address Fields Access
 */
trait AddressTrait
{
    /**
     * Build Address Fields using FieldFactory
     */
    protected function buildAddressFields(): void
    {
        //====================================================================//
        // Billing Address
        $this->fieldsFactory()->create((string) self::objects()->encode("Address", SPL_T_ID))
            ->identifier("billing_address_id")
            ->name('Billing Address ID')
            ->microData("http://schema.org/Order", "billingAddress")
            ->isRequired()
        ;
        //====================================================================//
        // Shipping Address
        $this->fieldsFactory()->create((string) self::objects()->encode("Address", SPL_T_ID))
            ->identifier("shipping_address_id")
            ->name('Shipping Address ID')
            ->microData("http://schema.org/Order", "orderDelivery")
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
    protected function getAddressFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // Billing/Shipping Address Object Id Readings
            case 'billing_address_id':
            case 'shipping_address_id':
                if ("billing_address_id" == $fieldName) {
                    $address = $this->object->getBillingAddress();
                } else {
                    $address = $this->object->getShippingAddress();
                }
                if ($address && $address->getCustomerAddressId()) {
                    $this->out[$fieldName] = self::objects()->encode("Address", $address->getCustomerAddressId());

                    break;
                }
                $this->out[$fieldName] = null;

                break;
            default:
                return;
        }

        unset($this->in[$key]);
    }

    /**
     * Write Given Fields
     *
     * @param string $fieldName Field Identifier / Name
     * @param mixed  $data      Field Data
     *
     * @return void
     */
    protected function setAddressFields(string $fieldName, $data): void
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            //====================================================================//
            // Billing/Shipping Address Writing
            case 'billing_address_id':
                if ($this->setAddressContents('billing', self::objects()->id($data))) {
                    $this->needUpdate();
                }

                break;
            case 'shipping_address_id':
                //====================================================================//
                // Retrieve Address Object Id
                $addressId = self::objects()->id($data);
                //====================================================================//
                // Setup Address Object & Set Order as "Non Virtual" => With Shipping
                if ($addressId > 0) {
                    if ($this->setAddressContents('shipping', $addressId)) {
                        $this->needUpdate();
                    }
                    $this->object->setIsVirtual(0);
                //====================================================================//
                // No Address Setup & Set Order as "Virtual" => No Shipping
                } else {
                    $this->object->setIsVirtual(1);
                }

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }

    /**
     * Set Given Order Address
     *
     * @param string $type
     * @param mixed  $addressId
     *
     * @return bool
     */
    private function setAddressContents(string $type, $addressId): bool
    {
        //====================================================================//
        // Read Original Billing/Shipping Order Address
        if ("billing" === $type) {
            $address = $this->object->getBillingAddress();
        } elseif ("shipping" === $type) {
            $address = $this->object->getShippingAddress();
        } else {
            return false;
        }
        //====================================================================//
        // Empty => Create Order Address
        if (!$address) {
            /** @var Mage_Sales_Model_Order_Address $address */
            $address = Mage::getModel('sales/order_address');
            $address
                ->setOrder($this->object)
                ->setAddressType($type)
            ;
        }
        //====================================================================//
        // Check For Changes
        if ($address->getCustomerAddressId() == $addressId) {
            return false;
        }
        //====================================================================//
        // Load Customer Address
        /** @var Mage_Customer_Model_Address $model */
        $model = Mage::getModel('customer/address');
        /** @var false|Mage_Customer_Model_Address $customerAddress */
        $customerAddress = $model->load($addressId);
        if (!$customerAddress || ($customerAddress->getEntityId() != $addressId)) {
            return false;
        }
        //====================================================================//
        // Update Address
        $address
            ->setCustomerAddressId($addressId)
            ->setFirstname($customerAddress->getData("firstname"))
            ->setMiddlename($customerAddress->getData("middlename"))
            ->setLastname($customerAddress->getData("lastname"))
            ->setSuffix($customerAddress->getData("suffix"))
            ->setCompany($customerAddress->getData("company"))
            ->setStreet($customerAddress->getStreet())
            ->setCity($customerAddress->getData("city"))
            ->setCountry_id($customerAddress->getData('country_id'))
            ->setRegion($customerAddress->getRegion())
            ->setRegion_id($customerAddress->getRegionId())
            ->setPostcode($customerAddress->getData("postcode"))
            ->setTelephone($customerAddress->getData("telephone"))
            ->setFax($customerAddress->getData("fax"))
            ->save()
        ;
        $this->needUpdate();
        //====================================================================//
        // Update Order Address Collection
        if ("billing" === $type) {
            $this->object->setBillingAddress($address);
        } elseif ("shipping" === $type) {
            $this->object->setShippingAddress($address);
        }

        return true;
    }
}
