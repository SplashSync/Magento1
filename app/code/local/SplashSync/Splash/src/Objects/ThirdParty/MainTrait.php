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

use Mage;
use Mage_Core_Model_Date;
use Splash\Core\SplashCore      as Splash;

/**
 * @abstract    Magento 1 Customers Main Fields Access
 */
trait MainTrait
{
    /**
     * Build Customers Main Fields using FieldFactory
     *
     * @return void
     */
    protected function buildMainFields(): void
    {
        //====================================================================//
        // Gender Name
        $this->fieldsFactory()->Create(SPL_T_VARCHAR)
            ->Identifier("gender_name")
            ->Name("Social title")
            ->MicroData("http://schema.org/Person", "honorificPrefix")
            ->isReadOnly();

        //====================================================================//
        // Gender Type
        $desc = "Social title"." ; 0 => Male // 1 => Female // 2 => Neutral";
        $this->fieldsFactory()->Create(SPL_T_INT)
            ->Identifier("gender")
            ->Name("Social title")
            ->MicroData("http://schema.org/Person", "gender")
            ->Description($desc)
            ->AddChoice("0", "Male")
            ->AddChoice("1", "Female")
            ->isNotTested();

        //====================================================================//
        // Date Of Birth
        $this->fieldsFactory()->Create(SPL_T_DATE)
            ->Identifier("dob")
            ->Name("Date of birth")
            ->MicroData("http://schema.org/Person", "birthDate");

        //====================================================================//
        // Company
        $this->fieldsFactory()->Create(SPL_T_VARCHAR)
            ->Identifier("company")
            ->Name("Company")
            ->MicroData("http://schema.org/Organization", "legalName")
            ->isReadOnly();

        //====================================================================//
        // Prefix
        $this->fieldsFactory()->Create(SPL_T_VARCHAR)
            ->Identifier("prefix")
            ->Name("Prefix name")
            ->MicroData("http://schema.org/Person", "honorificPrefix");

        //====================================================================//
        // MiddleName
        $this->fieldsFactory()->Create(SPL_T_VARCHAR)
            ->Identifier("middlename")
            ->Name("Middlename")
            ->MicroData("http://schema.org/Person", "additionalName");

        //====================================================================//
        // Suffix
        $this->fieldsFactory()->Create(SPL_T_VARCHAR)
            ->Identifier("suffix")
            ->Name("Suffix name")
            ->MicroData("http://schema.org/Person", "honorificSuffix");

//        //====================================================================//
//        // Address List
//        $this->fieldsFactory()->Create(self::ObjectId_Encode( "Address" , SPL_T_ID))
//                ->Identifier("address")
//                ->InList("contacts")
//                ->Name($this->spl->l("Address"))
//                ->MicroData("http://schema.org/Organization","address")
//                ->isReadOnly();
    }

    /**
     * Read requested Field
     *
     * @param string $key Input List Key
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
            // Customer Company Override by User Id
            case 'company':
                $this->out[$fieldName] = $this->getCompany();

                break;
            //====================================================================//
            // Gender Name
            case 'gender_name':
                $this->out[$fieldName] = $this->getGenderName();

                break;
            //====================================================================//
            // Gender Type
            case 'gender':
                $this->out[$fieldName] = (2 == $this->object->getData($fieldName)) ? 1 : 0 ;

                break;
            //====================================================================//
            // Customer Date Of Birth
            case 'dob':
                /** @var Mage_Core_Model_Date $model */
                $model = Mage::getModel('core/date');
                $this->out[$fieldName] = date(
                    SPL_T_DATECAST,
                    $model->timestamp($this->object->getData($fieldName))
                );

                break;
            //====================================================================//
            // Customer Extended Names
            case 'prefix':
            case 'middlename':
            case 'suffix':
                $this->out[$fieldName] = $this->object->getData($fieldName);

                break;
//            //====================================================================//
//            // Customer Address List
//            case 'address@contacts':
//                if ( !$this->getAddressList() ) {
//                   return;
//                }
//                break;
            default:
                return;
        }
        unset($this->in[$key]);
    }

    /**
     * Write Given Fields
     *
     * @param string $fieldName Field Identifier / Name
     * @param mixed $data Field Data
     *
     * @return void
     */
    protected function setMainFields(string $fieldName, $data): void
    {
        //====================================================================//
        // WRITE Fields
        switch ($fieldName) {
            //====================================================================//
            // Gender Type
            case 'gender':
                //====================================================================//
                // Convert Gender Type Value to Magento Values
                // Splash Social title ; 0 => Male // 1 => Female // 2 => Neutral
                // Magento Social title ; 1 => Male // 2 => Female
                $data++;
                //====================================================================//
                // Update Gender Type
                $this->setData($fieldName, $data);

                break;
            //====================================================================//
            // Customer Date Of Birth
            case 'dob':
                /** @var Mage_Core_Model_Date $model */
                $model = Mage::getModel('core/date');
                $currentDob = date(
                    SPL_T_DATECAST,
                    $model->timestamp($this->object->getData($fieldName))
                );
                if ($currentDob != $data) {
                    $this->object->setData($fieldName, $model->gmtDate(null, $data));
                    $this->needUpdate();
                }

                break;
            //====================================================================//
            // Customer Extended Names
            case 'prefix':
            case 'middlename':
            case 'suffix':
                $this->setData($fieldName, $data);

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }

    /**
     * Read Customer Company Name
     *
     * @return string
     */
    private function getCompany(): string
    {
        if (!empty($this->object->getData('company'))) {
            return $this->object->getData('company');
        }

        return "Magento1(".$this->object->getEntityId().")";
    }

    /**
     * Read Customer Gender Name
     *
     * @return string
     */
    private function getGenderName(): string
    {
        if (empty($this->object->getData("gender"))) {
            Splash::trans("Empty");
        }
        if (2 == $this->object->getData("gender")) {
            return "Femele";
        }

        return "Male";
    }
}
