<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @category    SplashSync
 * @package     SplashSync_Splash
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @generator   http://www.mgt-commerce.com/kickstarter/ Mgt Kickstarter
 */

use Splash\Client\Splash;

class SplashSync_Splash_IndexController extends Mage_Adminhtml_Controller_Action
{
    
    /**
    *  @abstract    Splash Module Class Constructor 
    *  @return      None
    */    
    public function _Initialize()
    {
        //====================================================================//
        // Load Splash Module
        //====================================================================//
        require_once( dirname(dirname(__FILE__)) . '/vendor/autoload.php'); 
    }
    
    public function indexAction()
    {
        //====================================================================//
        // Init Splash Module
        $this->_Initialize();
        //====================================================================//
        // Init Result Array
        $this->Results = array();
        $this->Messages = array();
        
        //====================================================================//
        // Execute Server Tests
        $this->TST_SelfTests();
        $this->TST_ListObjects();
        $this->TST_ServerPing();
        $this->TST_ServerConnect();
        //====================================================================//
        // Post Splash Messages
        $this->Messages = Splash::Log();
        //====================================================================//
        // Load Magento Layout
        $this->loadLayout();
        //====================================================================//
        // Render Page
        // Create SelfSetup Block Block
        $block_selftest = $this->getLayout()
            ->createBlock('adminhtml/template')
            ->setTemplate('splashsync/soap/selftest.phtml')
            ->setData(array( 
                'results'   => $this->Results,
                'messages'  => $this->Messages->GetRawLog()
                ));
        $this->_addContent($block_selftest);
        
        //====================================================================//
        // Create Languages Setup Block
        $block_languages = $this->getLayout()
            ->createBlock('adminhtml/template')
            ->setTemplate('splashsync/soap/languages.phtml')
            ->setData(array( 
                'results'   => $this->Results,
                'messages'  => $this->Messages->GetRawLog()
                ));
        $this->_addContent($block_languages);
        
        
        //====================================================================//
        // Create Languages Setup Block
        $block_origins = $this->getLayout()
            ->createBlock('adminhtml/template')
            ->setTemplate('splashsync/soap/origins.phtml')
            ->setData(array( 
                'results'   => $this->Results,
                'messages'  => $this->Messages->GetRawLog()
                ));
        $this->_addContent($block_origins);
       
        $this->renderLayout();
    }

    public function debugAction()
    {
        //====================================================================//
        // Init Splash Module
        $this->_Initialize();
        
        //====================================================================//
        // Module Tests & Debugs
        //====================================================================//
        
        $Text   =   "<PRE>";
        
foreach (Mage::app()->getWebsites() as $website) {
//    echo $website->getConfig('splashsync_splash_options/thirdparty/origin')."<br/>";
    echo $website->getId() ." ".$website->getName(). " " . $website->getDefaultStore()->getId() . "<br/>";
//    foreach ($website->getGroups() as $group) {
//        $stores = $group->getStores();
        foreach ($website->getStores() as $store) {
            echo $store->getId() ." ".$store->getName()."<br/>";
        }
//    }
}       

////        echo   "<PRE>";
//        $Id = Splash::Object("Order")->Set(0,$Data);
//
////        $Text  .=   print_r($Data, TRUE);
////        $Text  .=   print_r(Mage::getModel('sales/order')->load(1)->getAllItems(), TRUE);
//    foreach(Mage::getModel('sales/order')->load($Id)->getAllItems() as $Key => $item):
////        $Text  .=   print_r($Key, TRUE);
//        $Text  .=   print_r($item->getData(), TRUE);
//    endforeach;
//        $Text  .=   print_r(Mage::getModel('sales/order')->load($Id)->getData(), TRUE);

for( $i=3; $i<600; $i++) {
//    Splash::Object("Order")->Delete($i);
}    
//        $Text  .=   print_r(Mage::getModel('catalog/product')->load(1), TRUE);
        $Text  .=   print_r(Mage::getModel('customer/customer')->load(67)->getSplashId(), TRUE);
//        $Text  .=   print_r(Mage::getModel('sales/order')->load(2)->getData(), TRUE);

//$Text  .=   print_r(Mage::getModel('sales/order_invoice')->load(2)->getPaymentsCollection()->count(), TRUE) . PHP_EOL;
//        
//foreach (Mage::getModel('sales/order_invoice')->load(2)->getPaymentsCollection() as $Payment) {

//$collection = Mage::getModel('sales/order_payment_transaction')->getCollection()
//                    ->setOrderFilter(Mage::getModel('sales/order')->load(3))
//                    ->addPaymentIdFilter(Mage::getModel('sales/order')->load(3)->getPayment()->getId())
//                    ->addTxnTypeFilter(Mage_Sales_Model_Order_Payment_Transaction::TYPE_PAYMENT)
//                    ->setOrder('created_at', Varien_Data_Collection::SORT_ORDER_DESC)
//                    ->setOrder('transaction_id', Varien_Data_Collection::SORT_ORDER_DESC);
//
//        $Text  .=   print_r($collection->getData(), TRUE) . PHP_EOL;
//        
//} 
//    $order = Mage::getModel('sales/order')->load(3)->getPayment();
//    $payment = $order;
//    $payment->setTransactionId("1212120");
//    $transaction = $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_PAYMENT, null, false, "My Transaction");
    
//    $transaction = $payment->GetTransaction("1212120");
//    $transaction->setParentTxnId("12100000");
//    $transaction->setAdditionalInformation(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS,array("Amount" => "10") );
//    $transaction->setTxnType(Mage_Sales_Model_Order_Payment_Transaction::TYPE_PAYMENT);
//    
//    
//    $transaction->setIsClosed(true);
////    $transaction->setAdditionalInformation("arrI    nfo", serialize($arrInformation));
//    $transaction->save();
//    $order->save();

//        $Text  .=   print_r(Mage::getModel('sales/order')->load(3)->getData(), TRUE);
//        $Text  .=   print_r(Mage::getModel('sales/order')->load(9)->getBillingAddress()->getData(), TRUE);
//        $Text  .=   print_r(Mage::getModel('sales/order')->load(9)->getShippingAddress()->getData(), TRUE);
//        $Text  .=   print_r(Mage::getModel('sales/order')->load(1)->getAllItems(), TRUE);
//        $Text  .=   print_r(Mage::getModel('sales/order')->load(2)->getPaymentsCollection()->getData(), TRUE);
//        $Text  .=   print_r(Mage::getModel('sales/order')->load(2)->getShipmentsCollection()->getData(), TRUE);
//        $Text  .=   print_r(Mage::getModel('sales/order')->load(2)->getTracksCollection()->getData(), TRUE);

$Invoice = Mage::getModel('sales/order_invoice')->load(5);
//$Invoice->setData("grand_total",100);
        $Text  .=   print_r($Invoice->getData(), TRUE);
//        $Text  .=   print_r($Invoice->getOrigData("grand_total"), TRUE);
        
//        $Text  .=   print_r(Mage::getModel('sales/order_invoice')->load(5)->getOrder()->getPaymentsCollection(), TRUE);
//        $Text  .=   print_r(Mage::getModel('sales/order_invoice')->load(1)->getItemsCollection()->getFirstItem()->getData(), TRUE);
//        $Text  .=   print_r(Mage::getModel('sales/order_invoice')->load(1)->getItemsCollection()->getFirstItem()->getData(), TRUE);


//        foreach (Mage::getModel('catalog/product')->load(1)->getMediaGalleryImages() as $image) {
//            $Text  .=   print_r($image->getPath(), TRUE);
//            $Text  .=   print_r($image, TRUE);
//        }  
        //====================================================================//
        // Render Page
        //====================================================================//
        $this->loadLayout();
        //create a text block with the name of "example-block"
        $block = $this->getLayout()
        ->createBlock('core/text', 'debug-block')
        ->setText($Text);
        $this->_addContent($block);
        $this->renderLayout();
    }
    
