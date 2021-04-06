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
use Mage_Customer_Model_Customer;
use Splash\Core\SplashCore      as Splash;

/**
 * Magento 1 Customers Address Core Fields Access
 */
trait CoreTrait
{
    /**
     * Build Address Core Fields using FieldFactory
     */
    protected function buildCoreFields(): void
    {
        //====================================================================//
        // Customer
        $this->fieldsFactory()->Create((string) self::objects()->encode("ThirdParty", SPL_T_ID))
            ->Identifier("parent_id")
            ->Name("Customer")
            ->MicroData("http://schema.org/Organization", "ID")
            ->isRequired()
        ;
        //====================================================================//
        // Company
        $this->fieldsFactory()->Create(SPL_T_VARCHAR)
            ->Identifier("company")
            ->Name("Company")
            ->MicroData("http://schema.org/Organization", "legalName")
            ->isListed()
        ;
        //====================================================================//
        // Firstname
        $this->fieldsFactory()->Create(SPL_T_VARCHAR)
            ->Identifier("firstname")
            ->Name("First name")
            ->MicroData("http://schema.org/Person", "familyName")
            ->Association("firstname", "lastname")
            ->isRequired()
            ->isListed()
        ;
        //====================================================================//
        // Lastname
        $this->fieldsFactory()->Create(SPL_T_VARCHAR)
            ->Identifier("lastname")
            ->Name("Last name")
            ->MicroData("http://schema.org/Person", "givenName")
            ->Association("firstname", "lastname")
            ->isRequired()
            ->isListed()
        ;
        //====================================================================//
        // Prefix
        $this->fieldsFactory()->Create(SPL_T_VARCHAR)
            ->Identifier("prefix")
            ->Name("Prefix name")
            ->MicroData("http://schema.org/Person", "honorificPrefix")
        ;
        //====================================================================//
        // MiddleName
        $this->fieldsFactory()->Create(SPL_T_VARCHAR)
            ->Identifier("middlename")
            ->Name("Middlename")
            ->MicroData("http://schema.org/Person", "additionalName")
        ;
        //====================================================================//
        // Suffix
        $this->fieldsFactory()->Create(SPL_T_VARCHAR)
            ->Identifier("suffix")
            ->Name("Suffix name")
            ->MicroData("http://schema.org/Person", "honorificSuffix")
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
    protected function getCoreFields($key, $fieldName): void
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // Direct Readings
            case 'company':
            case 'firstname':
            case 'middlename':
            case 'lastname':
            case 'prefix':
            case 'suffix':
                $this->getData($fieldName);

                break;
            //====================================================================//
            // Customer Object Id Readings
            case 'parent_id':
                $this->out[$fieldName] = self::objects()->encode("ThirdParty", $this->object->getParentId());

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
    protected function setCoreFields(string $fieldName, $data)
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            //====================================================================//
            // Direct Writings
            case 'company':
            case 'firstname':
            case 'middlename':
            case 'lastname':
            case 'prefix':
            case 'suffix':
                $this->setData($fieldName, $data);

                break;
            //====================================================================//
            // Customer Object Id Writings
            case 'parent_id':
                $this->setParentId($data);

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }

    /**
     * Write Given Fields
     *
     * @param mixed $data
     *
     * @return bool
     */
    private function setParentId($data): bool
    {
        //====================================================================//
        // Decode Customer Id
        $customerId = self::objects()->id($data);
        //====================================================================//
        // Check For Change
        if ($customerId == $this->object->getParentId()) {
            return true;
        }
        //====================================================================//
        // Verify Object Type
        if ("ThirdParty" !== self::objects()->type($data)) {
            return Splash::log()->errTrace("Wrong Object Type (".self::objects()->Type($data).").");
        }
        //====================================================================//
        // Verify Object Exists
        /** @var Mage_Customer_Model_Customer $model */
        $model = Mage::getModel('customer/customer');
        $customer = $model->load((int) $customerId);
        if ($customer->getEntityId() != $customerId) {
            return Splash::log()->errTrace("Unable to load Address Customer(".$customerId.").");
        }
        //====================================================================//
        // Update Link
        $this->object->setParentId($customerId);

        return true;
    }
}
