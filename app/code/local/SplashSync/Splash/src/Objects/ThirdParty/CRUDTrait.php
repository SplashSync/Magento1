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
use Mage_Customer_Exception;
use Mage_Customer_Model_Customer;
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
        /** @var Mage_Customer_Model_Customer $model */
        $model = Mage::getModel('customer/customer');
        $customer = $model->load((int) $objectId);
        if ($customer->getEntityId() != $objectId) {
            return Splash::log()->errTrace("Unable to load Customer (".$objectId.").");
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
        if (empty($this->in["firstname"])) {
            return Splash::log()->err("ErrLocalFieldMissing", __CLASS__, __FUNCTION__, "firstname");
        }
        if (empty($this->in["lastname"])) {
            return Splash::log()->err("ErrLocalFieldMissing", __CLASS__, __FUNCTION__, "lastname");
        }
        if (empty($this->in["email"])) {
            return Splash::log()->err("ErrLocalFieldMissing", __CLASS__, __FUNCTION__, "email");
        }
        /** @var Mage_Customer_Model_Customer $model */
        $model = Mage::getModel('customer/customer');
        //====================================================================//
        // Create Empty Customer
        $customer = $model->setStore($this->getSplashOriginStore());
        $customer->setData("firstname", $this->in["firstname"]);
        $customer->setData("lastname", $this->in["lastname"]);
        $customer->setData("email", $this->in["email"]);
        //====================================================================//
        // Save Object
        try {
            $customer->save();
        } catch (Mage_Customer_Exception $ex) {
            Splash::log()->deb($ex->getTraceAsString());

            return Splash::log()->errTrace($ex->getMessage());
        }

        return $customer;
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
     * @param int $objectId Object Id.  If NULL, Object needs to be created.
     *
     * @return bool
     */
    public function delete($objectId = null): bool
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace();
        //====================================================================//
        // Execute Generic Magento Delete Function ...
        return $this->coreDelete('customer/customer', $objectId);
    }
}
