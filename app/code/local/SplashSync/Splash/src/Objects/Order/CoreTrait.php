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
use Mage_Core_Model_Date;
use Mage_Customer_Model_Customer;
use Splash\Client\Splash;

/**
 * Magento 1 Order Core Fields Access
 */
trait CoreTrait
{
    /**
     * Build Core Fields using FieldFactory
     */
    protected function buildCoreFields(): void
    {
        //====================================================================//
        // Customer Object
        $this->fieldsFactory()->create((string) self::objects()->Encode("ThirdParty", SPL_T_ID))
            ->identifier("customer_id")
            ->name('Customer')
            ->microData("http://schema.org/Organization", "ID")
            ->isRequired();

        //====================================================================//
        // Reference
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("increment_id")
            ->name('Reference')
            ->microData("http://schema.org/Order", "orderNumber")
            ->isListed();

        //====================================================================//
        // Order Date
        $this->fieldsFactory()->create(SPL_T_DATE)
            ->identifier("created_at")
            ->name("Date")
            ->microData("http://schema.org/Order", "orderDate")
            ->isListed();
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field identifier / Name
     *
     * @return void
     */
    protected function getCoreFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // Direct Readings
            case 'increment_id':
                $this->getData($fieldName);

                break;
            //====================================================================//
            // Customer Object Id Readings
            case 'customer_id':
                $this->out[$fieldName] = self::objects()->encode("ThirdParty", $this->object->getData($fieldName));

                break;
            //====================================================================//
            // Order Official Date
            case 'created_at':
                /** @var Mage_Core_Model_Date $model */
                $model = Mage::getModel('core/date');
                $this->out[$fieldName] = date(
                    SPL_T_DATECAST,
                    $model->timestamp($this->object->getData($fieldName))
                );

                break;
            default:
                return;
        }

        unset($this->in[$key]);
    }

    /**
     * Write Given Fields
     *
     * @param string $fieldName Field identifier / Name
     * @param mixed  $data      Field Data
     *
     * @return void
     */
    protected function setCoreFields(string $fieldName, $data): void
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            //====================================================================//
            // Direct Readings
            case 'increment_id':
                $this->setData($fieldName, $data);

                break;
            //====================================================================//
            // Order Official Date
            case 'created_at':
                /** @var Mage_Core_Model_Date $model */
                $model = Mage::getModel('core/date');
                $currentDate = date(
                    SPL_T_DATECAST,
                    $model->timestamp($this->object->getData($fieldName))
                );
                if ($currentDate != $data) {
                    $this->object->setData($fieldName, $model->gmtDate(null, $data));
                    $this->needUpdate();
                }

                break;
            //====================================================================//
            // Order Company Id
            case 'customer_id':
                $customerId = self::objects()->Id($data);
                //====================================================================//
                // Compare Customer
                if ($this->object->getCustomerId() == $customerId) {
                    break;
                }
                //====================================================================//
                // Load Customer Object
                /** @var Mage_Customer_Model_Customer $model */
                $model = Mage::getModel('customer/customer');
                /** @var false|Mage_Customer_Model_Customer $newCustomer */
                $newCustomer = $model->load((int) $customerId);
                if (!$newCustomer) {
                    Splash::log()->errTrace("Unable to find Customer ".$customerId);

                    break;
                }
                //====================================================================//
                //Update Customer Id
                $this->object->setCustomer($newCustomer);
                //====================================================================//
                //Update Customer Infos
                $this->object->setCustomerEmail($newCustomer->getData("email"));
                $this->object->setCustomerFirstname($newCustomer->getData('firstname'));
                $this->object->setCustomerLastname($newCustomer->getData('lastname'));
                $this->object->setCustomerMiddlename($newCustomer->getData('middlename'));
                $this->object->setCustomerPrefix($newCustomer->getData('prefix'));
                $this->object->setCustomerSufix($newCustomer->getData('sufix'));

                $this->needUpdate();

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }
}
