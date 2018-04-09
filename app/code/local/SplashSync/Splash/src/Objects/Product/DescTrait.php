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

namespace Splash\Local\Objects\Product;

// Splash Namespaces
use Splash\Core\SplashCore      as Splash;

/**
 * @abstract    Magento 1 Products Descriptions Fields Access
 */
trait DescTrait {
    

    /**
    *   @abstract     Build Description Fields using FieldFactory
    */
    private function buildDescFields()   {
        
        //====================================================================//
        // PRODUCT DESCRIPTIONS
        //====================================================================//

        //====================================================================//
        // Name without Options
        $this->FieldsFactory()->Create(SPL_T_MVARCHAR)
                ->Identifier("name")
                ->Name("Product Name without Options")
                ->Group("Description")
                ->IsListed()
                ->MicroData("http://schema.org/Product","name")
                ->isRequired();

        //====================================================================//
        // Long Description
        $this->FieldsFactory()->Create(SPL_T_MTEXT)
                ->Identifier("description")
                ->Name("Description")
                ->Group("Description")
                ->MicroData("http://schema.org/Article","articleBody");
        
        //====================================================================//
        // Short Description
        $this->FieldsFactory()->Create(SPL_T_MVARCHAR)
                ->Identifier("short_description")
                ->Name("Short Description")
                ->Group("Description")
                ->MicroData("http://schema.org/Product","description");

        //====================================================================//
        // Meta Description
        $this->FieldsFactory()->Create(SPL_T_MVARCHAR)
                ->Identifier("meta_description")
                ->Name("SEO" . " " . "Meta description")
                ->Group("SEO")
                ->MicroData("http://schema.org/Article","headline");

        //====================================================================//
        // Meta Title
        $this->FieldsFactory()->Create(SPL_T_MVARCHAR)
                ->Identifier("meta_title")
                ->Name("SEO" . " " . "Meta title")
                ->Group("SEO")
                ->MicroData("http://schema.org/Article","alternateName");
        
        //====================================================================//
        // Url Path
        $this->FieldsFactory()->Create(SPL_T_MVARCHAR)
                ->Identifier("url_key")
                ->Name("SEO" . " " . "Friendly URL")
                ->Group("SEO")
                ->MicroData("http://schema.org/Product","urlRewrite")
                ->AddOption("isLowerCase")
                ;
        
    }    



    /**
     *  @abstract     Read requested Field
     * 
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     * 
     *  @return         none
     */
    private function getDescFields($Key,$FieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($FieldName)
        {
            //====================================================================//
            // PRODUCT MULTILANGUAGES CONTENTS
            //====================================================================//
            case 'name':
            case 'description':
            case 'short_description':
            case 'meta_title':
            case 'meta_description':
            case 'meta_keywords':
            case 'url_key':
                $this->Out[$FieldName] = Splash::Local()->getMultilang($this->Object,$FieldName);
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
    private function setDescFields($FieldName,$Data) 
    {
        //====================================================================//
        // WRITE Field
        switch ($FieldName)
        {
            
            //====================================================================//
            // PRODUCT MULTILANGUAGES CONTENTS
            //====================================================================//
            case 'name':
            case 'description':
            case 'short_description':
            case 'meta_title':
            case 'meta_description':
            case 'meta_keywords':
            case 'url_key':
                if ( Splash::Local()->setMultilang($this->Object,$FieldName,$Data) ) {
                    $this->needUpdate();
                } 
                break;
            default:
                return;
        }
        unset($this->In[$FieldName]);
    }
 
    
}
