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

namespace Splash\Local\Objects\ThirdParty;

use Mage;
use Mage_Newsletter_Model_Subscriber;

/**
 * Magento 1 Customers Meta Fields Access
 */
trait MetaTrait
{
    /**
     * Build Customers Unused Fields using FieldFactory
     *
     * @return void
     */
    protected function buildMetaFields(): void
    {
        //====================================================================//
        // STRUCTURAL INFORMATIONS
        //====================================================================//

        //====================================================================//
        // Active
        $this->fieldsFactory()->Create(SPL_T_BOOL)
            ->Identifier("is_active")
            ->Name("Is Enabled")
            ->Group("Meta")
            ->MicroData("http://schema.org/Organization", "active")
            ->isListed()->isReadOnly();

        //====================================================================//
        // Newsletter
        $this->fieldsFactory()->Create(SPL_T_BOOL)
            ->Identifier("newsletter")
            ->Name("Newletter")
            ->Group("Meta")
            ->MicroData("http://schema.org/Organization", "newsletter");
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
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
            // Active Flag
            case 'is_active':
                $this->out[$fieldName] = $this->object->getData($fieldName);

                break;
            case 'newsletter':
                /** @var \Mage_Newsletter_Model_Subscriber $model */
                $model = Mage::getModel('newsletter/subscriber');
                $this->out[$fieldName] = $model
                    ->loadByCustomer($this->object)
                    ->isSubscribed()
                ;

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
    protected function setMetaFields(string $fieldName, $data): void
    {
        //====================================================================//
        // WRITE Fields
        switch ($fieldName) {
            //====================================================================//
            // Active Flag
            case 'is_active':
                if ($this->object->getData($fieldName) != $data) {
                    $this->object->setData($fieldName, $data);
                    $this->needUpdate();
                }

                break;
            case 'newsletter':
                /** @var \Mage_Newsletter_Model_Subscriber $model */
                $model = Mage::getModel('newsletter/subscriber');
                $subscriber = $model->loadByCustomer($this->object);
                //====================================================================//
                // Read Newsletter Status
                if ($subscriber->isSubscribed() == $data) {
                    break;
                }
                //====================================================================//
                // Status Change Required => Subscribe
                if ($data) {
                    $subscriber->setStatus(Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED);
                } else {
                    $subscriber->setStatus(Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED);
                }
                $subscriber->setSubscriberEmail($this->object->getEmail());
                $subscriber->setSubscriberConfirmCode($subscriber->RandomSequence());
                $subscriber->setStoreId(Mage::app()->getStore()->getId());
                $subscriber->setCustomerId($this->object->getId());
                $subscriber->save();
                $this->needUpdate();

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }
}
