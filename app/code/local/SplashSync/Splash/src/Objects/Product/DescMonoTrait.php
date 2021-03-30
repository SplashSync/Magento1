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

namespace Splash\Local\Objects\Product;

use Splash\Core\SplashCore      as Splash;

/**
 * Magento 1 Monolang Products Descriptions Fields Access
 */
trait DescMonoTrait
{
    /**
     * Build Description Fields using FieldFactory
     *
     * @return void
     */
    protected function buildDescMonoFields()
    {
        //====================================================================//
        // Name without Options
        $this->fieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("s_name")
                ->Name("[S] Product Name without Options")
                ->Group("Description")
                ->MicroData("http://schema.org/Product", "alternateName")
                ->isReadOnly();

        //====================================================================//
        // Long Description
        $this->fieldsFactory()->Create(SPL_T_TEXT)
                ->Identifier("s_description")
                ->Name("[S] Description")
                ->Group("Description")
                ->MicroData("http://schema.org/Article", "articleBody")
                ->isReadOnly();

        //====================================================================//
        // Short Description
        $this->fieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("s_short_description")
                ->Name("[S] Short Description")
                ->Group("Description")
                ->MicroData("http://schema.org/Product", "description")
                ->isReadOnly();
    }

    /**
     *  Read requested Field
     *
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     *
     *  @return void
     */
    protected function getDescMonoFields($Key, $FieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($FieldName) {
            //====================================================================//
            // PRODUCT MONO LANG CONTENTS
            //====================================================================//
            case 's_name':
            case 's_description':
            case 's_short_description':
                $this->Out[$FieldName] = (string) $this->Object->getData(str_replace("s_", "", $FieldName));

                break;
                
            default:
                return;
        }
        
        unset($this->In[$Key]);
    }
}
