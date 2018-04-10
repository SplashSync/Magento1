<?php

namespace Splash\Local\Tests;

use Splash\Tests\Tools\ObjectsCase;

use Splash\Client\Splash;

use Mage;

/**
 * @abstract    Local Test Suite - Order Tracking Reading Verifications
 *
 * @author SplashSync <contact@splashsync.com>
 */
class L01OrderTrackingTest extends ObjectsCase
{
    
    public function testOrdersTrackingNumbers()
    {
        //====================================================================//
        //   Search for Orders including Bundle Products
        $Collection = Mage::getModel('sales/order_shipment_track')
                            ->getCollection()
                            ->addAttributeToSelect('order_id')
                ;

        //====================================================================//
        //   Skip if Empty
        if ($Collection->getSize() < 1) {
            $this->markTestSkipped('No Orders including Tracking Numbers in Database.');
            return false;
        }

        //====================================================================//
        //   Perform Tests
        foreach ($Collection->getItems() as $listItem) {
            $this->verifyNumbers($listItem["order_id"]);
        }
    }

    private function verifyNumbers(int $objectId)
    {
        //====================================================================//
        //   Get Readable Object Fields List
        $Fields = $this->reduceFieldList(Splash::Object("Order")->Fields(), true, false);
        //====================================================================//
        //   Read Order Data from Module
        $Data   =   Splash::Object("Order")->get($objectId, $Fields);
        //====================================================================//
        //   Basic verifications
        $this->assertNotEmpty($Data);
        $this->assertArrayHasKey("tracking", $Data);
        $this->assertNotEmpty($Data["tracking"]);
        //====================================================================//
        //   Read Order from Magento
        $Order  =   Mage::getModel('sales/order')->load($objectId);
        
        //====================================================================//
        //   Basic verifications
        $this->assertNotEmpty($Order);
        $this->assertNotEmpty($Order->getTracksCollection());
        
        //====================================================================//
        //   Verify Quantities
        $totalFound =   0;
        foreach ($Order->getTracksCollection() as $Key => $orderTracking) {
            $this->assertEquals(
                $orderTracking->getTitle(),
                $Data["tracking"][$Key-1]["title"]
            );
            $this->assertEquals(
                $orderTracking->getCarrierCode(),
                $Data["tracking"][$Key-1]["carrier_code"]
            );
            $this->assertEquals(
                $orderTracking->getTrackNumber(),
                $Data["tracking"][$Key-1]["track_number"]
            );

            if (!$totalFound) {
                $this->assertEquals($orderTracking->getTitle(), $Data["title"]);
                $this->assertEquals($orderTracking->getCarrierCode(), $Data["carrier_code"]);
                $this->assertEquals($orderTracking->getTrackNumber(), $Data["tracking"][$Key-1]["track_number"]);
            }
            
            $totalFound++;
        }
        $this->assertGreaterThan(0, $totalFound);
    }
}
