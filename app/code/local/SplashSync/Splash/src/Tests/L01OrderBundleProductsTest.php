<?php

namespace Splash\Local\Tests;

use Splash\Tests\Tools\ObjectsCase;

use Splash\Client\Splash;

use Mage;

/**
 * @abstract    Local Test Suite - Bundle Products Reading Verifications
 *
 * @author SplashSync <contact@splashsync.com>
 */
class L01OrderBundleProductsTest extends ObjectsCase
{
    
    public function testOrdersBundleProductsReading()
    {
        //====================================================================//
        //   Search for Orders including Bundle Products
        $Collection = Mage::getModel('sales/order_item')
                            ->getCollection()
                            ->addAttributeToFilter('product_type', array('eq' => "bundle"))
                            ->addAttributeToSelect('order_id')
                ;

        //====================================================================//
        //   Skip if Empty
        if ($Collection->getSize() < 1) {
            $this->markTestSkipped('No Orders including Bundle Products in Database.');
            return false;
        }

        //====================================================================//
        //   Perform Tests
        foreach ($Collection->getItems() as $listItem) {
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
     * @abstract    Verify Order Data Reading Without Bundle Price Mode Enabled
     * @param   string  $objectId
     */
    private function verifyItems(string $objectId)
    {
        //====================================================================//
        //   Read Order Data from Module
        $Data   =   $this->getOrderItemsData($objectId);
        //====================================================================//
        //   Read Order from Magento
        $Items  =   $this->getMagentoOrderItems($objectId);
        //====================================================================//
        //   Verify Quantities
        $totalFound =   0;
        foreach ($Items as $Key => $orderItem) {
            $SplashItem =   $Data["lines"][$Key];
            //====================================================================//
            //   Splash Item Has Qty & Prices Set
            $this->assertArrayHasKey("qty_ordered", $SplashItem);
            $this->assertArrayHasKey("unit_price",  $SplashItem);
            $this->assertNotEmpty($SplashItem["unit_price"]);
            if ( ($orderItem->getProductType() == "bundle") || $orderItem->getHasChildren() ){
                //====================================================================//
                //   Bundle Has Null Qty
                $this->assertEquals(0, $SplashItem["qty_ordered"]);
                //====================================================================//
                //   Bundle Has Real Prices
                $this->assertGreaterThan(0, $SplashItem["unit_price"]["ht"]);
                $this->assertGreaterThan(0, $SplashItem["unit_price"]["ttc"]);
                $totalFound++;
            }
            //====================================================================//
            //   Bundle Componant Has Qty
            if ($orderItem->getParentItemId()) {
                $this->assertGreaterThan(0, $SplashItem["qty_ordered"]);
                $this->assertGreaterThan(0, $SplashItem["unit_price"]["ht"]);
                $this->assertGreaterThan(0, $SplashItem["unit_price"]["ttc"]);
                $totalFound++;
            }
        }
        $this->assertGreaterThan(1, $totalFound);
    }
    
    
    /**
     * @abstract    Setup Bundle Price Mode on Magento Configuration
     */
    private function setBundlePricesMode(bool $Mode)
    {    
        Mage::getConfig()->saveConfig(
                'splashsync_splash_options/advanced/bundle_mode', $Mode ? '1' : '0', 
                'default', 
                0
            );
    }
    
    /**
     * @abstract    Get Splash Order Data
     * @param   string  $objectId
     * @return  array 
     */
    private function getOrderItemsData(string $objectId)
    {
        //====================================================================//
        //   Get Readable Object Fields List
        $Fields = $this->reduceFieldList(Splash::object("Order")->fields(), true, false);
        //====================================================================//
        //   Read Order Data from Module
        $Data   =   Splash::object("Order")->get($objectId, $Fields);
        //====================================================================//
        //   Basic verifications
        $this->assertNotEmpty($Data);
        $this->assertArrayHasKey("lines", $Data);
        $this->assertNotEmpty($Data["lines"]);
        
        return $Data;
    }   
    
    /**
     * @abstract    Get Magento Order Items
     * @param   string  $objectId
     */
    private function getMagentoOrderItems(string $objectId)
    {
        //====================================================================//
        //   Read Order from Magento
        $Order  =   Mage::getModel('sales/order')->load($objectId);
        //====================================================================//
        //   Basic verifications
        $this->assertNotEmpty($Order);
        $this->assertNotEmpty($Order->getAllItems());

        return $Order->getAllItems();
    }    
}
