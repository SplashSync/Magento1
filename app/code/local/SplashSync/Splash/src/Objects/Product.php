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
use Splash\Models\Objects\PricesTrait;
use Splash\Models\Objects\ImagesTrait;

// Magento Namespaces
use Mage;

/**
 * @abstract    Splash PHP Module For Magento 1 - Product Object Intégration
 * @author      B. Paquier <contact@splashsync.com>
 */
class Product extends AbstractObject
{
    
    // Splash Php Core Traits
    use IntelParserTrait;
    use PricesTrait;
    use ImagesTrait;

    
    // Core / Common Traits
    use \Splash\Local\Objects\Core\DataAccessTrait;
    use \Splash\Local\Objects\Core\SplashIdTrait;
    use \Splash\Local\Objects\Core\DatesTrait;
    use \Splash\Local\Objects\Core\PricesTrait;

    // Product Traits
    use \Splash\Local\Objects\Product\CRUDTrait;
    use \Splash\Local\Objects\Product\CoreTrait;
    use \Splash\Local\Objects\Product\MainTrait;
    use \Splash\Local\Objects\Product\DescTrait;
    use \Splash\Local\Objects\Product\ImagesTrait;
    use \Splash\Local\Objects\Product\StocksTrait;
    use \Splash\Local\Objects\Product\MetaTrait;
    
    //====================================================================//
    // Object Definition Parameters
    //====================================================================//
    
    /**
     *  Object Disable Flag. Uncomment thius line to Override this flag and disable Object.
     */
//    protected static    $DISABLED        =  True;
    
    /**
     *  Object Name (Translated by Module)
     */
    protected static $NAME            =  "Product";
    
    /**
     *  Object Description (Translated by Module)
     */
    protected static $DESCRIPTION     =  "Magento 1 Product Object";
    
    /**
     *  Object Icon (FontAwesome or Glyph ico tag)
     */
    protected static $ICO     =  "fa fa-product-hunt";
    
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
    *   @abstract     Return List Of Objects with required filters
     *
    *   @param        string  $filter                   Filters/Search String for Contact List.
    *   @param        array   $params                   Search parameters for result List.
    *                         $params["max"]            Maximum Number of results
    *                         $params["offset"]         List Start Offset
    *                         $params["sortfield"]      Field name for sort list (Available fields listed below)
    *                         $params["sortorder"]      List Order Constraign (Default = ASC)
     *
    *   @return       array   $data                     List of all customers main data
    *                         $data["meta"]["total"]     ==> Total Number of results
    *                         $data["meta"]["current"]   ==> Total Number of results
    */
    public function ObjectsList($filter = null, $params = null)
    {
        //====================================================================//
        // Stack Trace
        Splash::Log()->Trace(__CLASS__, __FUNCTION__);
        /* Get customer model, run a query */
        $Collection = Mage::getModel('catalog/product')
                  ->getCollection()
                  ->addAttributeToSelect('*');
        //====================================================================//
        // Setup filters
        // Add filters with names convertions. Added LOWER function to be NON case sensitive
        if ( !empty($filter) && is_string($filter)) {
            $Collection->addFieldToFilter(
                array(
                    array('attribute' => 'sku',     'like' => "%" . $filter . "%"),
                    array('attribute' => 'name',    'like' => "%" . $filter . "%"),
                )
            );
        }        
        //====================================================================//
        // Setup sortorder
        $sortfield = empty($params["sortfield"])?"sku":$params["sortfield"];
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
        foreach ($Collection->getItems() as $key => $Customer) {
//            $Data[$key]         = $Customer->toArray();
            $Data[$key]["id"]       = $Customer->getEntityId();
            $Data[$key]["sku"]      = $Customer->getSku();
            $Data[$key]["name"]     = $Customer->getName();
            $Data[$key]["status"]   = $Customer->getStatus();
            $Data[$key]["price"]    = $Customer->getPrice();
            
            $Data[$key]["qty"]      = $Customer->getStockItem()->getQty();
        }
        //====================================================================//
        // Prepare List result meta infos
        $Data["meta"]["current"]    =   count($Data);  // Store Current Number of results
        $Data["meta"]["total"]      =   $total;  // Store Total Number of results
        Splash::Log()->Deb("MsgLocalTpl", __CLASS__, __FUNCTION__, (count($Data)-1)." Products Found.");
        return $Data;
    }
}