    public function TST_SelfTests()
    {
        //====================================================================//
        // List Objects
        //====================================================================//
        $ObjectsList    = count(Splash::Objects()) . ' (';
        foreach (Splash::Objects() as $value) {
            $ObjectsList    .= $value . ", ";
        }
        $ObjectsList    .= ")";
        
        $this->Results[] = Array(
            "id"    =>  count($this->Results) + 1,
            "name"  =>  'Server SelfTest',
            "desc"  =>  'Verify configuration & functionnality of this server.',
            "result"=>  Splash::SelfTest()?"Pass":"Fail",
        );
    }
    

    public function TST_ListObjects()
    {
        //====================================================================//
        // List Objects
        //====================================================================//
        $ObjectsList    = count(Splash::Objects()) . ' (';
        foreach (Splash::Objects() as $value) {
            $ObjectsList    .= $value . ", ";
        }
        $ObjectsList    .= ")";
        
        $this->Results[] = Array(
            "id"    =>  count($this->Results) + 1,
            "name"  =>  'Available Objects',
            "desc"  =>  'List of all Available objects on this server.',
            "result"=>  $ObjectsList,
        );
    }
    
    public function TST_ServerPing()
    {
        //====================================================================//
        // Splash Server Ping
        //====================================================================//
        $this->Results[] = Array(
            "id"    =>  count($this->Results) + 1,
            "name"  =>  'Ping Test',
            "desc"  =>  'Test to Ping Splash Server.',
            "result"=>  Splash::Ping()?"Pass":"Fail",
        );
    }

    public function TST_ServerConnect()
    {
        //====================================================================//
        // Splash Server Connect
        //====================================================================//
        $this->Results[] = Array(
            "id"    =>  count($this->Results) + 1,
            "name"  =>  'Connect Test',
            "desc"  =>  'Test to Connect to Splash Server.',
            "result"=>  Splash::Connect()?"Pass":"Fail",
        );
    }

    public function TST_StoreLanguages()
    {
        $this->Languages = [];
        
        //====================================================================//
        // Splash Store View To Languages Mapping
        //====================================================================//
        foreach (Mage::app()->getWebsites() as $Website) {
            foreach ($Website->getStores() as $Store) {
                if ( empty($Store->getConfig('splashsync_splash_options/langs/store_lang')) ) {
                    return Splash::Log()->Err("Multi-Language mode, You must select a Language for Store: " . $Store->getName() );
                }
            }
        }       
        
        
    }    
    
}