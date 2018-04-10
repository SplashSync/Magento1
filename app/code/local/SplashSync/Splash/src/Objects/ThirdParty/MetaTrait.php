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

namespace Splash\Local\Objects\ThirdParty;

// Magento Namespaces
use Mage;
use Mage_Newsletter_Model_Subscriber;

/**
 * @abstract    Magento 1 Customers Meta Fields Access
 */
trait MetaTrait
{
    

        /**
    *   @abstract     Build Customers Unused Fields using FieldFactory
    */
    private function buildMetaFields()
    {
        
        //====================================================================//
        // STRUCTURAL INFORMATIONS
        //====================================================================//

        //====================================================================//
        // Active
        $this->FieldsFactory()->Create(SPL_T_BOOL)
                ->Identifier("is_active")
                ->Name("Is Enabled")
                ->Group("Meta")
                ->MicroData("http://schema.org/Organization", "active")
                ->IsListed()->ReadOnly();
        
        //====================================================================//
        // Newsletter
        $this->FieldsFactory()->Create(SPL_T_BOOL)
                ->Identifier("newsletter")
                ->Name("Newletter")
                ->Group("Meta")
                ->MicroData("http://schema.org/Organization", "newsletter");
    }
    
    /**
     *  @abstract     Read requested Field
     *
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     *
     *  @return         none
     */
    private function getMetaFields($Key, $FieldName)
    {
            
        //====================================================================//
        // READ Fields
        switch ($FieldName) {
            //====================================================================//
            // Active Flag
            case 'is_active':
                $this->Out[$FieldName] = $this->Object->getData($FieldName);
                break;
            case 'newsletter':
                $this->Out[$FieldName] = Mage::getModel('newsletter/subscriber')->loadByCustomer($this->Object)->isSubscribed();
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
    private function setMetaFields($FieldName, $Data)
    {
        //====================================================================//
        // WRITE Fields
        switch ($FieldName) {
            //====================================================================//
            // Active Flag
            case 'is_active':
                if ($this->Object->getData($FieldName) != $Data) {
                    $this->Object->setData($FieldName, $Data);
                    $this->update = true;
                }
                break;
            case 'newsletter':
                $subscriber = Mage::getModel('newsletter/subscriber')->loadByCustomer($this->Object);
                //====================================================================//
                // Read Newsletter Status
                if ($subscriber->isSubscribed() == $Data) {
                    break;
                }
                //====================================================================//
                // Status Change Requiered => Subscribe
                if ($Data) {
                    $subscriber->setStatus(Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED);
                } else {
                    $subscriber->setStatus(Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED);
                }
                $subscriber->setSubscriberEmail($this->Object->getEmail());
                $subscriber->setSubscriberConfirmCode($subscriber->RandomSequence());
                $subscriber->setStoreId(Mage::app()->getStore()->getId());
                $subscriber->setCustomerId($this->Object->getId());
                $subscriber->save();
                $this->update = true;
                break;
            default:
                return;
        }
        unset($this->In[$FieldName]);
    }
}
