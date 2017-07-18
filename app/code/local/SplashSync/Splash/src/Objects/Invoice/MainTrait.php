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

namespace Splash\Local\Objects\Invoice;

use Mage;
use Mage_Sales_Model_Order_Invoice                  as MageInvoice;

/**
 * @abstract    Magento 1 Invoice Main Fields Access
 */
trait MainTrait {
    
    
    /**
    *   @abstract     Build Address Fields using FieldFactory
    */
    private function buildMainFields() {
        
        //====================================================================//
        // PRICES INFORMATIONS
        //====================================================================//
        
        //====================================================================//
        // Invoice Total Price HT
        $this->FieldsFactory()->Create(SPL_T_DOUBLE)
                ->Identifier("grand_total_excl_tax")
                ->Name("Total (tax excl.)" . " (" . Mage::app()->getStore()->getCurrentCurrencyCode() . ")")
                ->MicroData("http://schema.org/Invoice","totalPaymentDue")
                ->ReadOnly();
        
        //====================================================================//
        // Invoice Total Price TTC
        $this->FieldsFactory()->Create(SPL_T_DOUBLE)
                ->Identifier("grand_total")
                ->Name("Total (tax incl.)" . " (" . Mage::app()->getStore()->getCurrentCurrencyCode() . ")")
                ->MicroData("http://schema.org/Invoice","totalPaymentDueTaxIncluded")
                ->isListed()
                ->ReadOnly();        

        //====================================================================//
        // INVOICE STATUS FLAGS
        //====================================================================//        
        
        //====================================================================//
        // Order Current Status
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("state")
                ->Name("Status")
                ->MicroData("http://schema.org/Invoice","paymentStatus")
                ->AddChoices(
                    array(  "PaymentDraft"          => "Draft",
                            "PaymentDue"            => "Payment Due",
                            "PaymentDeclined"       => "Payment Declined",
                            "PaymentPastDue"        => "Payment Past Due",
                            "PaymentComplete"       => "Payment Complete",
                            "PaymentCanceled"       => "Canceled",
                        )
                    )                
                ->NotTested();
        
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("state_name")
                ->Name("Status Name")
                ->MicroData("http://schema.org/Invoice","paymentStatusName")
                ->ReadOnly();        
        
        //====================================================================//
        // INVOICE STATUS FLAGS
        //====================================================================//        
        
        //====================================================================//
        // Is Canceled
        // => There is no Diffrence Between a Draft & Canceled Order on Prestashop. 
        //      Any Non Validated Order is considered as Canceled
        $this->FieldsFactory()->Create(SPL_T_BOOL)
                ->Identifier("isCanceled")
                ->Name(Mage::helper('sales')->__('Invoice') . " : " . Mage::helper('sales')->__('Canceled'))
                ->MicroData("http://schema.org/PaymentStatusType","PaymentDeclined")
                ->Association("isCanceled","isValidated","isPaid")
                ->Group("Meta")
                ->ReadOnly();     
        
        //====================================================================//
        // Is Validated
        $this->FieldsFactory()->Create(SPL_T_BOOL)
                ->Identifier("isValidated")
                ->Name(Mage::helper('sales')->__('Invoice') . " : " . "Valid")
                ->MicroData("http://schema.org/PaymentStatusType","PaymentDue")
                ->Association("isCanceled","isValidated","isPaid")
                ->Group("Meta")
                ->ReadOnly();

        //====================================================================//
        // Is Paid
        $this->FieldsFactory()->Create(SPL_T_BOOL)
                ->Identifier("isPaid")
                ->Name(Mage::helper('sales')->__('Invoice') . " : " . Mage::helper('sales')->__('Paid'))
                ->MicroData("http://schema.org/PaymentStatusType","PaymentComplete")
                ->Group("Meta")
                ->ReadOnly();
        
        return;
    }
        
    /**
     *  @abstract     Read requested Field
     * 
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     * 
     *  @return         none
     */
    private function getMainFields($Key,$FieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($FieldName)
        {
            //====================================================================//
            // Order Delivery Date
//            case 'date_livraison':
//                $this->Out[$FieldName] = !empty($this->Object->date_livraison)?dol_print_date($this->Object->date_livraison, '%Y-%m-%d'):Null;
//                break;            
            
            //====================================================================//
            // PRICE INFORMATIONS
            //====================================================================//
            case 'grand_total_excl_tax':
                $this->Out[$FieldName] = $this->Object->getSubtotal() + $this->Object->getShippingAmount();
                break;
            case 'grand_total':
                $this->getData($FieldName);
                break;
            
            //====================================================================//
            // INVOICE STATUS
            //====================================================================//   
            case 'state':
                if ($this->Object->isCanceled()) {
                    $this->Out[$FieldName]  = "PaymentCanceled";
                } elseif ($this->Object->getState() == MageInvoice::STATE_PAID) {
                    $this->Out[$FieldName]  = "PaymentComplete";
                } else {
                    $this->Out[$FieldName]  = "PaymentDue";
                }
            break; 
            case 'state_name':
                $this->Out[$FieldName]  = $this->Object->getStateName();
            break; 
            
            //====================================================================//
            // INVOICE PAYMENT STATUS
            //====================================================================//   
            case 'isCanceled':
                $this->Out[$FieldName]  = (bool) $this->Object->isCanceled();
                break;
            case 'isValidated':
                $this->Out[$FieldName]  = (bool) !$this->Object->isCanceled();
                break;          
            case 'isPaid':
                $this->Out[$FieldName]  = (bool) ($this->Object->getState() == MageInvoice::STATE_PAID)?True:False;
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
    private function setMainFields($FieldName,$Data) 
    {
        //====================================================================//
        // WRITE Field
        switch ($FieldName)
        {
            //====================================================================//
            // INVOICE STATUS
            //====================================================================//   
            case 'state':
                $this->setInvoiceStatus($Data);
            break;              
                
            default:
                return;
        }
        unset($this->In[$FieldName]);
    }  
    
    
}
