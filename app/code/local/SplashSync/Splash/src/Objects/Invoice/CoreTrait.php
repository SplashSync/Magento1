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

namespace Splash\Local\Objects\Invoice;

use Mage;
use Mage_Core_Model_Date;
use Mage_Sales_Model_Order;

/**
 * Magento 1 Invoice Core Fields Access
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
            ->microData("http://schema.org/Invoice", "customer")
            ->isReadOnly()
        ;
        //====================================================================//
        // Customer Name
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("customer_name")
            ->name('Customer Name')
            ->microData("http://schema.org/Invoice", "customer")
            ->isListed()
            ->isReadOnly()
        ;
        //====================================================================//
        // Order Object
        $this->fieldsFactory()->create((string) self::objects()->Encode("Order", SPL_T_ID))
            ->identifier("order_id")
            ->name('Order')
            ->microData("http://schema.org/Invoice", "referencesOrder")
            ->isRequired()
        ;
        //====================================================================//
        // Invoice Reference
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("increment_id")
            ->name('Number')
            ->microData("http://schema.org/Invoice", "confirmationNumber")
            ->isListed()
        ;
        //====================================================================//
        // Order Reference
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("reference")
            ->name('Reference')
            ->microData("http://schema.org/Order", "orderNumber")
            ->isReadOnly()
        ;
        //====================================================================//
        // Order Date
        $this->fieldsFactory()->create(SPL_T_DATE)
            ->identifier("created_at")
            ->name("Date")
            ->microData("http://schema.org/Order", "orderDate")
            ->isRequired()
            ->isListed()
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
            // Order Reference Number
            case 'number':
                $this->out[$fieldName] = $this->object->getOrderIncrementId();

                break;
            //====================================================================//
            // Customer Object Id Readings
            case 'customer_id':
                $this->out[$fieldName] = self::objects()->Encode(
                    "ThirdParty",
                    $this->object->getOrder()->getData($fieldName)
                );

                break;
            //====================================================================//
            // Customer Name
            case 'customer_name':
                $this->out[$fieldName] = $this->object->getOrder()->getCustomerName();

                break;
            //====================================================================//
            // Object Object Id Readings
            case 'order_id':
                $this->out[$fieldName] = self::objects()->encode("Order", $this->object->getData($fieldName));

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
            case 'reference':
                $this->getSimple($fieldName, "Order");

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
    protected function setCoreFields(string $fieldName, $data)
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            //====================================================================//
            // Direct Readings
            case 'increment_id':
                if ($this->object->getData($fieldName) != $data) {
                    $this->object->setData($fieldName, $data);
                    $this->needUpdate();
                }

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
            // Parent Order Id
            case 'order_id':
                $orderId = self::objects()->id($data);
                if ($this->object->getOrder()->getId() == $orderId) {
                    break;
                }
                //====================================================================//
                // Load Order Object
                /** @var Mage_Sales_Model_Order $model */
                $model = Mage::getModel('sales/order');
                /** @var false|Mage_Sales_Model_Order $newOrder */
                $newOrder = $model->load((int) $orderId);
                if (!$newOrder || ($newOrder->getEntityId() !== $orderId)) {
                    break;
                }
                //====================================================================//
                //Update Customer Id
                $this->object->setOrder($newOrder);
                $this->needUpdate();

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }
}
