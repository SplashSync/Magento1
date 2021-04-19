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
use Mage_Sales_Model_Order_Creditmemo               as MageCreditNote;
use Mage_Sales_Model_Order_Invoice                  as MageInvoice;
use Mage_Sales_Model_Order_Payment;
use Mage_Sales_Model_Order_Payment_Transaction      as Transaction;
use Splash\Core\SplashCore      as Splash;
use Varien_Data_Collection;

/**
 * Magento 1 Orders Payments Fields Access
 */
trait PaymentsTrait
{
    /**
     * List of Known Payment Methods Codes
     *
     * @var array
     */
    public static $PAYMENT_METHODS = array(
        "CreditCard" => array(
            "ccsave", "authorizenet", "authorizenet_directpost", "verisign", "adyen_cc",
            "braintree_legacy", "braintree", "bakerloo_manualcreditcard", "adyen_oneclick"
        ),
        "CheckInAdvance" => array("checkmo"),
        "ByBankTransferInAdvance" => array(
            "banktransfer", "moneybookers_acc", "moneybookers_csi",
            "moneybookers_did", "moneybookers_dnk", "moneybookers_ebt",
            "moneybookers_ent", "moneybookers_gcb", "moneybookers_gir",
            "moneybookers_idl", "moneybookers_lsr", "moneybookers_mae",
            "moneybookers_npy", "moneybookers_pli", "moneybookers_psp",
            "moneybookers_pwy", "moneybookers_sft", "moneybookers_so2",
            "moneybookers_wlt"
        ),
        "PayPal" => array(
            "paypal", "paypal_express", "paypal_express_bml","paypal_direct",
            "paypal_standard", "paypaluk_express", "paypaluk_direct",
            "paypal_billing_agreement", "paypaluk_express_bml", "payflow_link",
            "payflow_advanced"
        ),
        "COD" => array("googlecheckout"),
        "ByInvoice" => array("purchaseorder"),
    );

    //====================================================================//
    // General Class Variables
    //====================================================================//

    /**
     * @var Mage_Sales_Model_Order_Payment
     */
    protected $payment;

    /**
     * @var object
     */
    protected $transactions;

    /**
     * Transaction Update Required
     *
     * @var bool
     */
    private $txnUpdate = false;

