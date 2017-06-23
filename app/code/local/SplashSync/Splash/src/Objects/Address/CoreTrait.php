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

namespace Splash\Local\Objects\Address;

// Magento Namespaces
use Mage;

/**
 * @abstract    Magento 1 Customers Address Core Fields Access
 */
trait CoreTrait {
    
    /**
    *   @abstract     Build Address Core Fields using FieldFactory
    */
    private function buildCoreFields()
    {
        //====================================================================//
        // Customer
        $this->FieldsFactory()->Create(self::Objects()->Encode( "ThirdParty" , SPL_T_ID))
                ->Identifier("parent_id")
                ->Name("Customer")
                ->MicroData("http://schema.org/Organization","ID")
                ->isRequired();
        
        //====================================================================//
        // Company
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("company")
                ->Name("Company")
                ->MicroData("http://schema.org/Organization","legalName")
                ->isListed();
        
        //====================================================================//
        // Firstname
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("firstname")
                ->Name("First name")
                ->MicroData("http://schema.org/Person","familyName")
                ->Association("firstname","lastname")        
                ->isRequired()
                ->isListed();        
        
        //====================================================================//
        // Lastname
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("lastname")
                ->Name("Last name")
                ->MicroData("http://schema.org/Person","givenName")
                ->Association("firstname","lastname")            
                ->isRequired()
                ->isListed();             
        
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
        
    }
    
    
    /**
     *  @abstract     Read requested Field
     * 
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     * 
     *  @return         none
     */
    private function getCoreFields($Key,$FieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($FieldName)
        {
            //====================================================================//
            // Direct Readings
            case 'company':
            case 'firstname':
            case 'middlename':
            case 'lastname':
            case 'prefix':
            case 'suffix':
                $this->getData($FieldName);
                break;

            //====================================================================//
            // Customer Object Id Readings
            case 'parent_id':
                $this->Out[$FieldName] = self::Objects()->Encode( "ThirdParty" , $this->Object->getParentId() );
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
    private function setCoreFields($FieldName,$Data) 
    {
        //====================================================================//
        // WRITE Field
        switch ($FieldName)
        {
            //====================================================================//
            // Direct Writtings
            case 'company':
            case 'firstname':
            case 'middlename':
            case 'lastname':
            case 'prefix':
            case 'suffix':
                $this->setData($FieldName,$Data);
                break;

            //====================================================================//
            // Customer Object Id Writtings
            case 'parent_id':
                $this->setParentId($Data);
                break;                    
            default:
                return;            
        }
        unset($this->In[$FieldName]);
    }    
    
    /**
     *  @abstract     Write Given Fields
     */
    private function setParentId($Data) 
    {
        //====================================================================//
        // Decode Customer Id
        $Id = self::Objects()->Id( $Data );
        //====================================================================//
        // Check For Change
        if ( $Id == $this->Object->getParentId() ) {
            return True;
        } 
        //====================================================================//
        // Verify Object Type
        if ( self::Objects()->Type( $Data ) !== "ThirdParty" ) {
            return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__," Wrong Object Type (" . self::Objects()->Type( $Data ) . ").");
        } 
        //====================================================================//
        // Verify Object Exists
        $Customer = Mage::getModel('customer/customer')->load($Id);
        if ( $Customer->getEntityId() != $Id )   {        
            return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__," Unable to load Address Customer(" . $Id . ").");
        } 
        //====================================================================//
        // Update Link
        $this->Object->setParentId($Id);
        return True;
    }      
    
}
