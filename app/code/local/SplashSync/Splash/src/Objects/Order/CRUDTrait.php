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

namespace Splash\Local\Objects\Order;

use Splash\Core\SplashCore      as Splash;

// Magento Namespaces
use Mage;
use Mage_Sales_Model_Order      as MageOrder;

/**
 * @abstract    Magento 1 Customers CRUD Functions
 */
trait CRUDTrait {
    
    /**
     * @abstract    Load Request Object 
     * 
     * @param       array   $Id               Object id
     * 
     * @return      mixed
     */
    public function Load( $Id )
    {
        //====================================================================//
        // Stack Trace
        Splash::Log()->Trace(__CLASS__,__FUNCTION__);  
        //====================================================================//
        // Safety Checks 
        if (empty ($Id) ) {
            return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__," Missing Id.");
        }
        $Order = Mage::getModel('sales/order')->load($Id);
        if ( $Order->getEntityId() != $Id )   {
            return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__," Unable to load Customer Order (" . $Id . ").");
        }
        return $Order;
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
        Splash::Log()->Trace(__CLASS__,__FUNCTION__); 
        //====================================================================//
        // Check Required Fields 
        if ( !$this->verifyRequiredFields() ) {
            return False;
        }           
        //====================================================================//
        // Create Empty Customer Order
        $Order = Mage::getModel('sales/order');
        //====================================================================//
        // Setup Order External Id to Know this is a Splash Created Order
        $Order->setExtOrderId(self::SPLASH_LABEL);
        // Set Is Virtual Order => No Billing or Shipping Address
        $Order->setIsVirtual(True);
        // Set Default Payment Method
        $Order->setData('payment', array('method'    => 'checkmo'));
        // Set Sales Order Payment
        $Order->setPayment(Mage::getModel('sales/order_payment')->setMethod('checkmo'));
        // Set Order Initial Status
        $Order->setState(MageOrder::STATE_NEW, "pending", 'Just Created by SplashSync Module',True);
        //====================================================================//
        // Set Currency To Default Store Values
        $DefaultCurrency = Mage::app()->getStore()->getBaseCurrencyCode();
        $Order->setGlobalCurrencyCode($DefaultCurrency);
        $Order->setOrderCurrencyCode($DefaultCurrency);
        $Order->setBaseCurrencyCode($DefaultCurrency);
        $Order->setBaseToOrderRate(1);        
        //====================================================================//
        // Save Object
        try {
            $Order->save();
        } catch (Mage_Catalog_Exception $ex) {
            Splash::Log()->Deb($ex->getTraceAsString());
            return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,$ex->getMessage());
        }
        return $Order;        
    }
    
    /**
     * @abstract    Update Request Object 
     * 
     * @param       array   $Needed         Is This Update Needed
     * 
     * @return      string      Object Id
     */
    public function Update( $Needed )
    {
        //====================================================================//
        // Stack Trace
        Splash::Log()->Trace(__CLASS__,__FUNCTION__);  
        if (!$Needed) {
            return $this->Object->getEntityId();
        }
        //====================================================================//
        // Update order Totals
        $this->_UpdateTotals();
        //====================================================================//
        // Do Generic Magento Object Save & Verify Update was Ok
        $Save = Splash::Local()->ObjectSave($this->Object, $Needed , "Customer Order");
        if ( $Save !== False ) {
            Splash::Object("Order")->Lock($Save);
        }
        return $Save;        
    } 
        
    /**
     * @abstract    Delete requested Object
     * 
     * @param       int     $Id     Object Id.  If NULL, Object needs to be created.
     * 
     * @return      bool                          
     */    
    public function Delete($Id = NULL)
    {
        //====================================================================//
        // Stack Trace
        Splash::Log()->Trace(__CLASS__,__FUNCTION__); 
        //====================================================================//
        // Execute Generic Magento Delete Function ...
        return Splash::Local()->ObjectDelete('sales/order',$Id);         
    } 
    
}
