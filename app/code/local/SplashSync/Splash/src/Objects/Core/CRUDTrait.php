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

namespace Splash\Local\Objects\Core;

use Splash\Core\SplashCore      as Splash;

// Magento Namespaces
use Mage;

/**
 * @abstract    Magento 1 Core CRUD Functions
 */
trait CRUDTrait
{

    /**
    *   @abstract   Generic Delete of requested Object
    *   @param      string      $Type           Object Magento Type
    *   @param      int         $Id             Object Id.  If NULL, Object needs to be created.
    *   @return     int                         0 if KO, >0 if OK
    */
    public function CoreDelete($Type, $Id = null)
    {
        //====================================================================//
        // Stack Trace
        Splash::Log()->Trace(__CLASS__, __FUNCTION__);
        //====================================================================//
        // Safety Checks
        if (empty($Id)) {
            return Splash::Log()->Err("ErrSchNoObjectId", __CLASS__."::".__FUNCTION__);
        }
        //====================================================================//
        // Initialize Remote Admin user ...
        if (!Splash::Local()->LoadLocalUser()) {
            return true;
        }
        //====================================================================//
        // Load Object From DataBase
        //====================================================================//
        $Object = Mage::getModel($Type)->load($Id);
        if ($Object->getEntityId() != $Id) {
            return Splash::Log()->War("ErrLocalTpl", __CLASS__, __FUNCTION__, "Unable to load (" . $Id . ").");
        }
        //====================================================================//
        // Delete Object From DataBase
        //====================================================================//
        $Object->delete();
        return true;
    }
    
    /**
     * @abstract    Update Request Object
     *
     * @param       array   $Needed         Is This Update Needed
     *
     * @return      string      Object Id
     */
    public function CoreUpdate($Needed)
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
}
