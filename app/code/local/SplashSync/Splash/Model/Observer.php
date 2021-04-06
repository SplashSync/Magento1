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

use Splash\Client\Splash;
use Splash\Components\Logger;
use Splash\Local\Local;

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
// phpcs:disable Squiz.Classes.ValidClassName

/**
 * Splash PHP Module For Magento 1 - Data Observer
 *
 * @SuppressWarnings(PHPMD.CamelCaseClassName)
 */
class SplashSync_Splash_Model_Observer
{
    /**
     * Objects Ressources Filter
     *
     * @var string[]
     */
    private $resourceFilter = array(
        "customer/customer",
        "customer/address",
        "catalog/product",
        "sales/order",
        "sales/order_invoice"
    );

    /**
     * Objects Ressources Types
     *
     * @var string[]
     */
    private $resourceTypes = array(
        "customer/customer" => "ThirdParty",
        "customer/address" => "Address",
        "catalog/product" => "Product",
        "sales/order" => "Order",
        "sales/order_invoice" => "Invoice"
    );

    /**
     * Objects Ressources Names
     *
     * @var string[]
     */
    private $resourceNames = array(
        "customer/customer" => "Customer",
        "customer/address" => "Customer Address",
        "catalog/product" => "Product",
        "sales/order" => "Customer Order",
        "sales/order_invoice" => "Customer Invoice"
    );

    /**
     * Object Change Save Before Event = Used only to detect Object Id and Create/Update Actions
     *
     * @param Varien_Event_Observer $observer
     *
     * @throws Exception
     */
    public function onSaveBefore(Varien_Event_Observer $observer): void
    {
        //====================================================================//
        // Filter & Get Object From Event Class
        $object = $this->filterEvent($observer);
        if (is_null($object)) {
            return;
        }
        //====================================================================//
        // Init Splash Module
        $this->ensureInit();
        //====================================================================//
        // Verify if Object is New & Store Entity Id
        /** @var Local $local */
        $local = Splash::local();
        if ($object->isObjectNew()) {
            $local->action = SPL_A_CREATE;
        } else {
            $local->action = SPL_A_UPDATE;
        }
    }

    /**
     * Object Change Save Commit After Event = Execute Splash Commits for Create/Update Actions
     *
     * @param Varien_Event_Observer $observer
     *
     * @throws Exception
     */
    public function onSaveCommitAfter(Varien_Event_Observer $observer): void
    {
        //====================================================================//
        // Filter & Get Object From Event Class
        $object = $this->filterEvent($observer);
        if (is_null($object)) {
            return;
        }
        //====================================================================//
        // Init Splash Module
        $this->ensureInit();
        //====================================================================//
        // Translate Object Type & Comment
        $objectType = $this->resourceTypes[$object->getResourceName()];
        $comment = $this->resourceNames[$object->getResourceName()];
        /** @var Local $local */
        $local = Splash::local();
        //====================================================================//
        // Do Generic Change Commit
        $this->commitChanges($objectType, $local->action, $object->getEntityId(), $comment);
    }

    /**
     * Object Change Delete Commit After Event = Execute Splash Commits for Delete Actions
     *
     * @param Varien_Event_Observer $observer
     */
    public function onDeleteCommitAfter(Varien_Event_Observer $observer): void
    {
        //====================================================================//
        // Filter & Get Object From Event Class
        $object = $this->filterEvent($observer);
        if (is_null($object)) {
            return;
        }
        //====================================================================//
        // Init Splash Module
        $this->ensureInit();
        //====================================================================//
        // Translate Object Type & Comment
        $objectType = $this->resourceTypes[$object->getResourceName()];
        $comment = $this->resourceNames[$object->getResourceName()];
        //====================================================================//
        // Do Generic Change Commit
        $this->commitChanges($objectType, SPL_A_DELETE, $object->getEntityId(), $comment);
    }

    /**
     * Ensure Splash Libraries are Loaded
     */
    private function ensureInit(): void
    {
        //====================================================================//
        // Splash Module Autoload Locations
        $autoloadLocations = array(
            dirname(dirname(__FILE__)).'/vendor/autoload.php',
            BP.'/app/code/local/SplashSync/Splash/vendor/autoload.php',
        );
        //====================================================================//
        // Load Splash Module
        foreach ($autoloadLocations as $autoload) {
            if (is_file($autoload)) {
                require_once($autoload);
                Splash::Core();

                return;
            }
        }
    }

    /**
     * Ensure Event is in Required Scope (Object action, Resources Filter)
     *
     * @return mixed Return Event Objects if Event to be treated
     */
    private function filterEvent(Varien_Event_Observer $observer)
    {
        //====================================================================//
        // Get Object From Event Class
        /** @phpstan-ignore-next-line */
        $object = $observer->getEvent()->getObject();
        if (is_null($object)) {
            return null;
        }
        //====================================================================//
        // Get Object Type from Class
        $resourceName = $object->getResourceName();
        if (is_null($resourceName)) {
            return null;
        }
        //====================================================================//
        // Filter Object Type
        if (!in_array($resourceName, $this->resourceFilter, true)) {
            return null;
        }

        return $object;
    }

    /**
     * Generic Splash Object Changes Commit Function
     *
     * @param string $objectType
     * @param string $action
     * @param mixed  $local
     * @param string $comment
     *
     * @throws Exception
     *
     * @return bool
     */
    private function commitChanges(string $objectType, string $action, $local, string $comment): bool
    {
        //====================================================================//
        // Complete Comment for Logging
        $comment .= " ".$action." on Magento 1";
        //====================================================================//
        // Prepare User Name for Logging
        /** @var \Mage_Admin_Model_Session $sessionModel */
        $sessionModel = Mage::getModel('admin/session');
        /** @phpstan-ignore-next-line */
        $adminUser = $sessionModel->getUser();
        if (!empty($adminUser)) {
            $user = $adminUser->getUsername();
        } else {
            $user = 'Unknown Employee';
        }
        //====================================================================//
        // Init Splash Module
        $this->ensureInit();
        //====================================================================//
        // Prevent Repeated Commit if Needed
        if ((SPL_A_UPDATE == $action) && Splash::object($objectType)->isLocked()) {
            return true;
        }
        //====================================================================//
        // Commit Action on remotes nodes (Master & Slaves)
        $result = Splash::commit($objectType, $local, $action, $user, $comment);
        //====================================================================//
        // Post Splash Messages
        $this->importLog(Splash::log());

        return $result;
    }

    /**
     * Import Splash Logs to User Session
     *
     * @param Logger $log
     */
    private function importLog($log): void
    {
        //====================================================================//
        // Import Errors
        if (isset($log->err) && !empty($log->err)) {
            $this->importMessages($log->err, "addError");
        }
        //====================================================================//
        // Import Warnings
        if (isset($log->war) && !empty($log->war)) {
            $this->importMessages($log->war, "addWarning");
        }
        //====================================================================//
        // Import Messages
        if (isset($log->msg) && !empty($log->msg)) {
            $this->importMessages($log->msg, "addSuccess");
        }
        //====================================================================//
        // Import Debug
        if (isset($log->deb) && !empty($log->deb)) {
            $this->importMessages($log->deb, "addSuccess");
        }
    }

    /**
     * @param array  $messagesArray
     * @param string $method
     */
    private function importMessages($messagesArray, $method): void
    {
        foreach ($messagesArray as $message) {
            Mage::getSingleton('adminhtml/session')->{$method}($message);
        }
    }
}
