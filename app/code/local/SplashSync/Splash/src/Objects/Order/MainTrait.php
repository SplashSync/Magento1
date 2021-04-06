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

use Mage;
use Mage_Core_Exception;
use Mage_Sales_Model_Order      as MageOrder;
use Splash\Core\SplashCore      as Splash;

/**
 * Magento 1 Order Main Fields Access
 */
trait MainTrait
{
    /**
     * List of Available Order Statuses
     *
     * @var string[]
     */
    private $availableStatus = array(
        "OrderPaymentDue" => "Payment Due",
        "OrderProcessing" => "In Process",
        "OrderReturned" => "Returned",
        "OrderDelivered" => "Delivered",
        "OrderCancelled" => "Canceled",
        "OrderProblem" => "On Hold"
    );

    //====================================================================//
    // Class Tooling Functions
    //====================================================================//

    /**
     * Get Standardized Order Status
     *
     * @param string $state
     *
     * @return string
     */
    public static function getStandardOrderState($state)
    {
        //====================================================================//
        // Generate Schema.org orderStatus From Order State
        switch ($state) {
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
     * Get Magento Order Status
     *
     * @param mixed $state
     *
     * @return string
     */
    public static function getMagentoOrderState($state)
    {
        //====================================================================//
        // Generate Magento Order State from Schema.org orderStatus
        switch ($state) {
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
     * Build Address Fields using FieldFactory
     */
    protected function buildMainFields(): void
    {
        //====================================================================//
        // CUSTOMER INFOS
        //====================================================================//

        //====================================================================//
        // Email
        $this->fieldsFactory()->create(SPL_T_EMAIL)
            ->identifier("customer_email")
            ->name("Customer Email")
            ->microData("http://schema.org/ContactPoint", "email")
            ->isReadOnly()
        ;

        //====================================================================//
        // ORDER STATUS
        //====================================================================//

        //====================================================================//
        // Not All Order Status are Available For Debug & Tests
        if (Splash::isDebugMode()) {
            unset(
                $this->availableStatus["OrderCancelled"],
                $this->availableStatus["OrderReturned"],
                $this->availableStatus["OrderDelivered"]
            );
        }

        //====================================================================//
        // Order Current Status
        $this->fieldsFactory()->Create(SPL_T_VARCHAR)
            ->Identifier("state")
            ->Name("Status")
            ->MicroData("http://schema.org/Order", "orderStatus")
            ->isListed()
            ->AddChoices($this->availableStatus)
        ;

        //====================================================================//
        // PRICES INFORMATIONS
        //====================================================================//

        //====================================================================//
        // Order Total Price HT
        $this->fieldsFactory()->create(SPL_T_DOUBLE)
            ->identifier("grand_total_excl_tax")
            ->name("Total (tax excl.)"." (".Mage::app()->getStore()->getCurrentCurrencyCode().")")
            ->microData("http://schema.org/Invoice", "totalPaymentDue")
            ->isReadOnly()
        ;

        //====================================================================//
        // Order Total Price TTC
        $this->fieldsFactory()->create(SPL_T_DOUBLE)
            ->identifier("grand_total")
            ->name("Total (tax incl.)"." (".Mage::app()->getStore()->getCurrentCurrencyCode().")")
            ->microData("http://schema.org/Invoice", "totalPaymentDueTaxIncluded")
            ->isListed()
            ->isReadOnly()
        ;

        //====================================================================//
        // ORDER Currency Data
        //====================================================================//

        //====================================================================//
        // Order Currency
        $this->fieldsFactory()->Create(SPL_T_CURRENCY)
            ->Identifier("order_currency_code")
            ->Name("Currency")
            ->MicroData("https://schema.org/PriceSpecification", "priceCurrency")
        ;
        //====================================================================//
        // Order Currency
        $this->fieldsFactory()->Create(SPL_T_DOUBLE)
            ->Identifier("base_to_order_rate")
            ->Name("Currency Rate")
            ->MicroData("https://schema.org/PriceSpecification", "priceCurrencyRate")
        ;
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    protected function getMainFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // PRICE INFORMATIONS
            //====================================================================//
            case 'grand_total_excl_tax':
                $this->out[$fieldName] = $this->object->getSubtotal() + $this->object->getShippingAmount();

                break;
            case 'customer_email':
            case 'grand_total':
                $this->getData($fieldName);

                break;
            //====================================================================//
            // ORDER STATUS
            //====================================================================//
            case 'state':
                $this->out[$fieldName] = self::getStandardOrderState($this->object->getState());

                break;
            //====================================================================//
            // ORDER Currency Data
            //====================================================================//
            case 'order_currency_code':
            case 'base_to_order_rate':
                $this->getData($fieldName);

                break;
            default:
                return;
        }

        unset($this->in[$key]);
    }

    /**
     * Write Given Fields
     *
     * @param string $fieldName Field Identifier / Name
     * @param mixed  $data      Field Data
     *
     * @return void
     */
    protected function setMainFields(string $fieldName, $data): void
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            //====================================================================//
            // ORDER STATUS
            //====================================================================//
            case 'state':
                $this->setOrderStatus($data);

                break;
            //====================================================================//
            // ORDER Currency Data
            //====================================================================//
            case 'order_currency_code':
            case 'base_to_order_rate':
                $this->setData($fieldName, $data);

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }

    /**
     * Update Order Status
     *
     * @param string $status Schema.org Order Status String
     *
     * @return bool
     */
    private function setOrderStatus(string $status): bool
    {
        if (!$this->isSplash()) {
            Splash::log()->war("You Cannot Change Status of Orders Created on Magento");

            return true;
        }

        //====================================================================//
        // Generate Magento Order State from Schema.org orderStatus
        if ($this->object->getState() == self::getMagentoOrderState($status)) {
            return true;
        }

        //====================================================================//
        // Update Order State if Required
        try {
            //====================================================================//
            // EXECUTE SYSTEM ACTIONS if Necessary
            $this->doOrderStatusUpdate($status);
        } catch (Mage_Core_Exception $exc) {
            Splash::log()->errTrace($exc->getMessage());
        }
        $this->needUpdate();

        return true;
    }

    /**
     * Try Update of Order Status
     *
     * @param string $status Schema.org Order Status String
     *
     * @return void
     */
    private function doOrderStatusUpdate(string $status): void
    {
        switch ($status) {
            case "OrderPaymentDue":
                $this->object->setState(MageOrder::STATE_PENDING_PAYMENT, true, 'Updated by SplashSync Module', true);

                break;
            case "OrderProcessing":
            case "OrderInTransit":
            case "OrderPickupAvailable":
                $this->object->setState(MageOrder::STATE_PROCESSING, true, 'Updated by SplashSync Module', true);

                break;
            case "OrderDelivered":
                $this->object->setData('state', MageOrder::STATE_COMPLETE);
                $this->object->setStatus(MageOrder::STATE_COMPLETE);
                $this->object->addStatusHistoryComment('Updated by SplashSync Module', false);

                break;
            case "OrderReturned":
                $this->object->setData('state', MageOrder::STATE_CLOSED);
                $this->object->setStatus(MageOrder::STATE_CLOSED);
                $this->object->addStatusHistoryComment('Updated by SplashSync Module', false);

                break;
            case "OrderCancelled":
                $this->object->cancel();

                break;
            case "OrderProblem":
                $this->object->hold();

                break;
        }
    }
}
