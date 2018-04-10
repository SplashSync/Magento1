<?php
/*
 * Copyright (C) 2017   Splash Sync       <contact@splashsync.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
*/

namespace Splash\Local\Objects\ThirdParty;

use Splash\Core\SplashCore      as Splash;

// Magento Namespaces
use Mage;
use Mage_Customer_Exception;

/**
 * @abstract    Magento 1 Customers CRUD Functions
 */
trait CRUDTrait
{
    
    /**
     * @abstract    Load Request Object
     *
     * @param       array   $Id               Object id
     *
     * @return      mixed
     */
    public function Load($Id)
    {
        //====================================================================//
        // Stack Trace
        Splash::Log()->Trace(__CLASS__, __FUNCTION__);
        //====================================================================//
        // Init Object
        $Customer   =   Mage::getModel('customer/customer')->load($Id);
        if ($Customer->getEntityId() != $Id) {
            return Splash::Log()->Err("ErrLocalTpl", __CLASS__, __FUNCTION__, " Unable to load Customer (" . $Id . ").");
        }
        return $Customer;
    }
    
    /**
     * @abstract    Create Request Object
     *
     * @param       array   $List         Given Object Data
     *
     * @return      object     New Object
     */
    public function Create()
    {
        //====================================================================//
        // Stack Trace
        Splash::Log()->Trace(__CLASS__, __FUNCTION__);
        //====================================================================//
        // Check Required Fields
        if (empty($this->In["firstname"])) {
            return Splash::Log()->Err("ErrLocalFieldMissing", __CLASS__, __FUNCTION__, "firstname");
        }
        if (empty($this->In["lastname"])) {
            return Splash::Log()->Err("ErrLocalFieldMissing", __CLASS__, __FUNCTION__, "lastname");
        }
        if (empty($this->In["email"])) {
            return Splash::Log()->Err("ErrLocalFieldMissing", __CLASS__, __FUNCTION__, "email");
        }
        //====================================================================//
        // If No Origin Given => Select Default WebSite
        if (empty($this->In["splash_origin"])) {
            Mage::app()->setCurrentStore(Mage::getStoreConfig('splashsync_splash_options/thirdparty/store'));
        //====================================================================//
        // If Origin Given => Select Choosen Website
        } else {
            foreach (Mage::app()->getWebsites() as $website) {
                if ($this->In["splash_origin"] == $website->getConfig('splashsync_splash_options/thirdparty/origin')) {
                    Mage::app()->setCurrentStore($website->getDefaultStore()->getId());
                }
            }
        }
        //====================================================================//
        // Create Empty Customer
        $Customer   =   Mage::getModel('customer/customer');
        $Customer->setData("firstname", $this->In["firstname"]);
        $Customer->setData("lastname", $this->In["lastname"]);
        $Customer->setData("email", $this->In["email"]);
        //====================================================================//
        // Save Object
        try {
            $Customer->save();
        } catch (Mage_Customer_Exception $ex) {
            Splash::Log()->Deb($ex->getTraceAsString());
            return Splash::Log()->Err("ErrLocalTpl", __CLASS__, __FUNCTION__, $ex->getMessage());
        }
        return $Customer;
    }
    
    /**
     * @abstract    Update Request Object
     *
     * @param       array   $Needed         Is This Update Needed
     *
     * @return      string      Object Id
     */
    public function Update($Needed)
    {
        //====================================================================//
        // Stack Trace
        Splash::Log()->Trace(__CLASS__, __FUNCTION__);
        if (!$Needed) {
            return $this->Object->getEntityId();
        }
        //====================================================================//
        // Update Object
        try {
            $this->Object->save();
        } catch (Mage_Customer_Exception $ex) {
            Splash::Log()->Deb($ex->getTraceAsString());
            return Splash::Log()->Err("ErrLocalTpl", __CLASS__, __FUNCTION__, $ex->getMessage());
        }
        //====================================================================//
        // Ensure All changes have been saved
        if ($this->Object->_hasDataChanges) {
            return Splash::Log()->Err("ErrLocalTpl", __CLASS__, __FUNCTION__, "Unable to update (" . $this->Object->getEntityId() . ").");
        }
        return $this->Object->getEntityId();
    }
        
    /**
     * @abstract    Delete requested Object
     *
     * @param       int     $Id     Object Id.  If NULL, Object needs to be created.
     *
     * @return      bool
     */
    public function Delete($Id = null)
    {
        //====================================================================//
        // Stack Trace
        Splash::Log()->Trace(__CLASS__, __FUNCTION__);
        //====================================================================//
        // Execute Generic Magento Delete Function ...
        return Splash::Local()->ObjectDelete('customer/customer', $Id);
    }
}
