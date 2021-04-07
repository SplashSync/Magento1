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
use Mage_Core_Model_Date;

/**
 * Magento 1 Generic Data Access
 */
trait DataAccessTrait
{
    /**
     * Generic Read of A Field
     *
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    protected function getData($fieldName)
    {
        $this->out[$fieldName] = $this->object->getData($fieldName);
    }

    /**
     * Generic Date Read of A Field
     *
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    protected function getDate($fieldName)
    {
        $this->out[$fieldName] = date(
            SPL_T_DATETIMECAST,
            $this->getMageDate()->gmtTimestamp($this->object->getData($fieldName))
        );
    }

    /**
     * Generic Date Read of A Field
     *
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    protected function getDateTime($fieldName)
    {
        $this->out[$fieldName] = date(
            SPL_T_DATECAST,
            $this->getMageDate()->gmtTimestamp($this->object->getData($fieldName))
        );
    }

    /**
     * Generic Write of Given Fields
     *
     * @param string $fieldName Field Identifier / Name
     * @param mixed  $data      Field Data
     *
     * @return void
     */
    protected function setData($fieldName, $data)
    {
        if ($this->object->getData($fieldName) != $data) {
            $this->object->setData($fieldName, $data);
            $this->needUpdate();
        }
    }

    /**
     * Get Magento Date Model
     *
     * @return Mage_Core_Model_Date
     */
    private function getMageDate(): Mage_Core_Model_Date
    {
        /** @var null|Mage_Core_Model_Date $mageDate */
        static $mageDate;
        if (!isset($mageDate)) {
            /** @var Mage_Core_Model_Date $mageDate */
            $mageDate = Mage::getModel('core/date');
        }

        return $mageDate;
    }
}
