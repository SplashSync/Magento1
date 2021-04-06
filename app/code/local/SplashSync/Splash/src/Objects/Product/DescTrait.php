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

namespace Splash\Local\Objects\Product;

/**
 * Magento 1 Products Descriptions Fields Access
 */
trait DescTrait
{
    /**
     * Build Description Fields using FieldFactory
     */
    protected function buildDescFields(): void
    {
        $this->fieldsFactory()->setDefaultLanguage(self::getDefaultLanguage());

        //====================================================================//
        // PRODUCT DESCRIPTIONS
        //====================================================================//

        foreach ($this->getAvailableLanguages() as $isoLang) {
            //====================================================================//
            // Name without Options
            $this->fieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("name")
                ->Name("Product Name without Options")
                ->Group("Description")
                ->MicroData("http://schema.org/Product", "name")
                ->setMultilang($isoLang)
                ->isListed(self::getDefaultLanguage() == $isoLang)
                ->isRequired()
            ;
            //====================================================================//
            // Long Description
            $this->fieldsFactory()->Create(SPL_T_TEXT)
                ->Identifier("description")
                ->Name("Description")
                ->Group("Description")
                ->MicroData("http://schema.org/Article", "articleBody")
                ->setMultilang($isoLang)
            ;
            //====================================================================//
            // Short Description
            $this->fieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("short_description")
                ->Name("Short Description")
                ->Group("Description")
                ->MicroData("http://schema.org/Product", "description")
                ->setMultilang($isoLang)
            ;
            //====================================================================//
            // Meta Description
            $this->fieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("meta_description")
                ->Name("SEO"." "."Meta description")
                ->Group("SEO")
                ->MicroData("http://schema.org/Article", "headline")
                ->setMultilang($isoLang)
            ;
            //====================================================================//
            // Meta Title
            $this->fieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("meta_title")
                ->Name("SEO"." "."Meta title")
                ->Group("SEO")
                ->MicroData("http://schema.org/Article", "alternateName")
                ->setMultilang($isoLang)
            ;
            //====================================================================//
            // Url Path
            $this->fieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("url_key")
                ->Name("SEO"." "."Friendly URL")
                ->Group("SEO")
                ->MicroData("http://schema.org/Product", "urlRewrite")
                ->AddOption("isLowerCase")
                ->setMultilang($isoLang)
            ;
        }
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    protected function getDescFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // Walk on Available Languages
        foreach ($this->getAvailableLanguages() as $storeId => $isoLang) {
            //====================================================================//
            // Decode Multi-lang Field Name
            $baseFieldName = self::fieldNameDecode($fieldName, $isoLang);
            //====================================================================//
            // READ Fields
            switch ($baseFieldName) {
                //====================================================================//
                // PRODUCT MULTI-LANG CONTENTS
                //====================================================================//
                case 'name':
                case 'description':
                case 'short_description':
                case 'meta_title':
                case 'meta_description':
                case 'meta_keywords':
                case 'url_key':
                    $this->out[$fieldName] = $this->getMultiLangData($storeId, $baseFieldName);

                    unset($this->in[$key]);

                    break;
            }
        }
    }

    /**
     * Write Given Fields
     *
     * @param string $fieldName Field Identifier / Name
     * @param mixed $data Field Data
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function setDescFields(string $fieldName, $data): void
    {
        //====================================================================//
        // Walk on Available Languages
        foreach ($this->getAvailableLanguages() as $storeId => $isoLang) {
            //====================================================================//
            // Decode Multi-lang Field Name
            $baseFieldName = self::fieldNameDecode($fieldName, $isoLang);
            //====================================================================//
            // WRITE Field
            switch ($baseFieldName) {
                //====================================================================//
                // PRODUCT MULTI-LANG CONTENTS
                //====================================================================//
                case 'name':
                case 'description':
                case 'short_description':
                case 'meta_title':
                case 'meta_description':
                case 'meta_keywords':
                case 'url_key':
                    if ($this->setMultilangData($storeId, $baseFieldName, $data)) {
                        $this->needUpdate();
                    }
                    unset($this->in[$fieldName]);

                    break;
            }
        }
    }
}
