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
use Mage_Core_Model_Website;

/**
 * @abstract    Magento 1 Object SplashOrigin Access
 */
trait SplashOriginTrait
{
    

    /**
    *   @abstract     Build Fields using FieldFactory
    */
    private function buildSplashOriginFields()
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
     *  @abstract     Read requested Field
     *
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     *
     *  @return         none
     */
    private function getSplashOriginFields($Key, $FieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($FieldName) {
            case 'splash_origin':
                $this->getData($FieldName);
                break;
            default:
                return;
        }
        unset($this->In[$Key]);
    }
    

    /**
     *  @abstract     Write Given Fields
     *
     *  @param        string    $FieldName              Field Identifier / Name
     *  @param        mixed     $Data                   Field Data
     *
     *  @return         none
     */
    private function setSplashOriginFields($FieldName, $Data)
    {
        //====================================================================//
        // WRITE Fields
        switch ($FieldName) {
            case 'splash_origin':
                $this->setData($FieldName, $Data);
                break;
            default:
                return;
        }
        unset($this->In[$FieldName]);
    }
    
    /**
     *  @abstract   Detect Creation Website with Origin Id Given by Splash
     *
     *  @return     Mage_Core_Model_Website
     */
    protected function getSplashOriginWebsite()
    {
        //====================================================================//
        // If Origin Given => Select Choosen Website
        if ( isset($this->In["splash_origin"]) && !empty($this->In["splash_origin"]) ) {
            foreach (Mage::app()->getWebsites() as $website) {
                if ($this->In["splash_origin"] == $website->getConfig('splashsync_splash_options/advanced/origin')) {
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
     *  @abstract     Detect Creation Websites Ids Array with Origin Id Given by Splash
     *
     *  @return         array
     */
    protected function getSplashOriginWebsiteIds()
    {
        return [$this->getSplashOriginWebsite()->getId()];
    }    
    
    /**
     *  @abstract   Detect Creation Store with Origin Id Given by Splash
     *
     *  @return     Mage_Core_Model_Store,
     */
    protected function getSplashOriginStore()
    {
        return $this->getSplashOriginWebsite()->getDefaultStore();
    }    
}
