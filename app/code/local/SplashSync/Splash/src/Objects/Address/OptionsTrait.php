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

namespace Splash\Local\Objects\Address;

/**
 * @abstract    Magento 1 Customers Address Optionnal Fields Access
 */
trait OptionsTrait {
    
    /**
    *   @abstract     Build Address Optional Fields using FieldFactory
    */
    private function buildOptionalFields()
    {
        $ContactGroup   =    "Contacts";
        
        //====================================================================//
        // Phone
        $this->FieldsFactory()->Create(SPL_T_PHONE)
                ->Identifier("telephone")
                ->Name("Phone")
                ->Group($ContactGroup)
                ->MicroData("http://schema.org/PostalAddress","telephone");
        
        //====================================================================//
        // Fax
        $this->FieldsFactory()->Create(SPL_T_PHONE)
                ->Identifier("fax")
                ->Name("Fax")
                ->Group($ContactGroup)
                ->MicroData("http://schema.org/PostalAddress","faxNumber");

        //====================================================================//
        // VAT ID
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("vat_id")
                ->Name("VAT Number")
                ->MicroData("http://schema.org/Organization","vatID");
        
    }  
    
    
    /**
     *  @abstract     Read requested Field
     * 
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     * 
     *  @return         none
     */
    private function getOptionalFields($Key,$FieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($FieldName)
        {
            //====================================================================//
            // Direct Readings
            case 'telephone':
            case 'fax':
            case 'vat_id':
                $this->getData($FieldName);
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
    private function setOptionalFields($FieldName,$Data) 
    {
        //====================================================================//
        // WRITE Field
        switch ($FieldName)
        {
            //====================================================================//
            // Direct Readings
            case 'telephone':
            case 'fax':
            case 'vat_id':
                $this->setData($FieldName, $Data);
                break;
            default:
                return;            
        }
        unset($this->In[$FieldName]);
    }
    
}
