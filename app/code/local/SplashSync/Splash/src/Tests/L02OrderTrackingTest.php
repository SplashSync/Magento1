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
use Mage_Sales_Model_Order_Shipment_Track;
use Splash\Client\Splash;
use Splash\Tests\Tools\ObjectsCase;

/**
 * Local Test Suite - Order Tracking Reading Verifications
 */
class L02OrderTrackingTest extends ObjectsCase
{
    /**
     * Test Reading of Order Tracking Numbers
     *
     * @return void
     */
    public function testOrdersTrackingNumbers(): void
    {
        //====================================================================//
        //   Search for Orders including Bundle Products
        /** @var Mage_Sales_Model_Order_Shipment_Track $model */
        $model = Mage::getModel('sales/order_shipment_track');
        $collection = $model
            ->getCollection()
            ->addAttributeToSelect('order_id')
        ;
        //====================================================================//
        //   Skip if Empty
        if ($collection->getSize() < 1) {
            $this->markTestSkipped('No Orders including Tracking Numbers in Database.');
        }
        //====================================================================//
        //   Perform Tests
        foreach ($collection->getItems() as $listItem) {
            $this->verifyNumbers($listItem["order_id"]);
        }
    }

    /**
     * Verify Tracking Numbers
     *
     * @param string $objectId
     *
     * @throws Exception
     *
     * @return void
     */
    private function verifyNumbers(string $objectId): void
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
        $this->assertArrayHasKey("tracking", $data);
        $this->assertNotEmpty($data["tracking"]);
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
        $this->assertNotEmpty($order->getTracksCollection());

        //====================================================================//
        //   Verify Quantities
        $totalFound = 0;
        /** @var Mage_Sales_Model_Order_Shipment_Track[] $orderTracks */
        $orderTracks = $order->getTracksCollection();
        foreach ($orderTracks as $key => $orderTracking) {
            $this->assertEquals(
                $orderTracking->getTitle(),
                $data["tracking"][$key - 1]["title"]
            );
            $this->assertEquals(
                $orderTracking->getCarrierCode(),
                $data["tracking"][$key - 1]["carrier_code"]
            );
            $this->assertEquals(
                $orderTracking->getNumber(),
                $data["tracking"][$key - 1]["track_number"]
            );

            if (!$totalFound) {
                $this->assertEquals($orderTracking->getTitle(), $data["title"]);
                $this->assertEquals($orderTracking->getCarrierCode(), $data["carrier_code"]);
                $this->assertEquals($orderTracking->getNumber(), $data["tracking"][$key - 1]["track_number"]);
            }

            $totalFound++;
        }
        $this->assertGreaterThan(0, $totalFound);
    }
}
