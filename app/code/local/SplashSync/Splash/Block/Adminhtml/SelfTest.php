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
// phpcs:disable PSR2.Methods.MethodDeclaration
// phpcs:disable Squiz.Classes.ValidClassName

use Mage_Core_Block_Abstract;
use Splash\Client\Splash;
use Splash\Components\Logger;

/**
 * Class SplashSync_Splash_Block_Adminhtml_SelfTest
 *
 * @SuppressWarnings(PHPMD.CamelCaseClassName)
 * @SuppressWarnings(PHPMD.LongClassName)
 */
class SplashSync_Splash_Block_Adminhtml_SelfTest extends Mage_Core_Block_Template
{
    /**
     * @var array
     */
    public $results = array();

    /**
     * @var Logger
     */
    public $messages;

    /**
     * SplashSync_Splash_Block_Adminhtml_SelfTest constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->setTemplate('Splash/selftest.phtml');
    }

    /**
     * SelfTests Results
     */
    public function selfTests(): void
    {
        $this->results[] = array(
            "id" => count($this->results) + 1,
            "name" => 'Server SelfTest',
            "desc" => 'Verify configuration & functionality of this server.',
            "result" => Splash::selfTest()?"Pass":"Fail",
        );
    }

    /**
     * List available Objects
     */
    public function listObjects(): void
    {
        //====================================================================//
        // List Objects
        //====================================================================//
        $objectsList = count(Splash::objects()).' (';
        foreach (Splash::objects() as $value) {
            $objectsList .= $value.", ";
        }
        $objectsList .= ")";

        $this->results[] = array(
            "id" => count($this->results) + 1,
            "name" => 'Available Objects',
            "desc" => 'List of all Available objects on this server.',
            "result" => $objectsList,
        );
    }

    /**
     * Server Ping Tests
     */
    public function serverPing(): void
    {
        //====================================================================//
        // Splash Server Ping
        //====================================================================//
        $this->results[] = array(
            "id" => count($this->results) + 1,
            "name" => 'Ping Test',
            "desc" => 'Test to Ping Splash Server.',
            "result" => Splash::ping(false)?"Pass":"Fail",
        );
    }

    /**
     * Server Connect Test
     */
    public function serverConnect(): void
    {
        //====================================================================//
        // Splash Server Connect
        //====================================================================//
        $this->results[] = array(
            "id" => count($this->results) + 1,
            "name" => 'Connect Test',
            "desc" => 'Test to Connect to Splash Server.',
            "result" => Splash::connect(false)?"Pass":"Fail",
        );
    }

    /**
     * @return Mage_Core_Block_Abstract.
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    protected function _beforeToHtml()
    {
        //====================================================================//
        // Init Result Array
        $this->results = array();

        //====================================================================//
        // Execute Server Tests
        $this->selfTests();
        $this->listObjects();
        $this->serverPing();
        $this->serverConnect();

        //====================================================================//
        // Post Splash Messages
        $this->messages = Splash::log();

        $this->setData(array(
            'results' => $this->results,
            'messages' => $this->messages->getRawLog()
        ));

        return parent::_beforeToHtml();
    }
}
