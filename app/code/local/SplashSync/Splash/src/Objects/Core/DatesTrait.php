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

// Magento Namespaces
use Mage;

/**
 * @abstract    Magento 1 Object Dates Access
 */
trait DatesTrait
{
    

    /**
    *   @abstract     Build Fields using FieldFactory
    */
    private function buildDatesFields()
    {
        //====================================================================//
        // Creation Date
        $this->FieldsFactory()->Create(SPL_T_DATETIME)
                ->Identifier("created_at")
                ->Name("Registration")
                ->Group("Meta")
                ->MicroData("http://schema.org/DataFeedItem", "dateCreated")
                ->isReadOnly();
        
        //====================================================================//
        // Last Change Date
        $this->FieldsFactory()->Create(SPL_T_DATETIME)
                ->Identifier("updated_at")
                ->Name("Last update")
                ->Group("Meta")
                ->MicroData("http://schema.org/DataFeedItem", "dateModified")
                ->isReadOnly();
    }
    
    /**
     *  @abstract     Read requested Field
     *
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     *
     *  @return         none
     */
    private function getDatesFields($Key, $FieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($FieldName) {
            case 'created_at':
            case 'updated_at':
                $this->Out[$FieldName] = date(SPL_T_DATETIMECAST, Mage::getModel("core/date")->gmtTimestamp($this->Object->getData($FieldName)));
                break;
            default:
                return;
        }
        unset($this->In[$Key]);
    }
}
