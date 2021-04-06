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

namespace Splash\Local\Objects\Order;

use Mage_Sales_Model_Order_Shipment_Track;

/**
 * Magento 1 Orders Tracking Fields Access
 */
trait TrackingTrait
{
    //====================================================================//
    // General Class Variables
    //====================================================================//

    /**
     * @var Mage_Sales_Model_Order_Shipment_Track[]
     */
    protected $trackings;

    /**
     * @var string[]
     */
    protected $tracking = array(
        "title" => "",
        "carrier_code" => "",
        "track_number" => "",
        "track_url" => "",
    );

    /**
     * Build Fields using FieldFactory
     */
    protected function buildFirstTrackingFields(): void
    {
        //====================================================================//
        // Order Shipping Method
        $this->fieldsFactory()->Create(SPL_T_VARCHAR)
            ->Identifier("title")
            ->Name("Shipping Method")
            ->MicroData("http://schema.org/ParcelDelivery", "provider")
            ->isReadOnly()
        ;
        //====================================================================//
        // Order Shipping Method
        $this->fieldsFactory()->Create(SPL_T_VARCHAR)
            ->Identifier("carrier_code")
            ->Name("Carrier Code")
            ->MicroData("http://schema.org/ParcelDelivery", "identifier")
            ->isReadOnly()
        ;
        //====================================================================//
        // Order Tracking Number
        $this->fieldsFactory()->Create(SPL_T_VARCHAR)
            ->Identifier("track_number")
            ->Name("Tracking Number")
            ->MicroData("http://schema.org/ParcelDelivery", "trackingNumber")
            ->isReadOnly()
        ;
    }

    /**
     * Build Fields using FieldFactory
     */
    protected function buildTrackingFields(): void
    {
        //====================================================================//
        // Order Shipping Method
        $this->fieldsFactory()->Create(SPL_T_VARCHAR)
            ->Identifier("title")
            ->InList("tracking")
            ->Name("Shipping Method")
            ->MicroData("http://schema.org/ParcelDelivery", "provider")
            ->isReadOnly()
        ;
        //====================================================================//
        // Order Shipping Method
        $this->fieldsFactory()->Create(SPL_T_VARCHAR)
            ->Identifier("carrier_code")
            ->InList("tracking")
            ->Name("Carrier Code")
            ->MicroData("http://schema.org/ParcelDelivery", "identifier")
            ->isReadOnly()
        ;
        //====================================================================//
        // Order Tracking Number
        $this->fieldsFactory()->Create(SPL_T_VARCHAR)
            ->Identifier("track_number")
            ->InList("tracking")
            ->Name("Tracking Number")
            ->MicroData("http://schema.org/ParcelDelivery", "trackingNumber")
            ->isReadOnly()
        ;
    }

    /**
     * Read Order Payment
     *
     * @param mixed $order
     *
     * @return void
     */
    protected function loadTracking($order): void
    {
        //====================================================================//
        // Load Order Tracking Collection
        $this->trackings = $order->getTracksCollection()->getItems();

        //====================================================================//
        // Load First Tracking Number
        if ($order->getTracksCollection()->count() > 0) {
            $this->tracking["title"] = $order->getTracksCollection()->getFirstItem()->getTitle();
            $this->tracking["carrier_code"] = $order->getTracksCollection()->getFirstItem()->getCarrierCode();
            $this->tracking["track_number"] = $order->getTracksCollection()->getFirstItem()->getTrackNumber();
        }
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    protected function getTrackingFields(string $key, string $fieldName): void
    {
        $index = 0;
        //====================================================================//
        // Decode Field Name
        $listFieldName = self::lists()->InitOutput($this->out, "tracking", $fieldName);
        if (!$listFieldName) {
            return;
        }
        //====================================================================//
        // Fill List with Data
        foreach ($this->trackings as $tracking) {
            //====================================================================//
            // READ Fields
            switch ($listFieldName) {
                //====================================================================//
                // Generic Infos
                case 'title':
                case 'carrier_code':
                case 'track_number':
                    $value = $tracking->getData($listFieldName);

                    break;
                default:
                    return;
            }
            //====================================================================//
            // Do Fill List with Data
            self::lists()->Insert($this->out, "tracking", $fieldName, $index, $value);
            $index++;
        }
        unset($this->in[$key]);
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    private function getFirstTrackingFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // Generic Infos
            case 'title':
            case 'carrier_code':
            case 'track_number':
                $this->out[$fieldName] = $this->tracking[$fieldName];

                break;
            default:
                return;
        }
        unset($this->in[$key]);
    }
}
