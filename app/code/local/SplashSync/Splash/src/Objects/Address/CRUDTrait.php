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

namespace Splash\Local\Objects\Address;

use Mage;
use Mage_Customer_Exception;
use Mage_Customer_Model_Address;
use Splash\Core\SplashCore      as Splash;

/**
 * Magento 1 Customers CRUD Functions
 */
trait CRUDTrait
{
    /**
     * Load Request Object
     *
     * @param string $objectId Object id
     *
     * @return false|object
     */
    public function load($objectId)
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace();
        //====================================================================//
        // Init Object
        /** @var Mage_Customer_Model_Address $model */
        $model = Mage::getModel('customer/address');
        $customer = $model->load((int) $objectId);
        if ($customer->getEntityId() != $objectId) {
            return Splash::log()->errTrace("Unable to load Address (".$objectId.").");
        }

        return $customer;
    }

    /**
     * Create Request Object
     *
     * @return false|object New Object
     */
    public function create()
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace();
        //====================================================================//
        // Check Required Fields
        if (!$this->verifyRequiredFields()) {
            return false;
        }
        //====================================================================//
        // Create Empty Customer
        /** @var Mage_Customer_Model_Address $address */
        $address = Mage::getModel('customer/address');
        $address->setData("firstname", $this->in["firstname"]);
        $address->setData("lastname", $this->in["lastname"]);
        $address->setData("address1", $this->in["address1"]);
        $address->setData("postcode", $this->in["postcode"]);
        $address->setData("city", $this->in["city"]);
        $address->setData("country_id", $this->in["country_id"]);
        $this->object = $address;
        $this->setParentId($this->in["parent_id"]);

        //====================================================================//
        // Save Object
        try {
            $address->save();
        } catch (Mage_Customer_Exception $ex) {
            return Splash::log()->report($ex);
        }

        return $address;
    }

    /**
     * Update Request Object
     *
     * @param bool $needed Is This Update Needed
     *
     * @return false|string Object Id
     */
    public function update($needed)
    {
        return $this->coreUpdate($needed);
    }

    /**
     * Delete requested Object
     *
     * @param string $objectId Object Id.  If NULL, Object needs to be created.
     *
     * @return bool
     */
    public function delete($objectId = null)
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace();
        //====================================================================//
        // Execute Generic Magento Delete Function ...
        return $this->coreDelete('customer/address', (int) $objectId);
    }
}
