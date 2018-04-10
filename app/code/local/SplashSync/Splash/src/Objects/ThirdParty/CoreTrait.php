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
 * @abstract    Magento 1 Customers Core Fields Access
 */
trait CoreTrait
{
    
    /**
    *   @abstract     Build Customers Core Fields using FieldFactory
    */
    private function buildCoreFields()
    {
        //====================================================================//
        // Firstname
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("firstname")
                ->Name("First name")
                ->MicroData("http://schema.org/Person", "familyName")
                ->Association("firstname", "lastname")
                ->isRequired()
                ->isListed();
        
        //====================================================================//
        // Lastname
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("lastname")
                ->Name("Last name")
                ->MicroData("http://schema.org/Person", "givenName")
                ->Association("firstname", "lastname")
                ->isRequired()
                ->isListed();

        //====================================================================//
        // Email
        $this->FieldsFactory()->Create(SPL_T_EMAIL)
                ->Identifier("email")
                ->Name("Email address")
                ->MicroData("http://schema.org/ContactPoint", "email")
                ->Association("firstname", "lastname")
                ->isRequired()
                ->isListed();
    }
    
    /**
     *  @abstract     Read requested Field
     *
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     *
     *  @return         none
     */
    private function getCoreFields($Key, $FieldName)
    {
        //====================================================================//
        // READ Field
        switch ($FieldName) {
            case 'lastname':
            case 'firstname':
            case 'email':
                $this->Out[$FieldName] = $this->Object->getData($FieldName);
                break;
            default:
                return;
        }
        unset($this->In[$Key]);
    }
    
    /**
     *  @abstract     Write Given Fields
     *
     *  @param        string    $FieldName              Field Identifier / Name
     *  @param        mixed     $Data                   Field Data
     *
     *  @return         none
     */
    private function setCoreFields($FieldName, $Data)
    {
        //====================================================================//
        // WRITE Fields
        switch ($FieldName) {
            case 'firstname':
            case 'lastname':
            case 'email':
                if ($this->Object->getData($FieldName) != $Data) {
                    $this->Object->setData($FieldName, $Data);
                    $this->needUpdate();
                }
                unset($this->In[$FieldName]);
                break;
        }
    }
}
