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

// Magento Namespaces
use Mage;

/**
 * @abstract    Magento 1 Customers Address Main Fields Access
 */
trait MainTrait
{

    /**
    *   @abstract     Build Address Main Fields using FieldFactory
    */
    private function buildMainFields()
    {
        
        $AddressGroup = "Address";
        
        //====================================================================//
        // Addess
        $this->fieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("street")
                ->Name("Address")
                ->MicroData("http://schema.org/PostalAddress", "streetAddress")
                ->Group($AddressGroup)
                ->isRequired();
        
        //====================================================================//
        // Zip Code
        $this->fieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("postcode")
                ->Name("Zip/Postal Code")
                ->MicroData("http://schema.org/PostalAddress", "postalCode")
                ->Group($AddressGroup)
                ->isRequired();
        
        //====================================================================//
        // City Name
        $this->fieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("city")
                ->Name("City")
                ->MicroData("http://schema.org/PostalAddress", "addressLocality")
                ->isRequired()
                ->Group($AddressGroup)
                ->isListed();
        
        //====================================================================//
        // State Name
        $this->fieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("region")
                ->Name("State")
                ->Group($AddressGroup)
                ->isReadOnly();
        
        //====================================================================//
        // State code
        $this->fieldsFactory()->Create(SPL_T_STATE)
                ->Identifier("region_id")
                ->Name("StateCode")
                ->MicroData("http://schema.org/PostalAddress", "addressRegion")
                ->Group($AddressGroup)
                ->isNotTested();
        
        //====================================================================//
        // Country Name
        $this->fieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("country")
                ->Name("Country")
                ->Group($AddressGroup)
                ->isReadOnly();
//                ->isListed();
        
        //====================================================================//
        // Country ISO Code
        $this->fieldsFactory()->Create(SPL_T_COUNTRY)
                ->Identifier("country_id")
                ->Name("CountryCode")
                ->MicroData("http://schema.org/PostalAddress", "addressCountry")
                ->Group($AddressGroup)
                ->isRequired();
    }


    /**
     *  @abstract     Read requested Field
     *
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     *
     *  @return         none
     */
    private function getMainFields($Key, $FieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($FieldName) {
            //====================================================================//
            // Direct Readings
            case 'street':
            case 'postcode':
            case 'city':
            case 'country_id':
            case 'region':
                $this->getData($FieldName);
                break;
            //====================================================================//
            // State ISO Id - READ With Convertion
            case 'region_id':
                //====================================================================//
                // READ With Convertion
                $this->Out[$FieldName] = Mage::getModel('directory/region')
                        ->load($this->Object->getData($FieldName))
                        ->getCode();
                break;
            //====================================================================//
            // Country Name - READ With Convertion
            case 'country':
                $this->Out[$FieldName] = Mage::getModel('directory/country')
                    ->load($this->Object->getData("country_id"))
                    ->getName();
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
    private function setMainFields($FieldName, $Data)
    {
        
        //====================================================================//
        // WRITE Field
        switch ($FieldName) {
            //====================================================================//
            // Direct Readings
            case 'street':
            case 'postcode':
            case 'city':
            case 'country_id':
                $this->setData($FieldName, $Data);
                break;

            //====================================================================//
            // State ISO Id - READ With Convertion
            case 'region_id':
                //====================================================================//
                // Get Country ISO Id - From Inputs or From Current Objects
                $CountryId  =   isset($this->In["country_id"])?$this->In["country_id"]:$this->Object->getData("country_id");
                $RegionId   =   Mage::getModel('directory/region')
                        ->loadByCode($Data, $CountryId)->getRegionId();
                if (( $RegionId ) && $this->Object->getData($FieldName)  != $RegionId) {
                    $this->Object->setData($FieldName, $RegionId);
                    $this->needUpdate();
                }
                break;
            default:
                return;
        }
        unset($this->In[$FieldName]);
    }
}
