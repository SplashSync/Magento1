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
use Splash\Core\SplashCore      as Splash;

/**
 * Magento 1 Multi-language Functions
 */
trait MultiLangTrait
{
    //====================================================================//
    // Structural Methods
    //====================================================================//

    /**
     * Get User Default language
     *
     * @return string
     */
    protected static function getDefaultLanguage(): string
    {
        /** @var null|string $dfLang */
        static $dfLang;
        //====================================================================//
        // Load Configuration
        if (!isset($dfLang)) {
            $dfLang = Mage::getStoreConfig('splashsync_splash_options/langs/default_lang');
            $dfLang = $dfLang ?: "en_US";
        }

        return $dfLang;
    }

    /**
     * Get All Available Languages
     *
     * @return array
     */
    protected function getAvailableLanguages(): array
    {
        /** @var null|string[] $languages */
        static $languages;
        //====================================================================//
        // Load Configuration
        if (!isset($languages)) {
            $languages = array();
            //====================================================================//
            // Multi-Lang Mode is Disabled
            if (!self::isMultiLang()) {
                $languages[$this->getSplashOriginStore()->getId()] = $this->getDefaultLanguage();
            }
            //====================================================================//
            // Multi-Lang Mode is Enabled
            if (self::isMultiLang()) {
                foreach ($this->getSplashOriginWebsite()->getStores() as $store) {
                    $languages[$store->getId()] = $this->getStoreLanguage($store->getId());
                }
            }
        }

        return $languages;
    }

    /**
     * Decode Multi-lang FieldName with ISO Code
     *
     * @param string $fieldName Complete Field Name
     * @param string $isoCode   Language Code in Splash Format
     *
     * @return string Base Field Name or Empty String
     */
    protected static function fieldNameDecode($fieldName, $isoCode)
    {
        //====================================================================//
        // Default Language => No code in FieldName
        if (self::getDefaultLanguage() == $isoCode) {
            return $fieldName;
        }
        //====================================================================//
        // Other Languages => Check if Code is in FieldName
        if (false === strpos($fieldName, $isoCode)) {
            return "";
        }

        return substr($fieldName, 0, strlen($fieldName) - strlen($isoCode) - 1);
    }

    //====================================================================//
    // Multi-lang Access Methods
    //====================================================================//

    /**
     * Get Store Specific Data
     *
     * @param string $key     Id of a Multi-lingual Contents
     * @param int    $storeId Magento Store Id
     *
     * @return null|string
     */
    protected function getMultiLangData(int $storeId, string $key)
    {
        //====================================================================//
        // This is default Store
        if (self::isDefaultStore($storeId)) {
            return $this->object->getData($key);
        }
        //====================================================================//
        // Other Stores
        /** @phpstan-ignore-next-line */
        return Mage::getResourceModel($this->object->getResourceName())
            ->getAttributeRawValue($this->object->getEntityId(), $key, $storeId);
    }

    /**
     * Set Store Specific Data
     *
     * @param int    $storeId Magento Store Id
     * @param string $key     Id of a Multi-lingual Contents
     * @param string $data    New Multi-lingual Content
     *
     * @return bool
     */
    protected function setMultiLangData(int $storeId, string $key, string $data): bool
    {
        //====================================================================//
        // Compare Data
        if ($this->getMultiLangData($storeId, $key) == $data) {
            return false;
        }
        //====================================================================//
        // Load Object Resource
        $resourceName = $this->object->getResourceName()."_action";
        $resource = Mage::getSingleton($resourceName);
        if (empty($resource)) {
            return Splash::log()->warTrace("Unable to load Object Resources (".$resourceName.").");
        }
        //====================================================================//
        // Update Data
        /** @phpstan-ignore-next-line */
        $resource->updateAttributes(
            array($this->object->getEntityId()),
            array($key => $data),
            $storeId
        );
        //====================================================================//
        // Update Default Data
        if ($this->isDefaultStore($storeId)) {
            $this->object->setData($key, $data);
        }

        return false;
    }

    //====================================================================//
    // Private Methods
    //====================================================================//

    /**
     * Is Multi-lingual Mode Active?
     *
     * @return bool
     */
    private static function isMultiLang(): bool
    {
        /** @var null|bool $isMultiLang */
        static $isMultiLang;
        //====================================================================//
        // Load Configuration
        if (!isset($isMultiLang)) {
            $isMultiLang = !empty(Mage::getStoreConfig('splashsync_splash_options/langs/multilang'));
        }
        //====================================================================//
        // Return Mode
        return $isMultiLang;
    }

    /**
     * Is Default Store
     *
     * @param int $storeId Magento Store Id
     *
     * @return bool
     */
    private static function isDefaultStore(int $storeId)
    {
        /** @var null|array<int, bool> $dfStores */
        static $dfStores;
        //====================================================================//
        // Init Configuration
        if (!isset($dfStores)) {
            $dfStores = array();
        }
        //====================================================================//
        // Init Value
        if (!isset($dfStores[$storeId])) {
            $dfStore = Mage::app()->getStore($storeId)->getWebsite()->getDefaultStore()->getId();
            $dfStores[$storeId] = ($storeId == $dfStore);
        }

        return $dfStores[$storeId];
    }

    /**
     * Get Store language
     *
     * @param int $storeId Magento Store Id
     *
     * @return string
     */
    private function getStoreLanguage($storeId)
    {
        /** @var null|array $storesLang */
        static $storesLang;
        //====================================================================//
        // Load Configuration
        if (!isset($storesLang[$storeId])) {
            $storesLang[$storeId] = Mage::getStoreConfig('splashsync_splash_options/langs/store_lang', $storeId);
            if (empty($storesLang[$storeId])) {
                $storesLang[$storeId] = "en_US";
            }
        }

        return $storesLang[$storeId];
    }
}
