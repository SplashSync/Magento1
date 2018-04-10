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

// Splash Namespaces
use Splash\Core\SplashCore      as Splash;

// Magento Namespaces
use Mage;
use Mage_Sales_Model_Order      as MageOrder;
use Mage_Core_Exception;

/**
 * @abstract    Magento 1 Order MAin Fields Access
 */
trait MainTrait
{
    
    private $AvailableStatus = array(
                                    "OrderPaymentDue"       => "Payment Due",
                                    "OrderProcessing"       => "In Process",
                                    "OrderReturned"         => "Returned",
                                    "OrderDelivered"        => "Delivered",
                                    "OrderCancelled"        => "Canceled",
                                    "OrderProblem"          => "On Hold"
                                );
            
    /**
    *   @abstract     Build Address Fields using FieldFactory
    */
    private function buildMainFields()
    {

        //====================================================================//
        // CUSTOMER INFOS
        //====================================================================//
        
        //====================================================================//
        // Email
        $this->FieldsFactory()->Create(SPL_T_EMAIL)
                ->Identifier("customer_email")
                ->Name("Customer Email")
                ->MicroData("http://schema.org/ContactPoint", "email")
                ->readOnly();
        
        //====================================================================//
        // ORDER STATUS
        //====================================================================//

        //====================================================================//
        // ?ot All Order Status are Availables For Debug & Tests
        if (SPLASH_DEBUG) {
            unset($this->AvailableStatus["OrderCancelled"]);
            unset($this->AvailableStatus["OrderReturned"]);
            unset($this->AvailableStatus["OrderDelivered"]);
        }
        
        //====================================================================//
        // Order Current Status
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("state")
                ->Name("Status")
                ->MicroData("http://schema.org/Order", "orderStatus")
                ->isListed()
                ->AddChoices($this->AvailableStatus)
                ;
        
        //====================================================================//
        // PRICES INFORMATIONS
        //====================================================================//
        
        //====================================================================//
        // Order Total Price HT
        $this->FieldsFactory()->Create(SPL_T_DOUBLE)
                ->Identifier("grand_total_excl_tax")
                ->Name("Total (tax excl.)" . " (" . Mage::app()->getStore()->getCurrentCurrencyCode() . ")")
                ->MicroData("http://schema.org/Invoice", "totalPaymentDue")
                ->ReadOnly();
        
        //====================================================================//
        // Order Total Price TTC
        $this->FieldsFactory()->Create(SPL_T_DOUBLE)
                ->Identifier("grand_total")
                ->Name("Total (tax incl.)" . " (" . Mage::app()->getStore()->getCurrentCurrencyCode() . ")")
                ->MicroData("http://schema.org/Invoice", "totalPaymentDueTaxIncluded")
                ->isListed()
                ->ReadOnly();
        
        //====================================================================//
        // ORDER Currency Data
        //====================================================================//
        
        //====================================================================//
        // Order Currency
        $this->FieldsFactory()->Create(SPL_T_CURRENCY)
                ->Identifier("order_currency_code")
                ->Name("Currency")
                ->MicroData("https://schema.org/PriceSpecification", "priceCurrency");

        //====================================================================//
        // Order Currency
        $this->FieldsFactory()->Create(SPL_T_DOUBLE)
                ->Identifier("base_to_order_rate")
                ->Name("Currency Rate")
                ->MicroData("https://schema.org/PriceSpecification", "priceCurrencyRate");
    }
        
    /**
     *  @abstract     Read requested Field
     *
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     *
     *  @return         none
     */
    private function getMainFields($Key, $FieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($FieldName) {
            //====================================================================//
            // PRICE INFORMATIONS
            //====================================================================//
            case 'grand_total_excl_tax':
                $this->Out[$FieldName] = $this->Object->getSubtotal() + $this->Object->getShippingAmount();
                break;
            case 'customer_email':
            case 'grand_total':
                $this->getData($FieldName);
                break;
            
            //====================================================================//
            // ORDER STATUS
            //====================================================================//
            case 'state':
                $this->Out[$FieldName]  = self::getStandardOrderState($this->Object->getState());
                break;
        
        
            //====================================================================//
            // ORDER Currency Data
            //====================================================================//
            case 'order_currency_code':
            case 'base_to_order_rate':
                $this->getData($FieldName);
                break;
                
            default:
                return;
        }
        
        unset($this->In[$Key]);
    }
    

    
    /**
     *  @abstract     Write Given Fields
     *
     *  @param        string    $FieldName              Field Identifier / Name
     *  @param        mixed     $Data                   Field Data
     *
     *  @return         none
     */
    private function setMainFields($FieldName, $Data)
    {
        //====================================================================//
        // WRITE Field
        switch ($FieldName) {
            //====================================================================//
            // ORDER STATUS
            //====================================================================//
            case 'state':
                $this->setOrderStatus($Data);
                break;
                
            //====================================================================//
            // ORDER Currency Data
            //====================================================================//
            case 'order_currency_code':
            case 'base_to_order_rate':
                $this->setData($FieldName, $Data);
                break;
                
            default:
                return;
        }
        unset($this->In[$FieldName]);
    }
    
