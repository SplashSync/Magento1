<?php

/*
 * This file is part of SplashSync Project.
 *
 * Copyright (C) Splash Sync <www.splashsync.com>
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * @abstract    Splash PHP Module For Magento 1
 * @author      B. Paquier <contact@splashsync.com>
 */

use Splash\Client\Splash;

class SplashSync_Splash_Block_Adminhtml_SelfTest extends Mage_Core_Block_Template
{
    public $Results =   array();
    
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('Splash/selftest.phtml');
    }
  
    protected function _beforeToHtml()
    {
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

        $this->setData(array( 
                'results'   => $this->Results,
                'messages'  => $this->Messages->GetRawLog()
                ));
        
        return parent::_beforeToHtml();
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
        
        $this->Results[] = array(
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
            "result"=>  Splash::Ping(False)?"Pass":"Fail",
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
            "result"=>  Splash::Connect(False)?"Pass":"Fail",
        );
    }

            

    
}