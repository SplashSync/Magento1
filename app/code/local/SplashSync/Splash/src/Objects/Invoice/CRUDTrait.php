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

namespace Splash\Local\Objects\Invoice;

use Splash\Core\SplashCore      as Splash;

// Magento Namespaces
use Mage;

/**
 * @abstract    Magento 1 Invoices CRUD Functions
 */
trait CRUDTrait
{

    //====================================================================//
    // General Class Variables
    //====================================================================//

    protected $Order          = null;
    protected $Products       = null;
    
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
        Splash::Log()->Trace(__CLASS__, __FUNCTION__);
        //====================================================================//
        // Safety Checks
        if (empty($Id)) {
            return Splash::Log()->Err("ErrLocalTpl", __CLASS__, __FUNCTION__, " Missing Id.");
        }
        $Invoice = Mage::getModel('sales/order_invoice')->load($Id);
        if ($Invoice->getEntityId() != $Id) {
            return Splash::Log()->Err("ErrLocalTpl", __CLASS__, __FUNCTION__, " Unable to load Customer Invoice (" . $Id . ").");
        }
        //====================================================================//
        // Load Linked Objects
        $this->Order        = $Invoice->getOrder();
        $this->Products     = $Invoice->getAllItems();
        $this->loadPayment($this->Order);
        
        return $Invoice;
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
        Splash::Log()->Trace(__CLASS__, __FUNCTION__);
        //====================================================================//
        // Execute Generic Magento Delete Function ...
        return Splash::Local()->ObjectDelete('sales/order_invoice', $Id);
    }
}
