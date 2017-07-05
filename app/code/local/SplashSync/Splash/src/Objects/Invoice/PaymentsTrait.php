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

/**
 * @abstract    Magento 1 Order Payments Fields Access
 */
trait PaymentsTrait {
    
    public static       $PAYMENT_METHODS            =   array(
        "CreditCard"                => array(
            "ccsave", "authorizenet", "authorizenet_directpost","verisign"
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
    private function buildPaymentLineFields() {
        
        $ListName = Mage::helper('sales')->__('Payment Information') . " => " ;
        $ListName = "" ;
        
        //====================================================================//
        // Payment Line Payment Method 
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("mode")
                ->InList("payments")
                ->Name( $ListName .  Mage::helper('sales')->__('Payment Method'))
                ->MicroData("http://schema.org/Invoice","PaymentMethod")
                ->ReadOnly()
                ->NotTested();        

        //====================================================================//
        // Payment Line Date
        $this->FieldsFactory()->Create(SPL_T_DATE)        
                ->Identifier("date")
                ->InList("payments")
                ->Name( $ListName .  Mage::helper('sales')->__('Date'))
                ->MicroData("http://schema.org/PaymentChargeSpecification","validFrom")
                ->Association("date@payments","mode@payments","amount@payments");        
//                ->ReadOnly()
//                ->NotTested();        

        //====================================================================//
        // Payment Line Payment Identifier
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)        
                ->Identifier("number")
                ->InList("payments")
                ->Name( $ListName .  Mage::helper('sales')->__('Transaction ID'))
                ->MicroData("http://schema.org/Invoice","paymentMethodId")        
                ->Association("date@payments","mode@payments","amount@payments");        
//                ->ReadOnly()
//                ->NotTested();        

        //====================================================================//
        // Payment Line Amount
        $this->FieldsFactory()->Create(SPL_T_DOUBLE)        
                ->Identifier("amount")
                ->InList("payments")
                ->Name( $ListName .  Mage::helper('sales')->__("Amount"))
                ->MicroData("http://schema.org/PaymentChargeSpecification","price");
//                ->ReadOnly()
//                ->NotTested();                    
    }        
    
    
    
    /**
     *  @abstract     Try To Detect Payment method Standardized Name
     * 
     *  @param  OrderPayment    $OrderPayment 
     * 
     *  @return         none
     */
    private function getPaymentMethod($OrderPayment)
    {
        //====================================================================//
        // Detect Payment Metyhod Type from Default Payment "known" methods
        $Method = $OrderPayment->getMethod();
        foreach ( Invoice::$PAYMENT_METHODS as $PaymentMethod => $Ids )
        {
            if ( in_array($Method, $Ids) ) {
                return $PaymentMethod;
            }
        }
        return "free";
    }    
    
    /**
     *  @abstract     Read requested Field
     * 
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     * 
     *  @return         none
     */
    private function getPaymentLineFields($Key,$FieldName)
    {
        $Index  =   0;
        //====================================================================//
        // Decode Field Name
        $ListFieldName = $this->List_InitOutput("payments",$FieldName);
    
        //====================================================================//
        // Retrieve List of Order Transactions
        $Transactions = Mage::getModel('sales/order_payment_transaction')->getCollection()
                    ->setOrderFilter($this->Order)
                    ->addPaymentIdFilter($this->Payment->getId())
                    ->addTxnTypeFilter(Transaction::TYPE_PAYMENT)
                    ->setOrder('created_at', Varien_Data_Collection::SORT_ORDER_DESC)
                    ->setOrder('transaction_id', Varien_Data_Collection::SORT_ORDER_DESC);        
        
        //====================================================================//
        // Fill List with Data
        foreach ($Transactions as $Transaction) {
            //====================================================================//
            // READ Fields
            switch ($ListFieldName)
            {
                //====================================================================//
                // Payment Line - Payment Mode
                case 'mode':
                    $Value = $this->getPaymentMethod($this->Payment);
                    break;
                //====================================================================//
                // Payment Line - Payment Date
                case 'date':
                    $Value = date( SPL_T_DATECAST, Mage::getModel("core/date")->timestamp($Transaction->getCreatedAt()));
                    break;
                //====================================================================//
                // Payment Line - Payment Identification Number
                case 'number':
                    $Value = $Transaction->getTxnId();
                    break;
                //====================================================================//
                // Payment Line - Payment Amount
                case 'amount':
                    $Details    = $Transaction->getAdditionalInformation(Transaction::RAW_DETAILS );
                    $Value      = isset($Details["Amount"])?$Details["Amount"]:0;
                    break;
                default:
                    return;
            }
            //====================================================================//
            // Do Fill List with Data
            $this->List_Insert("payments",$FieldName,$Index,$Value);
            $Index++;
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
    private function setPayments($FieldName,$Data) 
    {
        //====================================================================//
        // Safety Check
        if ( $FieldName !== "payments" ) {
            return True;
        }
        if ( !$this->isSplash() ) {
            Splash::Log()->Deb("You Cannot Edit Invoices Created on Magento");  
            unset($this->In[$FieldName]);            
            return True;
        }        
        
        //====================================================================//
        // Retrieve List of Order Transactions
//        $this->Transactions = Mage::getModel('sales/order_payment_transaction')->getCollection()
//                    ->setOrderFilter($this->Object->getOrder())
//                    ->addPaymentIdFilter($this->Payment->getId())
//                    ->addTxnTypeFilter(Transaction::TYPE_PAYMENT)
//                    ->setOrder('created_at', Varien_Data_Collection::SORT_ORDER_DESC)
//                    ->setOrder('transaction_id', Varien_Data_Collection::SORT_ORDER_DESC);        
        
        //====================================================================//
        // Verify Lines List & Update if Needed 
        foreach ($Data as $LineData) {
            
            if ( !array_key_exists("number", $LineData) ) {
                continue;
            }
            //====================================================================//
            // Init Transaction Informations
            $Transaction = $this->Payment->getTransaction($LineData["number"]);
            
            if ( !$Transaction ) {
                $this->Payment->setTransactionId($LineData["number"]);
                $Transaction = $this->Payment->addTransaction(Transaction::TYPE_PAYMENT,null,True);
                if ( !$Transaction ) {
                    continue;
                }
                $this->TxnUpdate    =   True;
            }
            //====================================================================//
            // Update Transaction Datas
            if ( array_key_exists("date", $LineData) ) {
                //====================================================================//
                // Verify Date Changed
                $CurrentDate = date( SPL_T_DATECAST, Mage::getModel("core/date")->timestamp($Transaction->getCreatedAt()));
                if ( $CurrentDate !== $LineData["date"] ) {
                    $Transaction->setCreatedAt($LineData["date"]);
                    $this->TxnUpdate    =   True;
                }  
            }
            if ( array_key_exists("amount", $LineData) ) {
                //====================================================================//
                // Verify Amount Changed
                $CurrentInfos = $Transaction->getAdditionalInformation(Transaction::RAW_DETAILS);
                if ( !is_array($CurrentInfos) ) {
                    $Transaction->setAdditionalInformation(Transaction::RAW_DETAILS, array("Amount" => $LineData["amount"] ));
                    $this->TxnUpdate    =   True;
                } elseif ( !isset($CurrentInfos["Amount"]) || ($CurrentInfos["Amount"] != $LineData["amount"] ) ) {
                    $CurrentInfos["Amount"] = $LineData["amount"];
                    $Transaction->setAdditionalInformation(Transaction::RAW_DETAILS, $CurrentInfos);
                    $this->TxnUpdate    =   True;
                }  
            }
            //====================================================================//
            // Save Changes
            if ( $this->TxnUpdate ) {  
                $Transaction->save();
                $this->TxnUpdate = False;
                $this->update = True;
            }        
            
        } 
        //====================================================================//
        // Delete Remaining Lines
//        foreach ($this->Products as $Product) {
//            //====================================================================//
//            // Perform Line Delete
//            $Product->delete();
//            $this->update = True;
//        }        
        unset($this->In[$FieldName]);
    } 
        
    
}
