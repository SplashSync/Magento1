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

namespace Splash\Local\Objects\ThirdParty;

use Splash\Core\SplashCore      as Splash;

// Magento Namespaces
use Mage;
use Mage_Customer_Exception;

/**
 * @abstract    Magento 1 Customers Main Fields Access
 */
trait MainTrait {
    
    
    /**
    *   @abstract     Build Customers Main Fields using FieldFactory
    */
    private function buildMainFields()
    {
        
        //====================================================================//
        // Gender Name
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("gender_name")
                ->Name("Social title")
                ->MicroData("http://schema.org/Person","honorificPrefix")
                ->ReadOnly();       

        //====================================================================//
        // Gender Type
        $desc = "Social title" . " ; 0 => Male // 1 => Female // 2 => Neutral";
        $this->FieldsFactory()->Create(SPL_T_INT)
                ->Identifier("gender")
                ->Name("Social title")
                ->MicroData("http://schema.org/Person","gender")
                ->Description($desc)
                ->AddChoice(0,    "Male")
                ->AddChoice(1,    "Femele")                
                ->NotTested();       
        
        //====================================================================//
        // Date Of Birth
        $this->FieldsFactory()->Create(SPL_T_DATE)
                ->Identifier("dob")
                ->Name("Date of birth")
                ->MicroData("http://schema.org/Person","birthDate");

        //====================================================================//
        // Company
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("company")
                ->Name("Company")
                ->MicroData("http://schema.org/Organization","legalName")
                ->ReadOnly();
        
        //====================================================================//
        // Prefix
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("prefix")
                ->Name("Prefix name")
                ->MicroData("http://schema.org/Person","honorificPrefix");        
        
        //====================================================================//
        // MiddleName
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("middlename")
                ->Name("Middlename")
                ->MicroData("http://schema.org/Person","additionalName");        
        
        //====================================================================//
        // Suffix
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("suffix")
                ->Name("Suffix name")
                ->MicroData("http://schema.org/Person","honorificSuffix");       
        
//        //====================================================================//
//        // Address List
//        $this->FieldsFactory()->Create(self::ObjectId_Encode( "Address" , SPL_T_ID))
//                ->Identifier("address")
//                ->InList("contacts")
//                ->Name($this->spl->l("Address"))
//                ->MicroData("http://schema.org/Organization","address")
//                ->ReadOnly();
        
    }   
    
    
    
    /**
     *  @abstract     Read requested Field
     * 
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     * 
     *  @return         none
     */
    private function getMainFields($Key,$FieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($FieldName)
        {
            //====================================================================//
            // Customer Company Overriden by User Id 
            case 'company':
                if ( !empty($this->Object->getData($FieldName)) ) {
                    $this->Out[$FieldName] = $this->Object->getData($FieldName);
                    break;
                } 
                $this->Out[$FieldName] = "Magento1("  . $this->Object->getEntityId() . ")";
                break;            
            
            //====================================================================//
            // Gender Name
            case 'gender_name':
                if (empty($this->Object->getData("gender") )) {
                    $this->Out[$FieldName] = Splash::Trans("Empty");
                    break;
                }
                if ($this->Object->getData("gender") == 2) {
                    $this->Out[$FieldName] = "Femele";                    
                } else {
                    $this->Out[$FieldName] = "Male";
                }
                break;
            //====================================================================//
            // Gender Type
            case 'gender':
                if ($this->Object->getData($FieldName) == 2) {
                    $this->Out[$FieldName] = 1;                    
                } else {
                    $this->Out[$FieldName] = 0;
                }
                break;  
                
            //====================================================================//
            // Customer Date Of Birth
            case 'dob':
                $this->Out[$FieldName] = date( SPL_T_DATECAST, Mage::getModel("core/date")->gmtTimestamp($this->Object->getData($FieldName)));
                break;

            //====================================================================//
            // Customer Extended Names
            case 'prefix':
            case 'middlename':
            case 'suffix':
                $this->Out[$FieldName] = $this->Object->getData($FieldName);
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
    private function setMainFields($FieldName,$Data) {
        //====================================================================//
        // WRITE Fields
        switch ($FieldName)
        {
            //====================================================================//
            // Gender Type
            case 'gender':
                //====================================================================//
                // Convert Gender Type Value to Magento Values 
                // Splash Social title ; 0 => Male // 1 => Female // 2 => Neutral
                // Magento Social title ; 1 => Male // 2 => Female
                $Data++;
                //====================================================================//
                // Update Gender Type
                if ( $this->Object->getData($FieldName) != $Data ) {
                    $this->Object->setData($FieldName, $Data);
                    $this->needUpdate();
                }  
                break;                     
            //====================================================================//
            // Customer Date Of Birth
            case 'dob':

                $CurrentDob = date( SPL_T_DATECAST, Mage::getModel("core/date")->gmtTimestamp($this->Object->getData($FieldName)));
                if ( $CurrentDob != $Data ) {
                    $this->Object->setData($FieldName, $Data);
                    $this->needUpdate();
                }   
                break;
            
            //====================================================================//
            // Customer Extended Names
            case 'prefix':
            case 'middlename':
            case 'suffix':
                if ( $this->Object->getData($FieldName) != $Data ) {
                    $this->Object->setData($FieldName, $Data);
                    $this->needUpdate();
                }   
                break;
                
            default:
                return;
        }
        unset($this->In[$FieldName]);
    }    
    
    
}
