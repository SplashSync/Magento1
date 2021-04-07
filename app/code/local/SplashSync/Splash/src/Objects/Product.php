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
use Splash\Models\Objects\ImagesTrait;
use Splash\Models\Objects\IntelParserTrait;
use Splash\Models\Objects\PricesTrait;

/**
 * Splash PHP Module For Magento 1 - Product Object Intégration
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class Product extends AbstractObject
{
    // Splash Php Core Traits
    use IntelParserTrait;
    use PricesTrait;
    use ImagesTrait;

    // Core / Common Traits
    use Core\CRUDTrait;
    use Core\ObjectListTrait;
    use Core\DataAccessTrait;
    use Core\SplashIdTrait;
    use Core\SplashOriginTrait;
    use Core\DatesTrait;
    use Core\PricesTrait;
    use Core\MultiLangTrait;

    // Product Traits
    use Product\CRUDTrait;
    use Product\ObjectListTrait;
    use Product\CoreTrait;
    use Product\MainTrait;
    use Product\DescTrait;
    use Product\ImagesTrait;
    use Product\StocksTrait;
    use Product\MetaTrait;
    use Product\ExtrasTrait;

    //====================================================================//
    // Magento Definition
    //====================================================================//

    /**
     * Magento Model Name
     *
     * @var string
     */
    protected static $modelName = 'catalog/product';

    /**
     * Magento Model List Attributes
     *
     * @var array
     */
    protected static $listAttributes = array('entity_id', 'sku', 'name', 'status', 'price');

    //====================================================================//
    // Object Definition Parameters
    //====================================================================//

    /**
     *  Object Name (Translated by Module)
     */
    protected static $NAME = "Product";

    /**
     *  Object Description (Translated by Module)
     */
    protected static $DESCRIPTION = "Magento 1 Product Object";

    /**
     *  Object Icon (FontAwesome or Glyph ico tag)
     */
    protected static $ICO = "fa fa-product-hunt";

    //====================================================================//
    // Object Synchronization Recommended Configuration
    //====================================================================//

    /**
     * Enable Creation Of New Local Objects when Not Existing
     *
     * @var bool
     */
    protected static $ENABLE_PUSH_CREATED = false;
}
