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

use Mage;
use Mage_Core_Model_Store;
use Mage_Core_Model_Website;

/**
 * Magento 1 Object SplashOrigin Access
 */
trait SplashOriginTrait
{
    /**
     * Detect Creation Website with Origin Id Given by Splash
     *
     * @return Mage_Core_Model_Website
     */
    protected function getSplashOriginWebsite()
    {
        //====================================================================//
        // If Origin Given => Select Website
        if (isset($this->in["splash_origin"]) && !empty($this->in["splash_origin"])) {
            foreach (Mage::app()->getWebsites() as $website) {
                if ($this->in["splash_origin"] == $website->getConfig('splashsync_splash_options/advanced/origin')) {
                    return $website;
                }
            }
        }
        //====================================================================//
        // If No Origin Given => Select Default WebSite
        return Mage::app()->getWebsite(
            Mage::getStoreConfig('splashsync_splash_options/advanced/website')
        );
    }

    /**
     * Detect Creation Websites Ids Array with Origin Id Given by Splash
     *
     * @return array
     */
    protected function getSplashOriginWebsiteIds(): array
    {
        return array($this->getSplashOriginWebsite()->getId());
    }

    /**
     * Detect Creation Store with Origin Id Given by Splash
     *
     * @return Mage_Core_Model_Store,
     */
    protected function getSplashOriginStore()
    {
        return $this->getSplashOriginWebsite()->getDefaultStore();
    }

    /**
     * Build Fields using FieldFactory
     */
    protected function buildSplashOriginFields(): void
    {
        //====================================================================//
        // Splash Object SOrigin Node Id
        $this->fieldsFactory()->Create(SPL_T_VARCHAR)
            ->Identifier("splash_origin")
            ->Name("Splash Origin Node")
            ->Group("Meta")
            ->MicroData("http://splashync.com/schemas", "SourceNodeId");
    }

    /**
     * Read requested Field
     *
     * @param string $key Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    protected function getSplashOriginFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            case 'splash_origin':
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
     * @param mixed $data Field Data
     *
     * @return void
     */
    protected function setSplashOriginFields(string $fieldName, $data)
    {
        //====================================================================//
        // WRITE Fields
        switch ($fieldName) {
            case 'splash_origin':
                $this->setData($fieldName, $data);

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }
}
