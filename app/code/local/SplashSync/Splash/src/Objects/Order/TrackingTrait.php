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

/**
 * @abstract    Magento 1 Orders Tracking Fields Access
 */
trait TrackingTrait
{
    
    //====================================================================//
    // General Class Variables
    //====================================================================//

    protected $trackings          = null;
    protected $tracking           = array(
        "title"             =>      "",
        "carrier_code"      =>      "",
        "track_number"      =>      "",
        "track_url"         =>      "",
    );
  
    /**
    *   @abstract     Build Fields using FieldFactory
    */
    private function buildFirstTrackingFields()
    {
        
        //====================================================================//
        // Order Shipping Method
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("title")
                ->Name("Shipping Method")
                ->MicroData("http://schema.org/ParcelDelivery", "provider")
                ->ReadOnly();

        //====================================================================//
        // Order Shipping Method
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("carrier_code")
                ->Name("Carrier Code")
                ->MicroData("http://schema.org/ParcelDelivery", "identifier")
                ->ReadOnly();
        
        //====================================================================//
        // Order Tracking Number
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("track_number")
                ->Name("Tracking Number")
                ->MicroData("http://schema.org/ParcelDelivery", "trackingNumber")
                ->ReadOnly();
        
        //====================================================================//
        // Order Tracking Url
//        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
//                ->Identifier("track_url")
//                ->InList("tracking")
//                ->Name("Tracking Url")
//                ->MicroData("http://schema.org/ParcelDelivery","trackingurl")
//                ->ReadOnly();
    }
    
    /**
    *   @abstract     Build Fields using FieldFactory
    */
    private function buildTrackingFields()
    {
        
        //====================================================================//
        // Order Shipping Method
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("title")
                ->InList("tracking")
                ->Name("Shipping Method")
                ->MicroData("http://schema.org/ParcelDelivery", "provider")
                ->ReadOnly();

        //====================================================================//
        // Order Shipping Method
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("carrier_code")
                ->InList("tracking")
                ->Name("Carrier Code")
                ->MicroData("http://schema.org/ParcelDelivery", "identifier")
                ->ReadOnly();
        
        //====================================================================//
        // Order Tracking Number
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("track_number")
                ->InList("tracking")
                ->Name("Tracking Number")
                ->MicroData("http://schema.org/ParcelDelivery", "trackingNumber")
                ->ReadOnly();
        
        //====================================================================//
        // Order Tracking Url
//        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
//                ->Identifier("track_url")
//                ->InList("tracking")
//                ->Name("Tracking Url")
//                ->MicroData("http://schema.org/ParcelDelivery","trackingurl")
//                ->ReadOnly();
    }
    
    
    /**
     *  @abstract     Read Order Payment
     *
     *  @return         none
     */
    private function loadTracking($Order)
    {
        //====================================================================//
        // Load Order Tracking Collection
        $this->trackings  =   $Order->getTracksCollection()->getItems();
        
        //====================================================================//
        // Load First Tracking Number
        if ($Order->getTracksCollection()->count() > 0) {
            $this->tracking["title"]            =  $Order->getTracksCollection()->getFirstItem()->getTitle();
            $this->tracking["carrier_code"]     =  $Order->getTracksCollection()->getFirstItem()->getCarrierCode();
            $this->tracking["track_number"]     =  $Order->getTracksCollection()->getFirstItem()->getTrackNumber();
        }
    }
    
    /**
     *  @abstract     Read requested Field
     *
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     *
     *  @return         none
     */
    private function getTrackingFields($Key, $FieldName)
    {
        $Index  =   0;
        //====================================================================//
        // Decode Field Name
        $ListFieldName = $this->Lists()->InitOutput($this->Out, "tracking", $FieldName);
        
        //====================================================================//
        // Fill List with Data
        foreach ($this->trackings as $Tracking) {
            //====================================================================//
            // READ Fields
            switch ($ListFieldName) {
                //====================================================================//
                // Generic Infos
                case 'title':
                case 'carrier_code':
                case 'track_number':
                    $Value = $Tracking->getData($ListFieldName);
                    break;
                
                default:
                    return;
            }
            //====================================================================//
            // Do Fill List with Data
            $this->Lists()->Insert($this->Out, "tracking", $FieldName, $Index, $Value);
            $Index++;
        }
        unset($this->In[$Key]);
    }

    /**
     *  @abstract     Read requested Field
     *
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     *
     *  @return         none
     */
    private function getFirstTrackingFields($Key, $FieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($FieldName) {
            //====================================================================//
            // Generic Infos
            case 'title':
            case 'carrier_code':
            case 'track_number':
                $this->Out[$FieldName] = $this->tracking[$FieldName];
                break;

            default:
                return;
        }
        unset($this->In[$Key]);
    }
}
