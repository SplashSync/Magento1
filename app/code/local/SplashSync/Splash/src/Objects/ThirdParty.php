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

use Mage;
use Mage_Customer_Model_Customer;
use Splash\Models\AbstractObject;
use Splash\Models\Objects\IntelParserTrait;

/**
 * Splash PHP Module For Magento 1 - ThirdParty Object Intégration
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class ThirdParty extends AbstractObject
{
    // Splash Php Core Traits
    use IntelParserTrait;

    // Core / Common Traits
    use Core\CRUDTrait;
    use Core\ObjectListTrait;
    use Core\DataAccessTrait;
    use Core\SplashIdTrait;
    use Core\SplashOriginTrait;
    use Core\DatesTrait;

    // Customer Traits
    use ThirdParty\CRUDTrait;
    use ThirdParty\ObjectListTrait;
    use ThirdParty\CoreTrait;
    use ThirdParty\MainTrait;
    use ThirdParty\MetaTrait;

    //====================================================================//
    // Magento Definition
    //====================================================================//

    /**
     * Magento Model Name
     *
     * @var string
     */
    protected static $modelName = 'customer/customer';

    /**
     * Magento Model List Attributes
     *
     * @var array
     */
    protected static $listAttributes = array('entity_id', 'firstname', 'lastname', 'email', 'is_active');

    //====================================================================//
    // Object Definition Parameters
    //====================================================================//

    /**
     * Object Name (Translated by Module)
     */
    protected static $NAME = "ThirdParty";

    /**
     * Object Description (Translated by Module)
     */
    protected static $DESCRIPTION = "Magento 1 Customer Object";

    /**
     * Object Icon (FontAwesome or Glyph ico tag)
     */
    protected static $ICO = "fa fa-user";

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
}
