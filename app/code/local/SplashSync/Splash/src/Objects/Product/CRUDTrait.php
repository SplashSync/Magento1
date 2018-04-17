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

use Splash\Core\SplashCore      as Splash;

// Magento Namespaces
use Mage;
use Mage_Catalog_Model_Product_Status;
use Mage_Catalog_Model_Product_Type;
use Mage_Catalog_Exception;

/**
 * @abstract    Magento 1 Customers CRUD Functions
 */
trait CRUDTrait
{
    
    //====================================================================//
    // General Class Variables
    //====================================================================//
    protected $ProductId      = null;     // Magento Product Class Id
    protected $AttributeId    = null;     // Magento Product Attribute Class Id

    
    /**
     * @abstract    Load Request Object
     *
     * @param       array   $Id               Object id
     *
     * @return      mixed
     */
    public function Load($Id)
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace(__CLASS__, __FUNCTION__);
        $Product = False;        
        //====================================================================//
        // Decode Product Id
        $this->ProductId        = self::getId($Id);
        $this->AttributeId      = self::getAttribute($Id);
        //====================================================================//
        // Safety Checks
        if (empty($Id)  || empty($this->ProductId)) {
            return Splash::log()->err("ErrLocalTpl", __CLASS__, __FUNCTION__, " Missing Id.");
        }
        //====================================================================//
        // If $id Given => Load Product Object From DataBase
        //====================================================================//
        if (!empty($this->ProductId)) {
            //====================================================================//
            // Init Object
            $Product = Mage::getModel('catalog/product')->load($this->ProductId);
            if ($Product->getEntityId() != $this->ProductId) {
                return Splash::log()->err("ErrLocalTpl", __CLASS__, __FUNCTION__, "Unable to fetch Product (" . $this->ProductId . ")");
            }
        }
        return $Product;
    }
    
    /**
     * @abstract    Create Request Object
     *
     * @param       array   $List         Given Object Data
     *
     * @return      object     New Object
     */
    public function Create()
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace(__CLASS__, __FUNCTION__);
        //====================================================================//
        // Check Required Fields
        if (!$this->verifyRequiredFields()) {
            return false;
        }
        //====================================================================//
        // Ensure Current Store is Admin Store
        if (!Mage::app()->getStore()->isAdmin()) {
            Mage::app()->setCurrentStore(\Mage_Core_Model_App::ADMIN_STORE_ID);
        }
        //====================================================================//
        // Init Product Class
        $Product = Mage::getModel('catalog/product')
                ->setStatus(Mage_Catalog_Model_Product_Status::STATUS_ENABLED);
        //====================================================================//
        // Init Product Entity
        $Product->setAttributeSetId(Mage::getStoreConfig('splashsync_splash_options/products/attribute_set'));
        //====================================================================//
        // Init Product Type => Always Simple when Created formOutside Magento
        $Product->setTypeId((Mage_Catalog_Model_Product_Type::TYPE_SIMPLE));
        $Product->setData("sku", $this->In["sku"]);
        //====================================================================//
        // Save Object
        try {
            $Product->save();
        } catch (Mage_Catalog_Exception $ex) {
            Splash::log()->deb($ex->getTraceAsString());
            return Splash::log()->err("ErrLocalTpl", __CLASS__, __FUNCTION__, $ex->getMessage());
        }
        $this->ProductId        = $Product->getEntityId();
        
        return $Product;
    }
    
    /**
     * @abstract    Update Request Object
     *
     * @param       array   $Needed         Is This Update Needed
     *
     * @return      string      Object Id
     */
    public function Update($Needed)
    {
        return $this->CoreUpdate($Needed);
    }
        
    /**
     * @abstract    Delete requested Object
     *
     * @param       int     $Id     Object Id.  If NULL, Object needs to be created.
     *
     * @return      bool
     */
    public function Delete($Id = null)
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace(__CLASS__, __FUNCTION__);
        //====================================================================//
        // Decode Product Id
        if (!empty($Id)) {
            $this->ProductId    = $this->getId($Id);
            $this->AttributeId  = $this->getAttribute($Id);
        } else {
            return Splash::log()->err("ErrSchWrongObjectId", __FUNCTION__);
        }
        //====================================================================//
        // Execute Generic Magento Delete Function ...
        return $this->CoreDelete('catalog/product', $this->ProductId);
    }
    
   
// *******************************************************************//
// Product COMMON Local Functions
// *******************************************************************//

    /**
     * @abstract       Convert id_product & id_product_attribute pair
     * 
     * @param   int     $ProductId          Product Identifier (Int10)
     * @param   int     $AttributeId        Product Combinaison Identifier (Int10)
     * 
     * @return  int     0 if KO, >0 if OK (Int32)
     */
    public function getUnikId($ProductId = null, $AttributeId = 0)
    {
        if (is_null($ProductId)) {
            return $this->ProductId + ($this->AttributeId << 20);
        }
        return $ProductId + ($AttributeId << 20);
    }
    
    /**
     * @abstract       Revert UnikId to decode id_product
     * 
     * @param   int     $UnikId         Product UnikId (Int32)
     * 
     * @return  int     0 if KO, >0 if OK (Int10)
     */
    static public function getId($UnikId)
    {
        return $UnikId & 0xFFFFF;
    }
    
    /**
     * @abstract       Revert UnikId to decode id_product_attribute
     * 
     * @param   int     $UnikId         Product UnikId (Int32)
     * 
     * @return  int     0 if KO, >0 if OK (Int10)
     */
    static public function getAttribute($UnikId)
    {
        return $UnikId >> 20;
    }
}
