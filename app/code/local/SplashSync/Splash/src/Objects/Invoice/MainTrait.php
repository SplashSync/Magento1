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
use Mage_Sales_Model_Order_Invoice                  as MageInvoice;

/**
 * Magento 1 Invoice Main Fields Access
 */
trait MainTrait
{
    /**
     * Build Address Fields using FieldFactory
     */
    protected function buildMainFields(): void
    {
        //====================================================================//
        // PRICES INFORMATIONS
        //====================================================================//

        //====================================================================//
        // Invoice Total Price HT
        $this->fieldsFactory()->Create(SPL_T_DOUBLE)
            ->Identifier("grand_total_excl_tax")
            ->Name("Total (tax excl.)"." (".Mage::app()->getStore()->getCurrentCurrencyCode().")")
            ->MicroData("http://schema.org/Invoice", "totalPaymentDue")
            ->isReadOnly();

        //====================================================================//
        // Invoice Total Price TTC
        $this->fieldsFactory()->Create(SPL_T_DOUBLE)
            ->Identifier("grand_total")
            ->Name("Total (tax incl.)"." (".Mage::app()->getStore()->getCurrentCurrencyCode().")")
            ->MicroData("http://schema.org/Invoice", "totalPaymentDueTaxIncluded")
            ->isListed()
            ->isReadOnly();

        //====================================================================//
        // INVOICE STATUS FLAGS
        //====================================================================//

        //====================================================================//
        // Order Current Status
        $this->fieldsFactory()->Create(SPL_T_VARCHAR)
            ->Identifier("state")
            ->Name("Status")
            ->MicroData("http://schema.org/Invoice", "paymentStatus")
            ->AddChoices(
                array(  "PaymentDraft" => "Draft",
                    "PaymentDue" => "Payment Due",
                    "PaymentDeclined" => "Payment Declined",
                    "PaymentPastDue" => "Payment Past Due",
                    "PaymentComplete" => "Payment Complete",
                    "PaymentCanceled" => "Canceled",
                )
            )
            ->isNotTested();

        $this->fieldsFactory()->Create(SPL_T_VARCHAR)
            ->Identifier("state_name")
            ->Name("Status Name")
            ->MicroData("http://schema.org/Invoice", "paymentStatusName")
            ->isReadOnly();

        //====================================================================//
        // INVOICE STATUS FLAGS
        //====================================================================//

        //====================================================================//
        // Is Canceled
        // => There is no Difference Between a Draft & Canceled Order on Prestashop.
        //      Any Non Validated Order is considered as Canceled
        $this->fieldsFactory()->Create(SPL_T_BOOL)
            ->Identifier("isCanceled")
            ->Name(Mage::helper('sales')->__('Invoice')." : ".Mage::helper('sales')->__('Canceled'))
            ->MicroData("http://schema.org/PaymentStatusType", "PaymentDeclined")
            ->Association("isCanceled", "isValidated", "isPaid")
            ->Group("Meta")
            ->isReadOnly();

        //====================================================================//
        // Is Validated
        $this->fieldsFactory()->Create(SPL_T_BOOL)
            ->Identifier("isValidated")
            ->Name(Mage::helper('sales')->__('Invoice')." : "."Valid")
            ->MicroData("http://schema.org/PaymentStatusType", "PaymentDue")
            ->Association("isCanceled", "isValidated", "isPaid")
            ->Group("Meta")
            ->isReadOnly();

        //====================================================================//
        // Is Paid
        $this->fieldsFactory()->Create(SPL_T_BOOL)
            ->Identifier("isPaid")
            ->Name(Mage::helper('sales')->__('Invoice')." : ".Mage::helper('sales')->__('Paid'))
            ->MicroData("http://schema.org/PaymentStatusType", "PaymentComplete")
            ->Group("Meta")
            ->isReadOnly();
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    protected function getMainFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // PRICE INFORMATIONS
            //====================================================================//
            case 'grand_total_excl_tax':
                $this->out[$fieldName] = $this->object->getSubtotal() + $this->object->getShippingAmount();

                break;
            case 'grand_total':
                $this->getData($fieldName);

                break;
            //====================================================================//
            // INVOICE STATUS
            //====================================================================//
            case 'state':
                $this->out[$fieldName] = $this->getPaymentState();

                break;
            case 'state_name':
                $this->out[$fieldName] = $this->object->getStateName();

                break;
            //====================================================================//
            // INVOICE PAYMENT STATUS
            //====================================================================//
            case 'isCanceled':
                $this->out[$fieldName] = (bool) $this->object->isCanceled();

                break;
            case 'isValidated':
                $this->out[$fieldName] = !$this->object->isCanceled();

                break;
            case 'isPaid':
                $this->out[$fieldName] = (MageInvoice::STATE_PAID == $this->object->getState()) ? true : false;

                break;
            default:
                return;
        }

        unset($this->in[$key]);
    }

    /**
     * Read Invoice Payment Status
     *
     * @return string
     */
    private function getPaymentState(): string
    {
        if ($this->object->isCanceled()) {
            return "PaymentCanceled";
        }
        if (MageInvoice::STATE_PAID == $this->object->getState()) {
            return "PaymentComplete";
        }

        return "PaymentDue";
    }
}
