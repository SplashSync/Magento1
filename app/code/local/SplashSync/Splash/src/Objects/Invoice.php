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
// Magento Namespaces
use Splash\Models\Objects\SimpleFieldsTrait;

/**
 * Splash PHP Module For Magento 1 - Invoice Object Intégration
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class Invoice extends AbstractObject
{
    // Splash Php Core Traits
    use IntelParserTrait;
    use ObjectsTrait;
    use PricesTrait;
    use ListsTrait;
    use SimpleFieldsTrait;

    // Core / Common Traits
    use Core\CRUDTrait;
    use Core\ObjectListTrait;
    use Core\DataAccessTrait;

    // Invoices Traits
    use Invoice\CRUDTrait;
    use Invoice\ObjectListTrait;
    use Invoice\CoreTrait;
    use Invoice\MainTrait;
    use Invoice\ItemsTrait;

    // Order Traits
    use Order\PaymentsTrait;

    //====================================================================//
    // Magento Definition
    //====================================================================//

    /**
     * Magento Model Name
     *
     * @var string
     */
    protected static $modelName = 'sales/order_invoice';

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
    protected static $NAME = "Customer Invoice";

    /**
     *  Object Description (Translated by Module)
     */
    protected static $DESCRIPTION = "Magento 1 Customers Invoice Object";

    /**
     *  Object Icon (FontAwesome or Glyph ico tag)
     */
    protected static $ICO = "fa fa-money";

    //====================================================================//
    // Object Synchronization Limitations
    //====================================================================//

    /**
     * Allow Creation Of New Local Objects
     *
     * @var bool
     */
    protected static $ALLOW_PUSH_CREATED = false;

    /**
     * Allow Update Of Existing Local Objects
     *
     * @var bool
     */
    protected static $ALLOW_PUSH_UPDATED = false;

    /**
     * Allow Delete Of Existing Local Objects
     *
     * @var bool
     */
    protected static $ALLOW_PUSH_DELETED = false;

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

    //====================================================================//
    // Class Main Functions
    //====================================================================//

    /**
     * Check if this Invoice was Created by Splash
     *
     * @return bool
     */
    protected function isSplash(): bool
    {
        // Splash Cannot Create Invoices
        return false;
    }
}
