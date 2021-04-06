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

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
// phpcs:disable Squiz.Classes.ValidClassName

/**
 * Config category source
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 *
 * @SuppressWarnings(PHPMD.CamelCaseClassName)
 * @SuppressWarnings(PHPMD.LongClassName)
 */
class SplashSync_Splash_Model_System_Config_Source_Product_Attributeset
{
    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        $entityTypeId = null;
        //====================================================================//
        // Get Products Entity Type Id
        /** @var Mage_Eav_Model_Entity_Type $eavModel */
        $eavModel = Mage::getResourceModel('eav/entity_type_collection');
        /** @phpstan-ignore-next-line */
        foreach ($eavModel->load() as $entityType) {
            //====================================================================//
            // Search for Products Entity Type Name
            if ('catalog/product' === $entityType->getEntityModel()) {
                $entityTypeId = $entityType->getEntityTypeId();
            }
        }
        //====================================================================//
        // Load List Of Products Attributes Set
        /** @phpstan-ignore-next-line */
        $attributeSetCollection = Mage::getResourceModel('eav/entity_attribute_set_collection')->load();
        //====================================================================//
        // Iterate all set
        $select = array();
        foreach ($attributeSetCollection as $attributeSet) {
            //====================================================================//
            // Verify This set is for Products
            if ($attributeSet->getEntityTypeId() != $entityTypeId) {
                continue;
            }
            //====================================================================//
            // Add Attribute Set to Select List
            $select[] = array(
                'value' => $attributeSet->getEntityTypeId(),
                'label' => $attributeSet->getAttributeSetName(),
            );
        }
        //====================================================================//
        // Return Sets List
        return $select;
    }
}
