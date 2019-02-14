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

// Splash Namespaces
use Splash\Core\SplashCore      as Splash;

// Magento Namespaces
use Mage;
use Mage_Sales_Model_Order_Payment_Transaction      as Transaction;
use Varien_Data_Collection;

/**
 * Magento 1 Orders Payments Fields Access
 */
trait PaymentsTrait
{
    
    //====================================================================//
    // General Class Variables
    //====================================================================//

    protected   $Payment        = null;
    protected   $Transactions   = null;
    private     $TxnUpdate      = false; 

    public static $PAYMENT_METHODS            =   array(
        "CreditCard"                => array(
            "ccsave", "authorizenet", "authorizenet_directpost", "verisign", "adyen_cc",
            "braintree_legacy", "braintree"
            ),
        "CheckInAdvance"            => array("checkmo"),
        "ByBankTransferInAdvance"   =>  array(
            "banktransfer", "moneybookers_acc", "moneybookers_csi",
            "moneybookers_did", "moneybookers_dnk", "moneybookers_ebt",
            "moneybookers_ent", "moneybookers_gcb", "moneybookers_gir",
            "moneybookers_idl", "moneybookers_lsr", "moneybookers_mae",
            "moneybookers_npy", "moneybookers_pli", "moneybookers_psp",
            "moneybookers_pwy", "moneybookers_sft", "moneybookers_so2",
            "moneybookers_wlt"
        ),
        "PayPal"                    => array(
            "paypal", "paypal_express", "paypal_express_bml","paypal_direct",
            "paypal_standard", "paypaluk_express", "paypaluk_direct",
            "paypal_billing_agreement", "paypaluk_express_bml", "payflow_link",
            "payflow_advanced"
            ),
        "COD"                       => array("googlecheckout"),
        "ByInvoice"                 => array("purchaseorder"),
    );
    
