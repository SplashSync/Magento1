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

use Splash\Client\Splash;

/**
 * @abstract    Splash PHP Module For Magento 1 - Data Observer
 * @author      B. Paquier <contact@splashsync.com>
 */
class SplashSync_Splash_Model_Observer
{
    
    /*
     * Objects Ressources Filter
     */
    private $ResourceFilter = array(
        "customer/customer",
        "customer/address",
        "catalog/product",
        "sales/order",
        "sales/order_invoice"
    );

    /*
     * Objects Ressources Types
     */
    private $ResourceTypes = array(
        "customer/customer"     => "ThirdParty",
        "customer/address"      => "Address",
        "catalog/product"       => "Product",
        "sales/order"           => "Order",
        "sales/order_invoice"   => "Invoice"
    );
    
    /*
     * Objects Ressources Names
     */
    private $ResourceNames = array(
        "customer/customer"     => "Customer",
        "customer/address"      => "Customer Address",
        "catalog/product"       => "Product",
        "sales/order"           => "Customer Order",
        "sales/order_invoice"   => "Customer Invoice"
    );
    
    /*
     * Ensure Splash Libraries are Loaded
     */
    private function _SplashInit()
    {
        //====================================================================//
        // Load Splash Module
        //====================================================================//
        require_once(dirname(dirname(__FILE__)) . '/vendor/autoload.php');
        Splash::Core();
    }
    
    /*
     * Ensure Event is in Requiered Scope (Object action, Resources Filter)
     *
     * @return mixed         Return Event Objects if Event to be treated
     */
    private function _FilterEvent(Varien_Event_Observer $observer)
    {
        //====================================================================//
        // Get Object From Event Class
        $Object = $observer->getEvent()->getObject();
        if (is_null($Object)) {
            return null;
        }
        //====================================================================//
        // Get Object Type from Class
        $ResourceName   =    $Object->getResourceName();
        if (is_null($ResourceName)) {
            return null;
        }
        //====================================================================//
        // Filter Object Type
        if (!in_array($ResourceName, $this->ResourceFilter)) {
            return null;
        }
        return $Object;
    }
    
    /*
     * Generic Splash Object Changes Commit Function
     */
    public function _CommitChanges($_Type, $_Action, $_Id, $_Comment)
    {
        //====================================================================//
        // Complete Comment for Logging
        $_Comment .= " " . $_Action . " on Magento 1";
        //====================================================================//
        // Prepare User Name for Logging
        $AdminUser = Mage::getSingleton('admin/session')->getUser();
        if (!empty($AdminUser)) {
            $_User   = $AdminUser->getUsername();
        } else {
            $_User   = 'Unknown Employee';
        }
        //====================================================================//
        // Init Splash Module
        $this->_SplashInit();
        //====================================================================//
        // Prevent Repeated Commit if Needed
        if (($_Action == SPL_A_UPDATE) && Splash::Object($_Type)->isLocked()) {
            return true;
        }
        //====================================================================//
        // Commit Action on remotes nodes (Master & Slaves)
        $result = Splash::Commit($_Type, $_Id, $_Action, $_User, $_Comment);
        //====================================================================//
        // Post Splash Messages
        $this->_importLog(Splash::Log());
        return $result;
    }
    
    /*
     * Object Change Save Before Event = Used only to detect Object Id and Create/Update Actions
     */
    public function onSaveBefore(Varien_Event_Observer $observer)
    {
        //====================================================================//
        // Filter & Get Object From Event Class
        $Object = $this->_FilterEvent($observer);
        if (is_null($Object)) {
            return;
        }
        //====================================================================//
        // Init Splash Module
        $this->_SplashInit();
        //====================================================================//
        // Verify if Object is New & Store Entity Id
        if ($Object->isObjectNew()) {
            Splash::Local()->_Action    = SPL_A_CREATE;
        } else {
            Splash::Local()->_Action    = SPL_A_UPDATE;
        }

        return true;
    }

    /*
     * Object Change Save Commit After Event = Execute Splash Commits for Create/Update Actions
     */
    public function onSaveCommitAfter(Varien_Event_Observer $observer)
    {
        //====================================================================//
        // Filter & Get Object From Event Class
        $Object = $this->_FilterEvent($observer);
        if (is_null($Object)) {
            return;
        }
        //====================================================================//
        // Init Splash Module
        $this->_SplashInit();
        //====================================================================//
        // Translate Object Type & Comment
        $_Type      =   $this->ResourceTypes[$Object->getResourceName()];
        $_Comment   =   $this->ResourceNames[$Object->getResourceName()];
        //====================================================================//
        // Do Generic Change Commit
        $this->_CommitChanges($_Type, Splash::Local()->_Action, $Object->getEntityId(), $_Comment);
        return true;
    }
    
    /*
     * Object Change Delete Commit After Event = Execute Splash Commits for Delete Actions
     */
    public function onDeleteCommitAfter(Varien_Event_Observer $observer)
    {
        //====================================================================//
        // Filter & Get Object From Event Class
        $Object = $this->_FilterEvent($observer);
        if (is_null($Object)) {
            return;
        }
        //====================================================================//
        // Init Splash Module
        $this->_SplashInit();
        //====================================================================//
        // Translate Object Type & Comment
        $_Type      =   $this->ResourceTypes[$Object->getResourceName()];
        $_Comment   =   $this->ResourceNames[$Object->getResourceName()];
        //====================================================================//
        // Do Generic Change Commit
        $this->_CommitChanges($_Type, SPL_A_DELETE, $Object->getEntityId(), $_Comment);
    }
    
    protected function _importLog($Log)
    {
        //====================================================================//
        // Import Errors
        if (isset($Log->err) && !empty($Log->err)) {
            $this->_importMessages($Log->err, "addError");
        }
        //====================================================================//
        // Import Warnings
        if (isset($Log->war) && !empty($Log->war)) {
            $this->_importMessages($Log->war, "addWarning");
        }
        //====================================================================//
        // Import Messages
        if (isset($Log->msg) && !empty($Log->msg)) {
            $this->_importMessages($Log->msg, "addSuccess");
        }
        //====================================================================//
        // Import Debug
        if (isset($Log->deb) && !empty($Log->deb)) {
            $this->_importMessages($Log->deb, "addSuccess");
        }
    }
    private function _importMessages($MessagesArray, $Method)
    {
        foreach ($MessagesArray as $Message) {
            Mage::getSingleton('adminhtml/session')->$Method($Message);
        }
    }    
}
