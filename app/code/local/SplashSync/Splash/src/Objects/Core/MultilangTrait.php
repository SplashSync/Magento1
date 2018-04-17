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

namespace Splash\Local\Objects\Core;

use Splash\Core\SplashCore      as Splash;

// Magento Namespaces
use Mage;

/**
 * @abstract    Magento 1 Multilanguage Functions
 */
trait MultilangTrait
{
    private $multilang;
    private $default_lang;
        
    /**
     *      @abstract       Read Multilangual Fields of an Object
     *      @param          object      $Object     Pointer to Prestashop Object
     *      @param          array       $key        Id of a Multilangual Contents
     *      @return         int                     0 if KO, 1 if OK
     */
    public function getMultilang(&$Object = null, $key = null)
    {
        //====================================================================//
        // Load Recurent Use Parameters
        $this->multilang    =   Mage::getStoreConfig('splashsync_splash_options/langs/multilang');
        $this->default_lang =   Mage::getStoreConfig('splashsync_splash_options/langs/default_lang');        
        
        if (empty($this->multilang) && !empty($this->default_lang)) {
            return array(
                Mage::getStoreConfig('splashsync_splash_options/langs/default_lang') => $Object->getData($key)
            );
        }

        Splash::log()->www("Object", $Object->getData());
    }

    /**
     *      @abstract       Update Multilangual Fields of an Object
     *
     *      @param          object      $Object     Pointer to Prestashop Object
     *      @param          array       $key        Id of a Multilangual Contents
     *      @param          array       $Data       New Multilangual Contents
     *      @param          int         $MaxLength  Maximum Contents Lenght
     *
     *      @return         bool                     0 if no update needed, 1 if update needed
     */
    public function setMultilang($Object = null, $key = null, $Data = null, $MaxLength = null)
    {
        //====================================================================//
        // Load Recurent Use Parameters
        $this->multilang    =   Mage::getStoreConfig('splashsync_splash_options/langs/multilang');
        $this->default_lang =   Mage::getStoreConfig('splashsync_splash_options/langs/default_lang');
        
        //====================================================================//
        // Check Received Data Are Valid
        if (!is_array($Data) && !is_a($Data, "ArrayObject")) {
            return false;
        }
        
        $UpdateRequired = false;
        
        if (empty($this->multilang) && !empty($this->default_lang)) {
            //====================================================================//
            // Compare Data
            if (!array_key_exists($this->default_lang, $Data)
                ||  ( $Object->getData($key) === $Data[$this->default_lang]) ) {
                return $UpdateRequired;
            }
            //====================================================================//
            // Verify Data Lenght
            if ($MaxLength &&  ( strlen($Data[$this->default_lang]) > $MaxLength)) {
                Splash::log()->war("MsgLocalTpl", __CLASS__, __FUNCTION__, "Text is too long for field " . $key . ", modification skipped.");
                return $UpdateRequired;
            }
            //====================================================================//
            // Update Data
            $Object->setData($key, $Data[$this->default_lang]);
            $UpdateRequired = true;
        }
        
        return $UpdateRequired;
    }
}
