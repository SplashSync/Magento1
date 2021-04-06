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

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
// phpcs:disable Squiz.Classes.ValidClassName

/**
 * Class SplashSync_Splash_IndexController
 *
 * @SuppressWarnings(PHPMD.CamelCaseClassName)
 */
class SplashSync_Splash_IndexController extends Mage_Adminhtml_Controller_Action
{
    /**
     *  Splash Module Class Constructor
     */
    public function __construct(
        Zend_Controller_Request_Abstract $controller,
        Zend_Controller_Response_Abstract $response
    ) {
        //====================================================================//
        // Load Splash Module
        require_once(dirname(dirname(__FILE__)).'/vendor/autoload.php');
        parent::__construct($controller, $response);
    }

    /**
     * @return void
     */
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
        /** @var Mage_Adminhtml_Block_Template $languages */
        $languages = $this->getLayout()->createBlock('adminhtml/template');
        $languages->setTemplate('Splash/languages.phtml');
        $this->_addContent($languages);
        //====================================================================//
        // Create Origins Setup Block
        /** @var Mage_Adminhtml_Block_Template $origins */
        $origins = $this->getLayout()->createBlock('adminhtml/template');
        $origins->setTemplate('Splash/origins.phtml');
        $this->_addContent($origins);

        $this->renderLayout();
    }
}
