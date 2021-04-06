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

namespace Splash\Local\Objects\Core;

/**
 * Magento 1 Object SplashId Access
 */
trait SplashIdTrait
{
    /**
     * Build Fields using FieldFactory
     */
    protected function buildSplashIdFields(): void
    {
        //====================================================================//
        // Splash Unique Object Id
        $this->fieldsFactory()->Create(SPL_T_VARCHAR)
            ->Identifier("splash_id")
            ->Name("Splash Id")
            ->Group("Meta")
            ->MicroData("http://splashync.com/schemas", "ObjectId")
            ->isNotTested()
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
    protected function getSplashIdFields($key, $fieldName): void
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            case 'splash_id':
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
    private function setSplashIdFields($fieldName, $data)
    {
        //====================================================================//
        // WRITE Fields
        switch ($fieldName) {
            //====================================================================//
            // Splash Meta Data
            case 'splash_id':
                $this->setData($fieldName, $data);

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }
}
