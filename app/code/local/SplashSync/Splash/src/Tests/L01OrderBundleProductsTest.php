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
class L01OrderBundleProductsTest extends ObjectsCase {
    
    public function testOrdersBundleProductsHaveNoQty()
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
        if ( $Collection->getSize() < 1 ) {
            $this->markTestSkipped('No Orders including Bundle Products in Database.');
            return False;
        }         

        //====================================================================//
        //   Perform Tests
        foreach ($Collection->getItems() as $listItem) {
            $this->verifyItems($listItem["order_id"]);
        }
        
    }

    private function verifyItems(int $objectId)
    {
        //====================================================================//
        //   Get Readable Object Fields List  
        $Fields = $this->reduceFieldList(Splash::Object("Order")->Fields(), True, False);
        //====================================================================//
        //   Read Order Data from Module
        $Data   =   Splash::Object("Order")->get($objectId, $Fields);
        //====================================================================//
        //   Basic verifications  
        $this->assertNotEmpty($Data);
        $this->assertArrayHasKey("lines" , $Data);
        $this->assertNotEmpty($Data["lines"]);
        
        //====================================================================//
        //   Read Order from Magento
        $Order  =   Mage::getModel('sales/order')->load($objectId);
        
        //====================================================================//
        //   Basic verifications  
        $this->assertNotEmpty($Order);
        $this->assertNotEmpty($Order->getAllItems());

        //====================================================================//
        //   Verify Quantities  
        $totalFound =   0;
        foreach ($Order->getAllItems() as $Key => $orderItem) {
            if ($orderItem->getProductType() == "bundle") {
                $this->assertEquals(0 , $Data["lines"][$Key]["qty_ordered"]);
            } 
            if ($orderItem->getHasChildren() == "bundle") {
                $this->assertEquals(0 , $Data["lines"][$Key]["qty_ordered"]);
            } 
            $totalFound++;
        }        
        $this->assertGreaterThan(0 , $totalFound);
        
    }    
    
}
