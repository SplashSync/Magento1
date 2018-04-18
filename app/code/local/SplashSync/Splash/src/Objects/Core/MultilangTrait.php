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
    private $isMultilang    = null;
    private $defaultLang    = null;
    private $storesLangs    = array();
        
    /**
     * @abstract       is Multilangual Mode
     * @param          object      $Object     Pointer to Object
     * @param          array       $key        Id of a Multilangual Contents
     * @return         bool
     */
    private function isMultilang()
    {
        //====================================================================//
        // Load Configuration
        if ( is_null($this->isMultilang) ) {
            $this->isMultilang    =   Mage::getStoreConfig('splashsync_splash_options/langs/multilang');
        } 
        //====================================================================//
        // Return Mode
        return !empty($this->isMultilang);
    }    
    
    /**
     * @abstract       Get User Default language
     * @return         string
     */
    private function getDefaultLanguage()
    {
        //====================================================================//
        // Load Configuration
        if ( is_null($this->defaultLang) ) {
            $this->defaultLang =   Mage::getStoreConfig('splashsync_splash_options/langs/default_lang');       
            if ( empty($this->defaultLang) ) {
                $this->defaultLang =   "en_US";
            }
        }  
        return $this->defaultLang;
    }
    
    /**
     * @abstract       Get Store language
     * 
     * @param   int     $StoreId     Magento Store Id
     * 
     * @return         string
     */
    private function getStoreLanguage($StoreId)
    {
        //====================================================================//
        // Load Configuration
        if ( !isset($this->storesLangs[$StoreId]) ) {
            $this->storesLangs[$StoreId] =   Mage::getStoreConfig('splashsync_splash_options/langs/store_lang', $StoreId);       
            if ( empty($this->storesLangs[$StoreId]) ) {
                $this->storesLangs[$StoreId] =   "en_US";
            }
        }  
        return $this->storesLangs[$StoreId];
    }

    /**
     * @abstract       is Default Store
     * @param          int      $StoreId     Magento Store Id
     * @return         bool
     */
    private function isDefaultStore($StoreId)
    {
        $DefaultStoreId  = Mage::app()->getStore($StoreId)->getWebsite()->getDefaultStore()->getId();
        return ($StoreId == $DefaultStoreId);
    }    
    
    /**
     * @abstract       Get Store Specific Data
     * 
     * @param   array       $Key            Id of a Multilangual Contents
     * @param   int         $StoreId        Magento Store Id
     * 
     * @return         string
     */
    private function getMultilangData($Key, $StoreId)
    {
        return Mage::getResourceModel($this->Object->getResourceName())
                    ->getAttributeRawValue($this->Object->getEntityId(), $Key, $StoreId);
        
    } 
    
    /**
     * @abstract       Set Store Specific Data
     * 
     * @param   string      $Key            Id of a Multilangual Contents
     * @param   int         $StoreId        Magento Store Id
     * @param   string      $Data           New Multilangual Content
     * @param   int         $MaxLength      Maximum Contents Lenght
     * 
     * @return         string
     */
    private function setMultilangData($Key, $StoreId, $Data = null, $MaxLength = null)
    {        
        //====================================================================//
        // Extract Data & Verify Data Lenght
        $NewData    =   $this->checkDataLength($Key, $Data, $MaxLength);                
        //====================================================================//
        // Compare Data
        if ( $this->getMultilangData($Key, $StoreId) == $NewData ) {
            return false;
        }
        //====================================================================//
        // Load Object Resource
        $ResourceName   = $this->Object->getResourceName() . "_action";
        $Resource       = Mage::getSingleton($ResourceName);
        if ( !$Resource ) {
            return Splash::log()->war("ErrLocalTpl", __CLASS__, __FUNCTION__, "Unable to load Object Resources (" . $ResourceName . ").");
        } 
        //====================================================================//
        // Update Data
        $Resource->updateAttributes(
            array($this->Object->getEntityId()),
            array($Key => $NewData),
            $StoreId
        );  
        //====================================================================//
        // Update Default Data
        if ( $this->isDefaultStore($StoreId) ) {
            $this->Object->setData($Key, $NewData);
        }
        
        return false;        
    } 
    
    /**
     *      @abstract       Read Multilangual Fields of an Object
     *      @param          array       $Key        Id of a Multilangual Contents
     *      @return         int                     0 if KO, 1 if OK
     */
    public function getMultilang($Key = null)
    {
        //====================================================================//
        // If Monolang Mode
        if ( !$this->isMultilang() ) {            
            return $this->getMonolangData($Key);
        }
        //====================================================================//
        // Walk on Stores This Product is Available
        $Response   =   array();
        foreach ( $this->Object->getStoreIds() as $StoreId ) {
            //====================================================================//
            // Load Store Language
            $IsoLang   =  $this->getStoreLanguage($StoreId);
            //====================================================================//
            // Load Store Object Value
            if ( !isset($Response[$IsoLang]) ) {
                $Response[$IsoLang] = $this->getMultilangData($Key, $StoreId);
            }
        }
        return $Response;
    }

    /**
     *  @abstract       Read Monolangual Fields of an Object
     *  @param          object      $Object     Pointer to Object
     *  @param          array       $key        Id of a Multilangual Contents
     *  @return         array
     */
    private function getMonolangData($key = null)
    {
        //====================================================================//
        // Build Monolanguge Data Array
        return array(
            $this->getDefaultLanguage() => $this->Object->getData($key)
        );
    }
    
    /**
     *  @abstract       Update Multilangual Fields of an Object
     *
     *  @param      string      $Key        Id of a Multilangual Contents
     *  @param      array       $Data       New Multilangual Contents
     *  @param      int         $MaxLength  Maximum Contents Lenght
     *
     *  @return     bool    Update needed
     */
    public function setMultilang($Key = null, $Data = null, $MaxLength = null)
    {
        //====================================================================//
        // Check Received Data Are Valid
        if (!is_array($Data) && !is_a($Data, "ArrayObject")) {
            return false;
        }
        //====================================================================//
        // If Monolang Mode
        if ( !$this->isMultilang() ) {
            return $this->setMonolangData($Key, $Data, $MaxLength);
        }        
        //====================================================================//
        // Walk on Stores This Product is Available
        foreach ( $this->Object->getStoreIds() as $StoreId ) {
            //====================================================================//
            // Load Store Language
            $IsoLang   =  $this->getStoreLanguage($StoreId);
            //====================================================================//
            // Check if this Language is Given
            if ( isset($Data[$IsoLang]) ) {
                $this->setMultilangData($Key, $StoreId, $Data[$IsoLang], $MaxLength);
            }
        }
        return false;
    }
    
    /**
     *      @abstract       Update Monolangual Fields of an Object
     *
     *      @param          array       $Key        Id of a Multilangual Contents
     *      @param          array       $Data       New Multilangual Contents
     *      @param          int         $MaxLength  Maximum Contents Lenght
     *
     *      @return         bool                     0 if no update needed, 1 if update needed
     */
    private function setMonolangData($Key = null, $Data = null, $MaxLength = null)
    {
        //====================================================================//
        // Check Default Language Data is Given
        if (!array_key_exists($this->getDefaultLanguage(), $Data) ) {
            return false;
        }
        //====================================================================//
        // Extract Data & Verify Data Lenght
        $NewData    =   $this->checkDataLength($Key, $Data[$this->getDefaultLanguage()], $MaxLength);        
        //====================================================================//
        // Compare Data
        if ( $this->Object->getData($Key) == $NewData ) {
            return false;
        }
        //====================================================================//
        // Update Data
        $this->Object->setData($Key, $NewData);

        return true;
    }    
    
    /**
     * @abstract       Check String Length and Truncate if Needed
     *
     * @param   string      $Key            Id of a Contents 
     * @param   string      $Data           New Contents
     * @param   int         $MaxLength      Maximum Contents Lenght
     *
     * @return  string
     */
    private function checkDataLength($Key = null, $Data = null, $MaxLength = null)
    {
        //====================================================================//
        // No Verify Required
        if (!$MaxLength) {
            return $Data;
        }
        //====================================================================//
        // Verify Data Lenght
        if ( strlen($Data) > $MaxLength) {
            Splash::log()->war("MsgLocalTpl", __CLASS__, __FUNCTION__, "Text is too long for field " . $Key . ", modification skipped.");
            return substr($Data, 0, $MaxLength);
        }
        return $Data;
    }        
    
}
