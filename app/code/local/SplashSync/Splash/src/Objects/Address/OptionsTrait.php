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

/**
 * Magento 1 Customers Address Optional Fields Access
 */
trait OptionsTrait
{
    /**
     * Build Address Optional Fields using FieldFactory
     */
    protected function buildOptionalFields(): void
    {
        $contactGroup = "Contacts";
        //====================================================================//
        // Phone
        $this->fieldsFactory()->Create(SPL_T_PHONE)
            ->Identifier("telephone")
            ->Name("Phone")
            ->Group($contactGroup)
            ->MicroData("http://schema.org/PostalAddress", "telephone")
        ;
        //====================================================================//
        // Fax
        $this->fieldsFactory()->Create(SPL_T_PHONE)
            ->Identifier("fax")
            ->Name("Fax")
            ->Group($contactGroup)
            ->MicroData("http://schema.org/PostalAddress", "faxNumber")
        ;
        //====================================================================//
        // VAT ID
        $this->fieldsFactory()->Create(SPL_T_VARCHAR)
            ->Identifier("vat_id")
            ->Name("VAT Number")
            ->MicroData("http://schema.org/Organization", "vatID")
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
    protected function getOptionalFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // Direct Readings
            case 'telephone':
            case 'fax':
            case 'vat_id':
                $this->getData($fieldName);

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
    protected function setOptionalFields(string $fieldName, $data)
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            //====================================================================//
            // Direct Readings
            case 'telephone':
            case 'fax':
            case 'vat_id':
                $this->setData($fieldName, $data);

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }
}
