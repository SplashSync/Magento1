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
 * Magento 1 Object Dates Access
 */
trait DatesTrait
{
    /**
     * Build Fields using FieldFactory
     */
    protected function buildDatesFields(): void
    {
        //====================================================================//
        // Creation Date
        $this->fieldsFactory()->Create(SPL_T_DATETIME)
            ->Identifier("created_at")
            ->Name("Registration")
            ->Group("Meta")
            ->MicroData("http://schema.org/DataFeedItem", "dateCreated")
            ->isReadOnly()
        ;
        //====================================================================//
        // Last Change Date
        $this->fieldsFactory()->Create(SPL_T_DATETIME)
            ->Identifier("updated_at")
            ->Name("Last update")
            ->Group("Meta")
            ->MicroData("http://schema.org/DataFeedItem", "dateModified")
            ->isReadOnly()
        ;
    }

    /**
     * Read requested Field
     *
     *  @param        string    $key                    Input List Key
     *  @param        string    $fieldName              Field Identifier / Name
     */
    protected function getDatesFields($key, $fieldName): void
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            case 'created_at':
            case 'updated_at':
                /** @var Mage_Core_Model_Date $model */
                $model = Mage::getModel('core/date');
                $this->out[$fieldName] = date(
                    SPL_T_DATETIMECAST,
                    $model->gmtTimestamp($this->object->getData($fieldName))
                );

                break;
            default:
                return;
        }
        unset($this->in[$key]);
    }
}
