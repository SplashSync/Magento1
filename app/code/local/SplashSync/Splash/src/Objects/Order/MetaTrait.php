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
use Mage_Sales_Model_Order      as MageOrder;

/**
 * Magento 1 Order Meta Fields Access
 */
trait MetaTrait
{
    /**
     * Build Meta Fields using FieldFactory
     */
    protected function buildMetaFields(): void
    {
        //====================================================================//
        // ORDER STATUS FLAGS
        //====================================================================//

        //====================================================================//
        // Is Canceled
        $this->fieldsFactory()->Create(SPL_T_BOOL)
            ->Identifier("isCanceled")
            ->Name("Order"." : "."Canceled")
            ->MicroData("http://schema.org/OrderStatus", "OrderCancelled")
            ->Association("isCanceled", "isValidated", "isClosed")
            ->Group("Meta")
            ->isReadOnly()
        ;
        //====================================================================//
        // Is Validated
        $this->fieldsFactory()->Create(SPL_T_BOOL)
            ->Identifier("isValidated")
            ->Name("Order"." : "."Valid")
            ->MicroData("http://schema.org/OrderStatus", "OrderProcessing")
            ->Association("isCanceled", "isValidated", "isClosed")
            ->Group("Meta")
            ->isReadOnly()
        ;
        //====================================================================//
        // Is Closed
        $this->fieldsFactory()->Create(SPL_T_BOOL)
            ->Identifier("isClosed")
            ->Name("Order"." : "."Closed")
            ->MicroData("http://schema.org/OrderStatus", "OrderDelivered")
            ->Association("isCanceled", "isValidated", "isClosed")
            ->Group("Meta")
            ->isReadOnly()
        ;
        //====================================================================//
        // Is Paid
        $this->fieldsFactory()->Create(SPL_T_BOOL)
            ->Identifier("isPaid")
            ->Name("Order"." : "."Paid")
            ->MicroData("http://schema.org/OrderStatus", "OrderPaid")
            ->Group("Meta")
            ->isReadOnly()
        ;
        //====================================================================//
        // TRACEABILITY INFORMATIONS
        //====================================================================//

        //====================================================================//
        // TMS - Last Change Date
        $this->fieldsFactory()->Create(SPL_T_DATETIME)
            ->Identifier("updated_at")
            ->Name("Last update")
            ->MicroData("http://schema.org/DataFeedItem", "dateModified")
            ->Group("Meta")
            ->isReadOnly()
        ;
    }

    /**
     * Read requested Field
     *
     * @param string $key Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    protected function getMetaFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // ORDER STATUS FLAGS
            //====================================================================//

            case 'isCanceled':
                if (MageOrder::STATE_CANCELED === $this->object->getState()) {
                    $this->out[$fieldName] = true;
                } else {
                    $this->out[$fieldName] = false;
                }

                break;
            case 'isValidated':
                $this->out[$fieldName] = $this->isValidated();

                break;
            case 'isClosed':
                $this->out[$fieldName] = $this->isClosed();

                break;
            case 'isPaid':
                $this->out[$fieldName] = $this->isPaid();

                break;
            //====================================================================//
            // TRACEABILITY INFORMATIONS
            //====================================================================//

            case 'updated_at':
                /** @var Mage_Core_Model_Date $model */
                $model = Mage::getModel('core/date');

                $this->out[$fieldName] = date(
                    SPL_T_DATETIMECAST,
                    $model->gmtTimestamp($this->object->getData($fieldName))
                );

                break;
            default:
                return;
        }

        unset($this->in[$key]);
    }

    /**
     * Read Validated Flag
     *
     * @return bool
     */
    private function isValidated(): bool
    {
        if (MageOrder::STATE_NEW === $this->object->getState()
            || MageOrder::STATE_PROCESSING === $this->object->getState()
            || MageOrder::STATE_COMPLETE === $this->object->getState()
            || MageOrder::STATE_CLOSED === $this->object->getState()
            || MageOrder::STATE_CANCELED === $this->object->getState()
            || MageOrder::STATE_HOLDED === $this->object->getState()
                ) {
            return true;
        }

        return false;
    }

    /**
     * Read Closed Flag
     *
     * @return bool
     */
    private function isClosed(): bool
    {
        if (MageOrder::STATE_COMPLETE === $this->object->getState()
            || MageOrder::STATE_CLOSED === $this->object->getState()
            ) {
            return true;
        }

        return false;
    }

    /**
     * Read Paid Flag
     *
     * @return bool
     */
    private function isPaid(): bool
    {
        if (MageOrder::STATE_PROCESSING === $this->object->getState()
            || MageOrder::STATE_COMPLETE === $this->object->getState()
            || MageOrder::STATE_CLOSED === $this->object->getState()
                ) {
            return true;
        }

        return false;
    }
}
