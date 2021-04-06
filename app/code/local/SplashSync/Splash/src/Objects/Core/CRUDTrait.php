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

namespace Splash\Local\Objects\Core;

use Mage;
use Mage_Core_Exception;
use Splash\Core\SplashCore      as Splash;
use Splash\Local\Local;

/**
 * Magento 1 Core CRUD Functions
 */
trait CRUDTrait
{
    /**
     * Generic Delete of requested Object
     *
     * @param string $model    Object Magento Type
     * @param int    $objectId Object Id.  If NULL, Object needs to be created.
     *
     * @return bool
     */
    public function coreDelete(string $model, $objectId = null)
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace();
        //====================================================================//
        // Safety Checks
        if (empty($objectId)) {
            return Splash::log()->err("ErrSchNoObjectId", __CLASS__."::".__FUNCTION__);
        }
        //====================================================================//
        // Initialize Remote Admin user ...
        /** @var Local $local */
        $local = Splash::local();
        if (!$local->loadLocalUser()) {
            return true;
        }
        //====================================================================//
        // Load Object From DataBase
        //====================================================================//
        $mageModel = Mage::getModel($model);
        if (!$mageModel) {
            return Splash::log()->warTrace("Unable find Mage Model ".$model.".");
        }
        $object = $mageModel->load($objectId);
        if ($object->getEntityId() != $objectId) {
            return Splash::log()->warTrace("Unable to load (".$objectId.").");
        }
        //====================================================================//
        // Delete Object From DataBase
        //====================================================================//
        $object->delete();

        return true;
    }

    /**
     * @return false|string
     */
    public function getObjectIdentifier()
    {
        if (!$this->object) {
            return false;
        }

        return $this->object->getEntityId();
    }

    /**
     * Update Request Object
     *
     * @param bool $needed Is This Update Needed
     *
     * @return false|string Object Id
     */
    protected function coreUpdate($needed)
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace();
        if (!$needed) {
            return $this->object->getEntityId();
        }
        //====================================================================//
        // Update Object
        try {
            $this->object->save();
        } catch (Mage_Core_Exception $ex) {
            return Splash::log()->report($ex);
        }
        //====================================================================//
        // Ensure All changes have been saved
        if ($this->object->_hasDataChanges) {
            return Splash::log()->errTrace("Unable to update (".$this->object->getEntityId().").");
        }

        return $this->object->getEntityId();
    }
}
