<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2021 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
// phpcs:disable Squiz.Classes.ValidClassName

/**
 * Config category source
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 *
 * @SuppressWarnings(PHPMD.CamelCaseClassName)
 * @SuppressWarnings(PHPMD.LongClassName)
 */
class SplashSync_Splash_Model_System_Config_Source_Product_Stock
{
    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        /**
         * Get the resource model
         *
         * @var Mage_Core_Model_Resource
         */
        $resource = Mage::getSingleton('core/resource');
        /**
         * Retrieve the read connection
         */
        $readConnection = $resource->getConnection('core_read');
        /**
         * Prepare SQL Query
         */
        $query = 'SELECT * FROM '.$resource->getTableName('cataloginventory/stock');
        /**
         * Execute the query and store the results in $results
         */
        $results = $readConnection->fetchAll($query);
        //====================================================================//
        // Iterate all Stocks
        $select = array();
        foreach ($results as $stock) {
            //====================================================================//
            // Add Attribute Set to Select List
            $select[] = array(
                'value' => $stock["stock_id"],
                'label' => $stock["stock_name"],
            );
        }
        //====================================================================//
        // Return Sets List
        return $select;
    }
}
