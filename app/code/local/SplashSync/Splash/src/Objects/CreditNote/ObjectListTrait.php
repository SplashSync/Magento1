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

namespace Splash\Local\Objects\CreditNote;

use Mage;
use Mage_Sales_Model_Order_Creditmemo;

/**
 * Magento 1 Credit Notes Objects Lists Access
 */
trait ObjectListTrait
{
    /**
     * Return List Of Order's Credit Notes with required filters
     *
     * @param string $filter Filters for Object Listing.
     * @param array  $params Search parameters for result List.
     *
     * @return array $data                 List of all customers main data
     */
    public function objectsList($filter = null, $params = null)
    {
        //====================================================================//
        // Setup filters
        $filters = array();
        if (!empty($filter) && is_string($filter)) {
            $filters = array(
                array('attribute' => 'increment_id',     'like' => "%".$filter."%"),
            );
        }
        //====================================================================//
        // Execute Core Object List Function
        return $this->coreObjectsList($filters, (array) $params);
    }

    /**
     * Convert Object for Lists
     *
     * @param Mage_Sales_Model_Order_Creditmemo $listObject
     *
     * @return array
     */
    protected function objectListToArray(Mage_Sales_Model_Order_Creditmemo $listObject): array
    {
        $currencySymbol = " ".Mage::app()->getLocale()->currency($listObject->getOrderCurrencyCode())->getSymbol();

        return array(
            "id" => $listObject->getEntityId(),
            "increment_id" => $listObject->getIncrementId(),
            "customer_name" => $listObject->getOrder()->getCustomerName(),
            "created_at" => $listObject->getCreatedAt(),
            "grand_total" => round($listObject->getGrandTotal(), 2).$currencySymbol,
        );
    }
}