    /**
    *   @abstract     Build Address Fields using FieldFactory
    */
    protected function buildPaymentLineFields()
    {
        
        $ListName = "" ;
        
        //====================================================================//
        // Payment Line Payment Method
        $this->fieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("mode")
                ->InList("payments")
                ->Name($ListName .  Mage::helper('sales')->__('Payment Method'))
                ->MicroData("http://schema.org/Invoice", "PaymentMethod")
                ->isReadOnly()
                ->isNotTested();

        //====================================================================//
        // Payment Line Date
        $this->fieldsFactory()->Create(SPL_T_DATE)
                ->Identifier("date")
                ->InList("payments")
                ->Name($ListName .  Mage::helper('sales')->__('Date'))
                ->MicroData("http://schema.org/PaymentChargeSpecification", "validFrom")
                ->Association("date@payments", "number@payments", "amount@payments");
    
        //====================================================================//
        // Payment Line Payment Identifier
        $this->fieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("number")
                ->InList("payments")
                ->Name($ListName .  Mage::helper('sales')->__('Transaction ID'))
                ->MicroData("http://schema.org/Invoice", "paymentMethodId")
                ->Association("date@payments", "number@payments", "amount@payments");

        //====================================================================//
        // Payment Line Amount
        $this->fieldsFactory()->Create(SPL_T_DOUBLE)
                ->Identifier("amount")
                ->InList("payments")
                ->Name($ListName .  Mage::helper('sales')->__("Amount"))
                ->MicroData("http://schema.org/PaymentChargeSpecification", "price")
                ->Association("date@payments", "number@payments", "amount@payments");
    }
    
    
    /**
     *  @abstract     Read requested Field
     *
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     *
     *  @return         none
     */
    protected function getPaymentsFields($Key, $FieldName)
    {
        //====================================================================//
        // Check if Payment has Transactions
        if($this->isSplash()) {
//        if($this->Payment->canFetchTransactionInfo()) {
            $this->getPaymentsFromTransactions($Key, $FieldName);
        } 
        
        //====================================================================//
        // Decode Field Name
        $listFieldName = self::lists()->InitOutput($this->Out, "payments", $FieldName);
        if(empty($listFieldName)) {
            return;
        }

        //====================================================================//
        // Fill List with Paid Amount Data
        if ($this->Payment->getAmountPaid() > 0) 
        {
            //====================================================================//
            // READ Fields
            $Value = $this->getPaymentData($listFieldName);
            //====================================================================//
            // Do Fill List with Data
            self::lists()->Insert($this->Out, "payments", $FieldName, 0, $Value);
        }
        
        //====================================================================//
        // Fill List with Refund Amount Data
        if ($this->Payment->getAmountRefunded() > 0) 
        {
            //====================================================================//
            // READ Fields
            if($listFieldName == "amount") {
                $Value = $this->getPaymentData("refund");
            } elseif($listFieldName == "number") {
                $Value = "R-" . $this->getPaymentData($listFieldName);
            } else {
                $Value = $this->getPaymentData($listFieldName);
            }
            //====================================================================//
            // Do Fill List with Data
            self::lists()->Insert($this->Out, "payments", $FieldName, 1, $Value);
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
    protected function setPaymentsFields($FieldName, $Data)
    {
        //====================================================================//
        // Safety Check
        if ($FieldName !== "payments") {
            return true;
        }
        if (!$this->isSplash()) {
            Splash::log()->deb("You Cannot Edit Invoices Created on Magento");
            unset($this->In[$FieldName]);
            return true;
        }
        
        $TxnIds = array();
        
        //====================================================================//
        // Verify Lines List & Update if Needed
        foreach ($Data as $PaymentData) {
            //====================================================================//
            // Update Transactions Data
            $this->setTransaction($PaymentData);
            //====================================================================//
            // Store Transactions Id to Manage Delete
            if (array_key_exists("number", $PaymentData)) {
                $TxnIds[] = $PaymentData["number"];
            }
        }
        //====================================================================//
        // Delete Remaining Transactions
        foreach ($this->getTransactions() as $Transaction) {
            if (!in_array($Transaction->getTxnId(), $TxnIds)) {
                $Transaction->delete();
            }
        }
        unset($this->In[$FieldName]);
    }
    
    /**
     * Get Payment Information
     *
     * @param  string    $fieldName
     *
     * @return         mixed
     */
    private function getPaymentData($fieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // Payment Line - Payment Mode
            case 'mode':
                return $this->getPaymentMethod($this->Payment);
                
            //====================================================================//
            // Payment Line - Payment Date
            case 'date':
                return date(SPL_T_DATECAST, Mage::getModel("core/date")->gmtTimestamp($this->Object->getCreatedAt()));
                
            //====================================================================//
            // Payment Line - Payment Identification Number
            case 'number':
                return $this->Object->getTransactionId();

            //====================================================================//
            // Payment Line - Payment Amount
            case 'amount':
                return $this->Payment->getAmountPaid();
                
            //====================================================================//
            // Payment Line - Refound Amount
            case 'refund':
                return (-1) * $this->Payment->getAmountRefunded();
        }        
        
        return null;
    }
    
    /**
     * Try To Detect Payment method Standardized Name
     *
     * @param  OrderPayment    $OrderPayment
     *
     * @return         string
     */
    private function getPaymentMethod($OrderPayment)
    {
        //====================================================================//
        // Detect Payment Metyhod Type from Default Payment "known" methods
        $Method = $OrderPayment->getMethod();
        foreach (static::$PAYMENT_METHODS as $PaymentMethod => $Ids) {
            if (in_array($Method, $Ids)) {
                return $PaymentMethod;
            }
        }
        Splash::log()->war("Unknown Payment Method: " . $Method);
        return "CreditCard";        
    }    
    
    /**
     *  @abstract     Read Order Payment
     *
     *  @return         none
     */
    private function loadPayment($Order)
    {
        $this->Payment  =   $Order->getPayment();
    }
    
    /**
     *  @abstract     Read requested Field
     *
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     *
     *  @return         none
     */
    protected function getPaymentsFromTransactions($Key, $FieldName)
    {
        $Index  =   0;
        //====================================================================//
        // Decode Field Name
        $ListFieldName = self::lists()->InitOutput($this->Out, "payments", $FieldName);
        //====================================================================//
        // Fill List with Data
        foreach ($this->getTransactions() as $Transaction) 
        {
            //====================================================================//
            // Filter on Customer Valid Payments Transactions
            if(!in_array($Transaction->getTxnType(), array(Transaction::TYPE_PAYMENT, Transaction::TYPE_CAPTURE))) {
                continue;
            }
            //====================================================================//
            // READ Fields
            switch ($ListFieldName) {
                //====================================================================//
                // Payment Line - Payment Mode
                case 'mode':
                    $Value = $this->getPaymentMethod($this->Payment);
                    break;
                //====================================================================//
                // Payment Line - Payment Date
                case 'date':
                    $Value = date(SPL_T_DATECAST, Mage::getModel("core/date")->timestamp($Transaction->getCreatedAt()));
                    break;
                //====================================================================//
                // Payment Line - Payment Identification Number
                case 'number':
                    $Value = $Transaction->getTxnId();
                    break;
                //====================================================================//
                // Payment Line - Payment Amount
                case 'amount':
                    //====================================================================//
                    // Look for Payment Amount in Transaction Details
                    $Details    = $Transaction->getAdditionalInformation(Transaction::RAW_DETAILS);
                    $Value      = isset($Details["Amount"])
                            ? $Details["Amount"]
                            : $this->Payment->getAmountPaid();
                    break;
                default:
                    return;
            }
            //====================================================================//
            // Do Fill List with Data
            self::lists()->Insert($this->Out, "payments", $FieldName, $Index, $Value);
            $Index++;
        }
        unset($this->In[$Key]);
    }
    
    /**
     *  @abstract     Read Order Transactions Collection
     *
     *  @return         array
     */
    private function getTransactions()
    {
        //====================================================================//
        // Load All Transactions on this Order
        return Mage::getModel('sales/order_payment_transaction')->getCollection()
                    ->setOrderFilter($this->Payment->getOrder())
                    ->addPaymentIdFilter($this->Payment->getId())
                    ->setOrder('transaction_id', Varien_Data_Collection::SORT_ORDER_ASC);
    }  
    
    /**
     *  @abstract     Write A Payment Line Fields
     *
     *  @param        mixed     $PaymentData            Transaction Data Array
     *
     *  @return         none
     */
    private function setTransaction($PaymentData)
    {
        //====================================================================//
        // Safety Check
        if (!array_key_exists("number", $PaymentData)) {
            return;
        }
        
        //====================================================================//
        // Init Transaction Informations
        $this->TxnUpdate    =   false; 
        $Transaction = $this->Payment->getTransaction($PaymentData["number"]);

        if (!$Transaction) {
            $this->Payment->setTransactionId($PaymentData["number"]);
            $Transaction = $this->Payment->addTransaction(Transaction::TYPE_PAYMENT, null, true);
            if (!$Transaction) {
                return Splash::log()->err("ErrLocalTpl", __CLASS__, __FUNCTION__, " Failed to add new transaction.");
            }
            $Transaction->save();
        }
        
        //====================================================================//
        // Update Transaction Data
        $this->setTransactionDate($Transaction, $PaymentData);
        
        //====================================================================//
        // Update Transaction Amount
        $this->setTransactionAmount($Transaction, $PaymentData);
        
        //====================================================================//
        // Save Changes
        if ($this->TxnUpdate) {
            $Transaction->save();
            $this->TxnUpdate = false;
            $this->needUpdate();
        }
    }
    
    /**
     *  @abstract     Update Transaction Date
     *
     *  @param        mixed     $Transaction            Transaction Object
     *  @param        mixed     $PaymentData            Transaction Data Array
     *
     *  @return       void
     */
    private function setTransactionDate($Transaction, $PaymentData)
    {
        if (array_key_exists("date", $PaymentData)) {
            //====================================================================//
            // Verify Date Changed
            $CurrentDate = date(SPL_T_DATECAST, Mage::getModel("core/date")->timestamp($Transaction->getCreatedAt()));
            if ($CurrentDate !== $PaymentData["date"]) {
                $Transaction->setCreatedAt(Mage::getModel("core/date")->gmtDate(null, $PaymentData["date"]));
                $this->TxnUpdate    =   true;
            }
        }
    }
    
    /**
     *  @abstract     Update Transaction Amount
     *
     *  @param        mixed     $Transaction            Transaction Object
     *  @param        mixed     $PaymentData            Transaction Data Array
     *
     *  @return       void
     */
    private function setTransactionAmount($Transaction, $PaymentData)
    {
        if (array_key_exists("amount", $PaymentData)) {
            //====================================================================//
            // Verify Amount Changed
            $CurrentInfos = $Transaction->getAdditionalInformation(Transaction::RAW_DETAILS);
            if (!is_array($CurrentInfos)) {
                $Transaction->setAdditionalInformation(Transaction::RAW_DETAILS, array("Amount" => $PaymentData["amount"] ));
                $this->TxnUpdate    =   true;
            } elseif (!isset($CurrentInfos["Amount"]) || ($CurrentInfos["Amount"] != $PaymentData["amount"] )) {
                $CurrentInfos["Amount"] = $PaymentData["amount"];
                $Transaction->setAdditionalInformation(Transaction::RAW_DETAILS, $CurrentInfos);
                $this->TxnUpdate    =   true;
            }
        }
    }    
}
