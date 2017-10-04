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
use Mage;

class SplashSync_Splash_IndexController extends Mage_Adminhtml_Controller_Action
{
    
    /**
    *  @abstract    Splash Module Class Constructor 
    *  @return      None
    */    
    public function __construct(Zend_Controller_Request_Abstract $Controller, Zend_Controller_Response_Abstract $Response)
    {
        //====================================================================//
        // Load Splash Module
        require_once( dirname(dirname(__FILE__)) . '/vendor/autoload.php'); 
        parent::__construct($Controller, $Response);
    }
    
    public function indexAction()
    {
        //====================================================================//
        // Load Magento Layout
        $this->loadLayout();     
        //====================================================================//
        // Create SelfTests Block
        $this->_addContent($this->getLayout()->createBlock('Splash/Adminhtml_SelfTest'));
        //====================================================================//
        // Create Languages Setup Block
        $this->_addContent($this->getLayout()->createBlock('adminhtml/template')->setTemplate('Splash/languages.phtml'));
        //====================================================================//
        // Create Origins Setup Block
        $this->_addContent($this->getLayout()->createBlock('adminhtml/template')->setTemplate('Splash/origins.phtml'));
        
        $this->renderLayout();
    }
    
}