    /**
     * Build Address Fields using FieldFactory
     */
    protected function buildPaymentLineFields(): void
    {
        $listName = "" ;
        //====================================================================//
        // Payment Line Payment Method
        $this->fieldsFactory()->Create(SPL_T_VARCHAR)
            ->Identifier("mode")
            ->InList("payments")
            ->Name($listName.Mage::helper('sales')->__('Payment Method'))
            ->MicroData("http://schema.org/Invoice", "PaymentMethod")
            ->isReadOnly()
            ->isNotTested()
        ;
        //====================================================================//
        // Payment Line Date
        $this->fieldsFactory()->Create(SPL_T_DATE)
            ->Identifier("date")
            ->InList("payments")
            ->Name($listName.Mage::helper('sales')->__('Date'))
            ->MicroData("http://schema.org/PaymentChargeSpecification", "validFrom")
            ->Association("date@payments", "number@payments", "amount@payments")
        ;
        //====================================================================//
        // Payment Line Payment Identifier
        $this->fieldsFactory()->Create(SPL_T_VARCHAR)
            ->Identifier("number")
            ->InList("payments")
            ->Name($listName.Mage::helper('sales')->__('Transaction ID'))
            ->MicroData("http://schema.org/Invoice", "paymentMethodId")
            ->Association("date@payments", "number@payments", "amount@payments")
        ;
        //====================================================================//
        // Payment Line Amount
        $this->fieldsFactory()->Create(SPL_T_DOUBLE)
            ->Identifier("amount")
            ->InList("payments")
            ->Name($listName.Mage::helper('sales')->__("Amount"))
            ->MicroData("http://schema.org/PaymentChargeSpecification", "price")
            ->Association("date@payments", "number@payments", "amount@payments")
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
    protected function getPaymentsFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // Check if Payment has Transactions
        if ($this->isSplash()) {
            $this->getPaymentsFromTransactions($key, $fieldName);
        }
        //====================================================================//
        // Decode Field Name
        $listFieldName = self::lists()->initOutput($this->out, "payments", $fieldName);
        if (empty($listFieldName)) {
            return;
        }
        //====================================================================//
        // Fill List with Paid Amount Data
        if (($this->payment->getAmountPaid() > 0) && !($this->object instanceof MageCreditNote)) {
            //====================================================================//
            // READ Fields
            $value = $this->getPaymentData($listFieldName);
            //====================================================================//
            // Do Fill List with Data
            self::lists()->insert($this->out, "payments", $fieldName, 0, $value);
        }
        //====================================================================//
        // Fill List with Refund Amount Data
        if (($this->payment->getAmountRefunded() > 0) && !($this->object instanceof MageInvoice)) {
            //====================================================================//
            // READ Fields
            if ("amount" == $listFieldName) {
                $value = $this->getPaymentData("refund");
            } elseif ("number" == $listFieldName) {
                $value = "R-".$this->getPaymentData($listFieldName);
            } else {
                $value = $this->getPaymentData($listFieldName);
            }
            //====================================================================//
            // Do Fill List with Data
            self::lists()->Insert($this->out, "payments", $fieldName, 1, $value);
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
    protected function setPaymentsFields(string $fieldName, $data): void
    {
        //====================================================================//
        // Safety Check
        if ("payments" !== $fieldName) {
            return;
        }
        if (!$this->isSplash()) {
            Splash::log()->deb("You Cannot Edit Invoices Created on Magento");
            unset($this->in[$fieldName]);

            return;
        }
        $txnIds = array();
        //====================================================================//
        // Verify Lines List & Update if Needed
        foreach ($data as $paymentData) {
            //====================================================================//
            // Update Transactions Data
            $this->setTransaction($paymentData);
            //====================================================================//
            // Store Transactions Id to Manage Delete
            if (array_key_exists("number", $paymentData)) {
                $txnIds[] = $paymentData["number"];
            }
        }
        //====================================================================//
        // Delete Remaining Transactions
        foreach ($this->getTransactions() as $transaction) {
            if (!in_array($transaction->getTxnId(), $txnIds, true)) {
                $transaction->delete();
            }
        }
        unset($this->in[$fieldName]);
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    protected function getPaymentsFromTransactions($key, $fieldName)
    {
        $index = 0;
        //====================================================================//
        // Decode Field Name
        $listFieldName = self::lists()->initOutput($this->out, "payments", $fieldName);
        //====================================================================//
        // Fill List with Data
        foreach ($this->getTransactions() as $transaction) {
            //====================================================================//
            // Filter on Customer Valid Payments Transactions
            $validTypes = array(Transaction::TYPE_PAYMENT, Transaction::TYPE_CAPTURE);
            if (!in_array($transaction->getTxnType(), $validTypes, true)) {
                continue;
            }
            //====================================================================//
            // READ Fields
            switch ($listFieldName) {
                //====================================================================//
                // Payment Line - Payment Mode
                case 'mode':
                    $value = $this->getPaymentMethod($this->payment);

                    break;
                //====================================================================//
                // Payment Line - Payment Date
                case 'date':
                    /** @var Mage_Core_Model_Date $model */
                    $model = Mage::getModel('core/date');
                    $value = date(SPL_T_DATECAST, $model->timestamp($transaction->getCreatedAt()));

                    break;
                //====================================================================//
                // Payment Line - Payment Identification Number
                case 'number':
                    $value = $transaction->getTxnId();

                    break;
                //====================================================================//
                // Payment Line - Payment Amount
                case 'amount':
                    //====================================================================//
                    // Look for Payment Amount in Transaction Details
                    $details = $transaction->getAdditionalInformation(Transaction::RAW_DETAILS);
                    $value = isset($details["Amount"])
                            ? $details["Amount"]
                            : $this->payment->getAmountPaid();

                    break;
                default:
                    return;
            }
            //====================================================================//
            // Do Fill List with Data
            self::lists()->insert($this->out, "payments", $fieldName, $index, $value);
            $index++;
        }
        unset($this->in[$key]);
    }

    /**
     * Read Order Payment
     *
     * @param mixed $order
     *
     * @return void
     */
    protected function loadPayment($order): void
    {
        $this->payment = $order->getPayment();
    }

    /**
     * Get Payment Information
     *
     * @param string $fieldName
     *
     * @return mixed
     */
    private function getPaymentData(string $fieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // Payment Line - Payment Mode
            case 'mode':
                return $this->getPaymentMethod($this->payment);
            //====================================================================//
            // Payment Line - Payment Date
            case 'date':
                /** @var Mage_Core_Model_Date $model */
                $model = Mage::getModel('core/date');

                return date(SPL_T_DATECAST, $model->gmtTimestamp($this->object->getCreatedAt()));
            //====================================================================//
            // Payment Line - Payment Identification Number
            case 'number':
                return $this->object->getTransactionId();
            //====================================================================//
            // Payment Line - Payment Amount
            case 'amount':
                return $this->payment->getAmountPaid();
            //====================================================================//
            // Payment Line - Refund Amount
            case 'refund':
                return (-1) * $this->payment->getAmountRefunded();
        }

        return null;
    }

    /**
     * Try To Detect Payment method Standardized Name
     *
     * @param Mage_Sales_Model_Order_Payment $orderPayment
     *
     * @return string
     */
    private function getPaymentMethod($orderPayment): string
    {
        //====================================================================//
        // Detect Payment Method Type from Default Payment "known" methods
        $method = $orderPayment->getMethod();
        foreach (static::$PAYMENT_METHODS as $paymentMethod => $methodIds) {
            if (in_array($method, $methodIds, true)) {
                return $paymentMethod;
            }
        }
        Splash::log()->war("Unknown Payment Method: ".$method);

        return "CreditCard";
    }

    /**
     * Read Order Transactions Collection
     *
     * @return array
     */
    private function getTransactions()
    {
        //====================================================================//
        // Load All Transactions on this Order
        /** @var Transaction $model */
        $model = Mage::getModel('sales/order_payment_transaction');

        return $model->getCollection()
            ->setOrderFilter($this->payment->getOrder())
            ->addPaymentIdFilter($this->payment->getId())
            ->setOrder('transaction_id', Varien_Data_Collection::SORT_ORDER_ASC);
    }

    /**
     * Write A Payment Line Fields
     *
     * @param mixed $paymentData Transaction Data Array
     *
     * @return void
     */
    private function setTransaction($paymentData): void
    {
        //====================================================================//
        // Safety Check
        if (!array_key_exists("number", $paymentData)) {
            return;
        }
        //====================================================================//
        // Init Transaction Informations
        $this->txnUpdate = false;
        $transaction = $this->payment->getTransaction($paymentData["number"]);
        if (!$transaction) {
            $this->payment->setTransactionId($paymentData["number"]);
            $transaction = $this->payment->addTransaction(Transaction::TYPE_PAYMENT, null, true);
            if (!$transaction) {
                Splash::log()->errTrace("Failed to add new transaction.");

                return;
            }
            $transaction->save();
        }
        //====================================================================//
        // Update Transaction Data
        $this->setTransactionDate($transaction, $paymentData);
        //====================================================================//
        // Update Transaction Amount
        $this->setTransactionAmount($transaction, $paymentData);
        //====================================================================//
        // Save Changes
        if ($this->txnUpdate) {
            $transaction->save();
            $this->txnUpdate = false;
            $this->needUpdate();
        }
    }

    /**
     * Update Transaction Date
     *
     * @param mixed $transaction Transaction Object
     * @param mixed $paymentData Transaction Data Array
     *
     * @return void
     */
    private function setTransactionDate($transaction, $paymentData): void
    {
        if (array_key_exists("date", $paymentData)) {
            /** @var Mage_Core_Model_Date $model */
            $model = Mage::getModel('core/date');
            //====================================================================//
            // Verify Date Changed
            $currentDate = date(SPL_T_DATECAST, $model->timestamp($transaction->getCreatedAt()));
            if ($currentDate !== $paymentData["date"]) {
                $transaction->setCreatedAt($model->gmtDate(null, $paymentData["date"]));
                $this->txnUpdate = true;
            }
        }
    }

    /**
     * Update Transaction Amount
     *
     * @param mixed $transaction Transaction Object
     * @param mixed $paymentData Transaction Data Array
     *
     * @return void
     */
    private function setTransactionAmount($transaction, $paymentData): void
    {
        if (array_key_exists("amount", $paymentData)) {
            //====================================================================//
            // Verify Amount Changed
            $currentInfos = $transaction->getAdditionalInformation(Transaction::RAW_DETAILS);
            if (!is_array($currentInfos)) {
                $transaction->setAdditionalInformation(
                    Transaction::RAW_DETAILS,
                    array("Amount" => $paymentData["amount"] )
                );
                $this->txnUpdate = true;
            } elseif (!isset($currentInfos["Amount"]) || ($currentInfos["Amount"] != $paymentData["amount"])) {
                $currentInfos["Amount"] = $paymentData["amount"];
                $transaction->setAdditionalInformation(Transaction::RAW_DETAILS, $currentInfos);
                $this->txnUpdate = true;
            }
        }
    }
}
