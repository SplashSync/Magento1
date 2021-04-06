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

namespace   Splash\Local\Objects;

use Splash\Models\AbstractObject;
use Splash\Models\Objects\IntelParserTrait;
use Splash\Models\Objects\ListsTrait;
use Splash\Models\Objects\ObjectsTrait;
use Splash\Models\Objects\PricesTrait;

/**
 * Splash PHP Module For Magento 1 - Order Object Intégration
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class Order extends AbstractObject
{
    // Splash Php Core Traits
    use IntelParserTrait;
    use ObjectsTrait;
    use PricesTrait;
    use ListsTrait;

    // Core / Common Traits
    use Core\CRUDTrait;
    use Core\ObjectListTrait;
    use Core\DataAccessTrait;

    // Order Traits
    use Order\CRUDTrait;
    use Order\ObjectListTrait;
    use Order\CoreTrait;
    use Order\MainTrait;
    use Order\AddressTrait;
    use Order\ItemsTrait;
    use Order\MetaTrait;
    use Order\PaymentsTrait;
    use Order\TrackingTrait;

    //====================================================================//
    // General Class Variables
    //====================================================================//

    /**
     * @var string
     */
    const SHIPPING_LABEL = "__Shipping";

    /**
     * @var string
     */
    const SPLASH_LABEL = "__Splash__";

    //====================================================================//
    // Magento Definition
    //====================================================================//

    /**
     * Magento Model Name
     *
     * @var string
     */
    protected static $modelName = 'sales/order';

    /**
     * Magento Model List Attributes
     *
     * @var string
     */
    protected static $listAttributes = '*';

    //====================================================================//
    // Object Definition Parameters
    //====================================================================//

    /**
     *  Object Name (Translated by Module)
     */
    protected static $NAME = "Customer Order";

    /**
     *  Object Description (Translated by Module)
     */
    protected static $DESCRIPTION = "Magento 1 Customers Order Object";

    /**
     *  Object Icon (FontAwesome or Glyph ico tag)
     */
    protected static $ICO = "fa fa-shopping-cart ";

    //====================================================================//
    // Object Synchronization Recommended Configuration
    //====================================================================//

    /**
     * Enable Creation Of New Local Objects when Not Existing
     *
     * @var bool
     */
    protected static $ENABLE_PUSH_CREATED = false;

    /**
     * Enable Update Of Existing Local Objects when Modified Remotely
     *
     * @var bool
     */
    protected static $ENABLE_PUSH_UPDATED = false;

    /**
     * Enable Delete Of Existing Local Objects when Deleted Remotely
     *
     * @var bool
     */
    protected static $ENABLE_PUSH_DELETED = false;

    /**
     * Check if this Order was Created by Splash
     *
     * @return bool
     */
    protected function isSplash(): bool
    {
        return self::SPLASH_LABEL === $this->object->getExtOrderId();
    }
}
