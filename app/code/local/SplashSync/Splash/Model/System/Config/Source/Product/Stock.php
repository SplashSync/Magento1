<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magento.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magento.com for more information.
 *
 * @category    Mage
 * @package     Mage_Adminhtml
 * @copyright  Copyright (c) 2006-2016 X.commerce, Inc. and affiliates (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Config category source
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class SplashSync_Splash_Model_System_Config_Source_Product_Stock
{
    public function toOptionArray()
    {
    /**
     * Get the resource model
     */
        $resource = Mage::getSingleton('core/resource');
    /**
     * Retrieve the read connection
     */
        $readConnection = $resource->getConnection('core_read');
    /**
     * Prepare SQL Query
     */
        $query = 'SELECT * FROM ' . $resource->getTableName('cataloginventory/stock');
    /**
     * Execute the query and store the results in $results
     */
        $results = $readConnection->fetchAll($query);
        //====================================================================//
        // Iterate all Stocks
        $Select = array();
        foreach ($results as $Stock) {
            //====================================================================//
            // Add Attribute Set to Select List
            $Select[] = array(
                'value' => $Stock["stock_id"],
                'label' => $Stock["stock_name"],
            );
        }
        //====================================================================//
        // Return Sets List
        return $Select;
    }
}
