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

namespace Splash\Local\Tests;

use Exception;
use Mage;
use Mage_Sales_Model_Order;
use Mage_Sales_Model_Order_Item;
use Splash\Client\Splash;
use Splash\Tests\Tools\ObjectsCase;

/**
 * @abstract    Local Test Suite - Bundle Products Reading Verifications
 *
 * @author SplashSync <contact@splashsync.com>
 */
class L01OrderBundleProductsTest extends ObjectsCase
{
    /**
     * Test Order Products Reading
     *
     * @return void
     */
    public function testOrdersBundleProductsReading(): void
    {
        //====================================================================//
        //   Search for Orders including Bundle Products
        /** @var Mage_Sales_Model_Order_Item $model */
        $model = Mage::getModel('sales/order_item');
        $collection = $model
            ->getCollection()
            ->addAttributeToFilter('product_type', array('eq' => "bundle"))
            ->addAttributeToSelect('order_id')
        ;

        //====================================================================//
        //   Skip if Empty
        if ($collection->getSize() < 1) {
            $this->markTestSkipped('No Orders including Bundle Products in Database.');
        }

        //====================================================================//
        //   Perform Tests
        foreach ($collection->getItems() as $listItem) {
            //====================================================================//
            //   Setup Bundle Products Prices Mode
            $this->setBundlePricesMode(false);
            //====================================================================//
            //   Verify order Items
            $this->verifyItems($listItem["order_id"]);
            //====================================================================//
            //   Setup Bundle Products Prices Mode
            $this->setBundlePricesMode(true);
            //====================================================================//
            //   Verify order Items
            $this->verifyItems($listItem["order_id"]);
        }
    }

    /**
     * Verify Order Data Reading Without Bundle Price Mode Enabled
     *
     * @param string $objectId
     *
     * @return void
     */
    private function verifyItems(string $objectId): void
    {
        //====================================================================//
        //   Read Order Data from Module
        $data = $this->getOrderItemsData($objectId);
        //====================================================================//
        //   Read Order from Magento
        $items = $this->getMagentoOrderItems($objectId);
        //====================================================================//
        //   Verify Quantities
        $totalFound = 0;
        foreach ($items as $key => $orderItem) {
            $splashItem = $data["lines"][$key];
            //====================================================================//
            //   Splash Item Has Qty & Prices Set
            $this->assertArrayHasKey("qty_ordered", $splashItem);
            $this->assertArrayHasKey("unit_price", $splashItem);
            $this->assertNotEmpty($splashItem["unit_price"]);
            if (("bundle" == $orderItem->getProductType()) || $orderItem->getHasChildren()) {
                //====================================================================//
                //   Bundle Has Null Qty
                $this->assertEquals(0, $splashItem["qty_ordered"]);
                //====================================================================//
                //   Bundle Has Real Prices
                $this->assertGreaterThan(0, $splashItem["unit_price"]["ht"]);
                $this->assertGreaterThan(0, $splashItem["unit_price"]["ttc"]);
                $totalFound++;
            }
            //====================================================================//
            //   Bundle Has Qty
            if ($orderItem->getParentItemId()) {
                $this->assertGreaterThan(0, $splashItem["qty_ordered"]);
                $this->assertGreaterThan(0, $splashItem["unit_price"]["ht"]);
                $this->assertGreaterThan(0, $splashItem["unit_price"]["ttc"]);
                $totalFound++;
            }
        }
        $this->assertGreaterThan(1, $totalFound);
    }

    /**
     * Setup Bundle Price Mode on Magento Configuration
     *
     * @param bool $mode
     *
     * @return void
     */
    private function setBundlePricesMode(bool $mode): void
    {
        Mage::getConfig()->saveConfig(
            'splashsync_splash_options/advanced/bundle_mode',
            $mode ? '1' : '0',
            'default',
            0
        );
    }

    /**
     * Get Splash Order Data
     *
     * @param string $objectId
     *
     * @throws Exception
     *
     * @return array
     */
    private function getOrderItemsData(string $objectId): array
    {
        //====================================================================//
        //   Get Readable Object Fields List
        $fields = $this->reduceFieldList(Splash::object("Order")->fields(), true, false);
        //====================================================================//
        //   Read Order Data from Module
        $data = Splash::object("Order")->get($objectId, $fields);
        //====================================================================//
        //   Basic verifications
        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
        $this->assertArrayHasKey("lines", $data);
        $this->assertNotEmpty($data["lines"]);

        return $data;
    }

    /**
     * Get Magento Order Items
     *
     * @param string $objectId
     *
     * @return array
     */
    private function getMagentoOrderItems(string $objectId)
    {
        //====================================================================//
        //   Read Order from Magento
        /** @var Mage_Sales_Model_Order $model */
        $model = Mage::getModel('sales/order');
        /** @var false|Mage_Sales_Model_Order $order */
        $order = $model->load((int) $objectId);
        //====================================================================//
        //   Basic verifications
        $this->assertNotEmpty($order);
        $this->assertInstanceOf(Mage_Sales_Model_Order::class, $order);
        $this->assertNotEmpty($order->getAllItems());

        return $order->getAllItems();
    }
}
