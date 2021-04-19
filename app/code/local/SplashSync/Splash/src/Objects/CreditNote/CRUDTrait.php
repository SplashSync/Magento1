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

namespace Splash\Local\Objects\CreditNote;

use Mage;
use Mage_Sales_Model_Order;
use Mage_Sales_Model_Order_Creditmemo;
use Mage_Sales_Model_Order_Creditmemo_Item;
use Splash\Core\SplashCore      as Splash;

/**
 * Magento 1 Credit Notes CRUD Functions
 */
trait CRUDTrait
{
    //====================================================================//
    // General Class Variables
    //====================================================================//

    /**
     * @var Mage_Sales_Model_Order
     */
    protected $order;

    /**
     * @var Mage_Sales_Model_Order_Creditmemo_Item[]
     */
    protected $products;

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
            return Splash::log()->errTrace("Missing Credit memo Id.");
        }

        /** @var Mage_Sales_Model_Order_Creditmemo $model */
        $model = Mage::getModel(self::$modelName);
        /** @var false|Mage_Sales_Model_Order_Creditmemo $creditMemo */
        $creditMemo = $model->load((int) $objectId);
        if (!$creditMemo || ($creditMemo->getEntityId() != $objectId)) {
            return Splash::log()->errTrace("Unable to load Customer Credit memo (".$objectId.").");
        }
        //====================================================================//
        // Load Linked Objects
        $this->order = $creditMemo->getOrder();
        $this->products = $creditMemo->getAllItems();
        $this->loadPayment($this->order);

        return $creditMemo;
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
        return $this->coreDelete(self::$modelName, $objectId);
    }
}
