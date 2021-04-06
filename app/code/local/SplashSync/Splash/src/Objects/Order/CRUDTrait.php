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
use Mage_Catalog_Exception;
use Mage_Sales_Model_Order      as MageOrder;
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
     * @return mixed
     */
    public function load($objectId)
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace();
        //====================================================================//
        // Safety Checks
        if (empty($objectId)) {
            return Splash::log()->errTrace("Missing Id.");
        }
        /** @var MageOrder $model */
        $model = Mage::getModel('sales/order');
        /** @var false|MageOrder $order */
        $order = $model->load((int) $objectId);
        if (!$order || ($order->getEntityId() != $objectId)) {
            return Splash::log()->errTrace("Unable to load Customer Order (".$objectId.").");
        }
        //====================================================================//
        // Load Linked Objects
        $this->loadPayment($order);
        $this->loadTracking($order);

        return $order;
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
        // Create Empty Customer Order
        /** @var MageOrder $order */
        $order = Mage::getModel('sales/order');
        //====================================================================//
        // Setup Order External Id to Know this is a Splash Created Order
        $order->setExtOrderId(self::SPLASH_LABEL);
        // Set Is Virtual Order => No Billing or Shipping Address
        $order->setIsVirtual(1);
        // Set Default Payment Method
        $order->setData('payment', array('method' => 'checkmo'));
        // Set Order Initial Status
        $order->setState(MageOrder::STATE_NEW, "pending", 'Just Created by SplashSync Module', true);
        //====================================================================//
        // Set Currency To Default Store Values
        $defaultCurrency = Mage::app()->getStore()->getBaseCurrencyCode();
        $order->setGlobalCurrencyCode($defaultCurrency);
        $order->setOrderCurrencyCode($defaultCurrency);
        $order->setBaseCurrencyCode($defaultCurrency);
        $order->setBaseToOrderRate(1);
        //====================================================================//
        // Save Object
        try {
            $order->save();
        } catch (Mage_Catalog_Exception $ex) {
            return Splash::log()->report($ex);
        }
        //====================================================================//
        // Load Linked Objects
        $this->loadPayment($order);

        return $order;
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
        //====================================================================//
        // Stack Trace
        Splash::log()->trace();
        if (!$needed) {
            return $this->object->getEntityId();
        }
        //====================================================================//
        // Update order Totals
        $this->updateOrderTotals();
        //====================================================================//
        // Verify Update Is Required
        if (false == $needed) {
            return $this->object->getEntityId();
        }
        //====================================================================//
        // If Id Given = > Update Object
        //====================================================================//
        try {
            $this->object->save();
        } catch (Mage_Catalog_Exception $ex) {
            return Splash::log()->report($ex);
        }

        if ($this->object->_hasDataChanges) {
            return Splash::log()->errTrace("Unable to Update Order (".$this->object->getEntityId().").");
        }
        Splash::object("Order")->lock($this->object->getEntityId());

        return $this->object->getEntityId();
    }

    /**
     * Delete requested Object
     *
     * @param int $objectId Object Id.  If NULL, Object needs to be created.
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
        return $this->coreDelete('sales/order', $objectId);
    }
}
