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

namespace Splash\Local\Objects\Address;

use Mage;
use Mage_Directory_Model_Country;
use Mage_Directory_Model_Region;

/**
 * Magento 1 Customers Address Main Fields Access
 */
trait MainTrait
{
    /**
     * Build Address Main Fields using FieldFactory
     */
    protected function buildMainFields(): void
    {
        $addressGroup = "Address";

        //====================================================================//
        // Address
        $this->fieldsFactory()->Create(SPL_T_VARCHAR)
            ->Identifier("street")
            ->Name("Address")
            ->MicroData("http://schema.org/PostalAddress", "streetAddress")
            ->Group($addressGroup)
            ->isRequired()
        ;
        //====================================================================//
        // Zip Code
        $this->fieldsFactory()->Create(SPL_T_VARCHAR)
            ->Identifier("postcode")
            ->Name("Zip/Postal Code")
            ->MicroData("http://schema.org/PostalAddress", "postalCode")
            ->Group($addressGroup)
            ->isRequired()
        ;
        //====================================================================//
        // City Name
        $this->fieldsFactory()->Create(SPL_T_VARCHAR)
            ->Identifier("city")
            ->Name("City")
            ->MicroData("http://schema.org/PostalAddress", "addressLocality")
            ->isRequired()
            ->Group($addressGroup)
            ->isListed()
        ;
        //====================================================================//
        // State Name
        $this->fieldsFactory()->Create(SPL_T_VARCHAR)
            ->Identifier("region")
            ->Name("State")
            ->Group($addressGroup)
            ->isReadOnly()
        ;
        //====================================================================//
        // State code
        $this->fieldsFactory()->Create(SPL_T_STATE)
            ->Identifier("region_id")
            ->Name("StateCode")
            ->MicroData("http://schema.org/PostalAddress", "addressRegion")
            ->Group($addressGroup)
            ->isNotTested()
        ;
        //====================================================================//
        // Country Name
        $this->fieldsFactory()->Create(SPL_T_VARCHAR)
            ->Identifier("country")
            ->Name("Country")
            ->Group($addressGroup)
            ->isReadOnly()
        ;
        //====================================================================//
        // Country ISO Code
        $this->fieldsFactory()->Create(SPL_T_COUNTRY)
            ->Identifier("country_id")
            ->Name("CountryCode")
            ->MicroData("http://schema.org/PostalAddress", "addressCountry")
            ->Group($addressGroup)
            ->isRequired()
        ;
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    protected function getMainFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // Direct Readings
            case 'street':
            case 'postcode':
            case 'city':
            case 'country_id':
            case 'region':
                $this->getData($fieldName);

                break;
            default:
                return;
        }
        unset($this->in[$key]);
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    protected function getMainIntlFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // State ISO Id - READ With Conversion
            case 'region_id':
                /** @var Mage_Directory_Model_Region $model */
                $model = Mage::getModel('directory/region');
                /** @var false|Mage_Directory_Model_Region $region */
                $region = $model->load($this->object->getData($fieldName));
                $this->out[$fieldName] = $region ? $region->getCode() : "";

                break;
            //====================================================================//
            // Country Name - READ With Conversion
            case 'country':
                /** @var Mage_Directory_Model_Country $model */
                $model = Mage::getModel('directory/country');
                /** @var false|Mage_Directory_Model_Country $country */
                $country = $model->load($this->object->getData("country_id"));
                $this->out[$fieldName] = $country ? $country->getName() : "";

                break;
            default:
                return;
        }
        unset($this->in[$key]);
    }

    /**
     * Write Given Fields
     *
     * @param string $fieldName Field Identifier / Name
     * @param mixed  $data      Field Data
     *
     * @return void
     */
    protected function setMainFields(string $fieldName, $data): void
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            //====================================================================//
            // Direct Readings
            case 'street':
            case 'postcode':
            case 'city':
            case 'country_id':
                $this->setData($fieldName, $data);

                break;
            //====================================================================//
            // State ISO Id - READ With Conversion
            case 'region_id':
                //====================================================================//
                // Get Country ISO Id - From Inputs or From Current Objects
                $countryId = isset($this->in["country_id"])
                    ? $this->in["country_id"]
                    : $this->object->getData("country_id")
                ;
                /** @var Mage_Directory_Model_Region $model */
                $model = Mage::getModel('directory/region');
                $regionId = $model->loadByCode($data, $countryId)->getRegionId();
                if (($regionId) && $this->object->getData($fieldName) != $regionId) {
                    $this->object->setData($fieldName, $regionId);
                    $this->needUpdate();
                }

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }
}
