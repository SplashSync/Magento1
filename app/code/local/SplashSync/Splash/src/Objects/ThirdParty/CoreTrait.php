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

namespace Splash\Local\Objects\ThirdParty;

/**
 * Magento 1 Customers Core Fields Access
 */
trait CoreTrait
{
    /**
     * Build Customers Core Fields using FieldFactory
     */
    protected function buildCoreFields(): void
    {
        //====================================================================//
        // Firstname
        $this->fieldsFactory()->Create(SPL_T_VARCHAR)
            ->Identifier("firstname")
            ->Name("First name")
            ->MicroData("http://schema.org/Person", "familyName")
            ->Association("firstname", "lastname")
            ->isRequired()
            ->isListed();

        //====================================================================//
        // Lastname
        $this->fieldsFactory()->Create(SPL_T_VARCHAR)
            ->Identifier("lastname")
            ->Name("Last name")
            ->MicroData("http://schema.org/Person", "givenName")
            ->Association("firstname", "lastname")
            ->isRequired()
            ->isListed();

        //====================================================================//
        // Email
        $this->fieldsFactory()->Create(SPL_T_EMAIL)
            ->Identifier("email")
            ->Name("Email address")
            ->MicroData("http://schema.org/ContactPoint", "email")
            ->Association("firstname", "lastname")
            ->isRequired()
            ->isListed();
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    protected function getCoreFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // READ Field
        switch ($fieldName) {
            case 'lastname':
            case 'firstname':
            case 'email':
                $this->out[$fieldName] = $this->object->getData($fieldName);

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
    protected function setCoreFields(string $fieldName, $data): void
    {
        //====================================================================//
        // WRITE Fields
        switch ($fieldName) {
            case 'firstname':
            case 'lastname':
            case 'email':
                if ($this->object->getData($fieldName) != $data) {
                    $this->object->setData($fieldName, $data);
                    $this->needUpdate();
                }
                unset($this->in[$fieldName]);

                break;
        }
    }
}