    //====================================================================//
    // Class Tooling Functions
    //====================================================================//

    /**
     *   @abstract   Get Standardized Order Status
     *
     *   @return     bool
     */
    public static function getStandardOrderState($State)
    {
        //====================================================================//
        // Generate Schema.org orderStatus From Order State
        switch ($State) {
            case MageOrder::STATE_NEW:
            case MageOrder::STATE_PENDING_PAYMENT:
                return "OrderPaymentDue";
            case MageOrder::STATE_PROCESSING:
                return "OrderProcessing";
            case MageOrder::STATE_COMPLETE:
                return "OrderDelivered";
            case MageOrder::STATE_CLOSED:
                return "OrderReturned";
            case MageOrder::STATE_CANCELED:
                return "OrderCancelled";
            case MageOrder::STATE_HOLDED:
                return "OrderProblem";
            case MageOrder::STATE_PAYMENT_REVIEW:
                return "OrderProblem";
        }
        return "OrderDelivered";
    }
    
    /**
     *   @abstract   Get Magento Order Status
     *
     *   @return     bool
     */
    public static function getMagentoOrderState($State)
    {
        //====================================================================//
        // Generate Magento Order State from Schema.org orderStatus
        switch ($State) {
            case "OrderPaymentDue":
                return MageOrder::STATE_PENDING_PAYMENT;
            case "OrderProcessing":
            case "OrderInTransit":
            case "OrderPickupAvailable":
                return MageOrder::STATE_PROCESSING;
            case "OrderDelivered":
                return MageOrder::STATE_COMPLETE;
            case "OrderReturned":
                return MageOrder::STATE_CLOSED;
            case "OrderCancelled":
                return MageOrder::STATE_CANCELED;
            case "OrderProblem":
                return MageOrder::STATE_HOLDED;
        }
        return MageOrder::STATE_COMPLETE;
    }
        

    /**
     *   @abstract   Check if this Order was Created by Splash
     *
     *   @return     bool
     */
    private function isSplash()
    {
        return ( $this->Object->getExtOrderId() === self::SPLASH_LABEL )? true:false;
    }

    /**
     *   @abstract   Update Order Status
     *
     *   @param      string     $Status         Schema.org Order Status String
     *
     *   @return     bool
     */
    private function setOrderStatus($Status)
    {
        
        if (!$this->isSplash()) {
            Splash::Log()->War("You Cannot Change Status of Orders Created on Magento");
            return true;
        }

        //====================================================================//
        // Generate Magento Order State from Schema.org orderStatus
        if ($this->Object->getState() == self::getMagentoOrderState($Status)) {
            return true;
        }
        
        //====================================================================//
        // Update Order State if Requiered
        try {
            //====================================================================//
            // EXECUTE SYSTEM ACTIONS if Necessary
            $this->doOrderStatusUpdate($Status);
        } catch (Mage_Core_Exception $exc) {
            Splash::Log()->Err("ErrLocalTpl", __CLASS__, __FUNCTION__, $exc->getMessage());
        }
            
//            //====================================================================//
//            // Update Order State if Requiered
//            try {
//                $this->Object->setState(self::getMagentoOrderState($Status), True, 'Updated by SplashSync Module',True);
//            } catch (Mage_Core_Exception $exc) {
//            }
        
        $this->needUpdate();
        return true;
    }
    
    /**
     *   @abstract   Try Update of Order Status
     *
     *   @param      string     $Status         Schema.org Order Status String
     *
     *   @return     bool
     */
    private function doOrderStatusUpdate($Status)
    {
        switch ($Status) {
            case "OrderPaymentDue":
                $this->Object->setState(MageOrder::STATE_PENDING_PAYMENT, true, 'Updated by SplashSync Module', true);
                break;
            case "OrderProcessing":
            case "OrderInTransit":
            case "OrderPickupAvailable":
                $this->Object->setState(MageOrder::STATE_PROCESSING, true, 'Updated by SplashSync Module', true);
                break;
            case "OrderDelivered":
                $this->Object->setData('state', MageOrder::STATE_COMPLETE);
                $this->Object->setStatus(MageOrder::STATE_COMPLETE);
                $this->Object->addStatusHistoryComment('Updated by SplashSync Module', false);
                break;
            case "OrderReturned":
                $this->Object->setData('state', MageOrder::STATE_CLOSED);
                $this->Object->setStatus(MageOrder::STATE_CLOSED);
                $this->Object->addStatusHistoryComment('Updated by SplashSync Module', false);
//                    $this->Object->setState(MageOrder::STATE_CLOSED, True, 'Updated by SplashSync Module',True);
                break;
            case "OrderCancelled":
//                    $this->Object->setState(MageOrder::STATE_CANCELED, True, 'Updated by SplashSync Module',True);
                $this->Object->cancel();
                break;
            case "OrderProblem":
                $this->Object->hold();
//                    $this->Object->setData('state', MageOrder::STATE_HOLDED);
//                    $this->Object->setStatus(MageOrder::STATE_HOLDED);
//                    $this->Object->addStatusHistoryComment('Updated by SplashSync Module', false);
                break;
        }
    }
        
}
