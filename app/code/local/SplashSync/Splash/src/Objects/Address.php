<?php

/*
 * This file is part of SplashSync Project.
 *
 * Copyright (C) Splash Sync <www.splashsync.com>
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace   Splash\Local\Objects;

// Splash Namespaces
use Splash\Core\SplashCore      as Splash;

use Splash\Models\AbstractObject;
use Splash\Models\Objects\IntelParserTrait;
use Splash\Models\Objects\ObjectsTrait;

// Magento Namespaces
use Mage;

/**
 * @abstract    Splash PHP Module For Magento 1 - ThirdParty Address Object Intégration
 * @author      B. Paquier <contact@splashsync.com>
 */
class Address extends AbstractObject
{
    // Splash Php Core Traits
    use IntelParserTrait;
    use ObjectsTrait;
    
    // Core / Common Traits
    use \Splash\Local\Objects\Core\DataAccessTrait;
    use \Splash\Local\Objects\Core\SplashIdTrait;
    use \Splash\Local\Objects\Core\DatesTrait;

    // Address Fields
    use \Splash\Local\Objects\Address\CRUDTrait;
    use \Splash\Local\Objects\Address\CoreTrait;
    use \Splash\Local\Objects\Address\MainTrait;
    use \Splash\Local\Objects\Address\OptionsTrait;
    
    //====================================================================//
    // Object Definition Parameters
    //====================================================================//
    
    /**
     *  Object Disable Flag. Uncomment this line to Override this flag and disable Object.
     */
//    protected static    $DISABLED        =  True;
    
    /**
     *  Object Name (Translated by Module)
     */
    protected static $NAME           =  "Address";
    
    /**
     *  Object Description (Translated by Module)
     */
    protected static $DESCRIPTION    =  "Magento 1 Customers Address Object";
    
    /**
     *  Object Icon (FontAwesome or Glyph ico tag)
     */
    protected static $ICO            =  "fa fa-envelope-o";

    /**
     *  Object Synchronistion Limitations
     *
     *  This Flags are Used by Splash Server to Prevent Unexpected Operations on Remote Server
     */
    protected static $ALLOW_PUSH_CREATED         =  true;        // Allow Creation Of New Local Objects
    protected static $ALLOW_PUSH_UPDATED         =  true;        // Allow Update Of Existing Local Objects
    protected static $ALLOW_PUSH_DELETED         =  true;        // Allow Delete Of Existing Local Objects
    
    /**
     *  Object Synchronistion Recommended Configuration
     */
    protected static $ENABLE_PUSH_CREATED       =  false;        // Enable Creation Of New Local Objects when Not Existing
    protected static $ENABLE_PUSH_UPDATED       =  false;        // Enable Update Of Existing Local Objects when Modified Remotly
    protected static $ENABLE_PUSH_DELETED       =  false;        // Enable Delete Of Existing Local Objects when Deleted Remotly

    protected static $ENABLE_PULL_CREATED       =  true;         // Enable Import Of New Local Objects
    protected static $ENABLE_PULL_UPDATED       =  true;         // Enable Import of Updates of Local Objects when Modified Localy
    protected static $ENABLE_PULL_DELETED       =  true;         // Enable Delete Of Remotes Objects when Deleted Localy
    

    
    //====================================================================//
    // Class Main Functions
    //====================================================================//
    
    /**
    *   @abstract     Return List Of Customer with required filters
    *   @param        string  $filter               Filters for Object Listing.
    *   @param        array   $params               Search parameters for result List.
    *                         $params["max"]        Maximum Number of results
    *                         $params["offset"]     List Start Offset
    *                         $params["sortfield"]  Field name for sort list (Available fields listed below)
    *                         $params["sortorder"]  List Order Constraign (Default = ASC)
    *   @return       array   $data                 List of all customers main data
    *                         $data["meta"]["total"]     ==> Total Number of results
    *                         $data["meta"]["current"]   ==> Total Number of results
    */
    public function ObjectsList($filter = null, $params = null)
    {
        //====================================================================//
        // Stack Trace
        Splash::Log()->Trace(__CLASS__, __FUNCTION__);
        /* Get customer address model, run a query */
        $Collection = Mage::getModel('customer/address')
                  ->getCollection()
                  ->addAttributeToSelect('*');
        //====================================================================//
        // Setup filters
        // Add filters with names convertions. Added LOWER function to be NON case sensitive
        if ( !empty($filter) && is_string($filter)) {
            $Collection->addFieldToFilter(
                array(
                    array('attribute' => 'firstname',   'like' => "%" . $filter . "%"),
                    array('attribute' => 'lastname',    'like' => "%" . $filter . "%"),
                    array('attribute' => 'street',      'like' => "%" . $filter . "%"),
                    array('attribute' => 'city',        'like' => "%" . $filter . "%"),
                    array('attribute' => 'postcode',    'like' => "%" . $filter . "%"),
                )
            );
        }
        //====================================================================//
        // Setup sortorder
        $sortfield = empty($params["sortfield"])?"entity_id":$params["sortfield"];
        // Build ORDER BY
        $Collection->setOrder($sortfield, $params["sortorder"]);
        //====================================================================//
        // Compute Total Number of Results
        $total      = $Collection->getSize();
        //====================================================================//
        // Build LIMIT
        $Collection->setPageSize($params["max"]);
        if (isset($params["max"]) || ($params["max"] > 0)) {
            $Collection->setCurPage(1 + (int) ($params["offset"] / $params["max"]));
        }
        //====================================================================//
        // Init Result Array
        $Data       = array();
        //====================================================================//
        // For each result, read information and add to $Data
        foreach ($Collection->getItems() as $key => $Address) {
            $Data[$key]         = array_merge(array("company" => null), $Address->toArray());
            $Data[$key]["id"]   = $Data[$key]["entity_id"];
        }
        //====================================================================//
        // Prepare List result meta infos
        $Data["meta"]["current"]    =   count($Data);  // Store Current Number of results
        $Data["meta"]["total"]      =   $total;  // Store Total Number of results
        Splash::Log()->Deb("MsgLocalTpl", __CLASS__, __FUNCTION__, (count($Data)-1)." Address Found.");
        return $Data;
    }
}
