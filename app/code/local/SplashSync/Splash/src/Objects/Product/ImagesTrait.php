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

namespace Splash\Local\Objects\Product;

use ArrayObject;
use Exception;
use Mage;
use Splash\Core\SplashCore      as Splash;

/**
 * Magento 1 Products Images Fields Access
 */
trait ImagesTrait
{
    /**
     * @var int
     */
    private $imgPosition = 0;

    /**
     * Build Fields using FieldFactory
     *
     * @return void
     */
    protected function buildImagesFields(): void
    {
        //====================================================================//
        // Product Images List
        $this->fieldsFactory()->create(SPL_T_IMG)
            ->identifier("image")
            ->inList("images")
            ->name("Images")
            ->group("Images")
            ->microData("http://schema.org/Product", "image")
        ;
        //====================================================================//
        // Product Images => Position
        $this->fieldsFactory()->create(SPL_T_INT)
            ->identifier("position")
            ->inList("images")
            ->name("Img. Position")
            ->microData("http://schema.org/Product", "positionImage")
            ->group("Images")
            ->isReadOnly()
            ->isNotTested()
        ;
        //====================================================================//
        // Product Images => Is Cover
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->Identifier("cover")
            ->InList("images")
            ->name("Img. Cover")
            ->microData("http://schema.org/Product", "isCover")
            ->group("Images")
            ->isNotTested()
        ;
        //====================================================================//
        // Product Images => Is Visible Image
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->identifier("visible")
            ->inList("images")
            ->name("Img. Visible")
            ->microData("http://schema.org/Product", "isVisibleImage")
            ->group("Images")
            ->isNotTested()
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
    protected function getImagesFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // Check if List field & Init List Array
        $fieldId = self::lists()->initOutput($this->out, "images", $fieldName);
        if (!$fieldId) {
            return;
        }
        //====================================================================//
        // Load Object Images List
        $objectImagesList = $this->object->getMediaGallery();
        //====================================================================//
        // Images List is Empty
        if (!isset($objectImagesList["images"]) || !count($objectImagesList["images"])) {
            return;
        }
        //====================================================================//
        // Walk on Images List
        foreach ($objectImagesList["images"] as $index => $mageImage) {
            //====================================================================//
            // Collect Data
            $value = $this->getImageGalleryValue($fieldId, $mageImage);
            //====================================================================//
            // Do Fill List with Data
            self::lists()->insert($this->out, "images", $fieldName, $index, $value);
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
    protected function setImagesFields(string $fieldName, $data): void
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            case 'images':
                $this->setImageGallery($data);

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }

    /**
     * Return Product Image Array from Prestashop Object Class
     *
     * @param string $fieldId
     * @param array  $mageImage
     *
     * @return null|mixed
     */
    private function getImageGalleryValue(string $fieldId, array $mageImage)
    {
        //====================================================================//
        // Load Object Images Media Config
        static $mediaCfg;
        if (!isset($mediaCfg)) {
            $mediaCfg = $this->object->getMediaConfig();
        }
        //====================================================================//
        // READ Field
        switch ($fieldId) {
            case 'position':
                return $mageImage["position"];
            case 'cover':
                return 1 == $mageImage["position"];
            case 'visible':
                return empty($mageImage["disabled"]);
            case 'image':
                return self::images()->encode(
                    // Image Legend/Label
                    $mageImage["label"] ? $mageImage["label"] : $this->object->getSku(),
                    // Image File Filename
                    basename($mageImage["file"]),
                    // Image Server Path (Without Filename)
                    dirname($mediaCfg->getMediaPath($mageImage['file'])).DS,
                    // Image Public Url
                    $mediaCfg->getMediaUrl($mageImage['file'])
                );
            default:
                return null;
        }
    }

    /**
     * Update Product Image Array from Server Data
     *
     * @param array|ArrayObject $data Input Image List for Update
     *
     * @return bool
     */
    private function setImageGallery($data): bool
    {
        //====================================================================//
        // Safety Check
        if (!is_array($data) && !is_a($data, "ArrayObject")) {
            return false;
        }
        //====================================================================//
        // Load Current Object Images List
        //====================================================================//

        //====================================================================//
        // Load Object Images List
        $objectImagesList = $this->object->getMediaGallery();

        //====================================================================//
        // If New PRODUCT => Reload Product to get Stock Item
        if (empty($objectImagesList)) {
            //====================================================================//
            // Media gallery initialization
            $this->object->setMediaGallery(array('images' => array(), 'values' => array()));
            //====================================================================//
            // Load Object Images List
            $objectImagesList = $this->object->getMediaGallery();
        }
        //====================================================================//
        // UPDATE IMAGES LIST
        //====================================================================//
        $this->imgPosition = 0;
        //====================================================================//
        // Given List Is Not Empty
        foreach ($data as $inValue) {
            $this->setImage($objectImagesList, $inValue);
        }

        //====================================================================//
        // If Remaining Image List Is Not Empty => Clear Remaining Local Images
        //====================================================================//
        if (isset($objectImagesList["images"]) && !empty($objectImagesList["images"])) {
            $this->cleanUpImages($objectImagesList["images"]);
        }

        return true;
    }

    /**
     * Update Product Image from Server Data
     *
     * @param mixed $objectImagesList
     * @param mixed $inValue
     */
    private function setImage(&$objectImagesList, $inValue): void
    {
        if (!isset($inValue["image"]) || empty($inValue["image"])) {
            return;
        }
        $this->imgPosition++;
        $inImage = $inValue["image"];
        //====================================================================//
        // Search For Image In Current List
        $imageFound = false;
        foreach ($objectImagesList["images"] as $key => $image) {
            //====================================================================//
            // Compute Md5 CheckSum for this Image
            $checkSum = md5_file($this->object->getMediaConfig()->getMediaPath($image['file']));
            //====================================================================//
            // If CheckSum are Different => Continue
            if ($inImage["md5"] !== $checkSum) {
                continue;
            }
            //====================================================================//
            // If Positions are Different => Continue
            if ($this->imgPosition !== $image['position']) {
                continue;
            }

            $imageFound = $image;
            //====================================================================//
            // If Object Found, Unset from Current List
            unset($objectImagesList["images"][$key]);

            break;
        }

        //====================================================================//
        // If found => Next
        if ($imageFound) {
            return;
        }
        //====================================================================//
        // If Not found, Add this object to list
        $this->addImage($inImage);
    }

    /**
     * Import a Product Image from Server Data
     *
     * @param array $imgArray Splash Image Definition Array
     *
     * @return bool
     */
    private function addImage($imgArray): bool
    {
        //====================================================================//
        // Read Image Raw Data From Splash Server
        $newImageFile = Splash::file()->getFile($imgArray["file"], $imgArray["md5"]);
        //====================================================================//
        // File Imported => Write it Here
        if (false == $newImageFile) {
            return false;
        }
        //====================================================================//
        // Write Image On Local Import Folder
        $path = Mage::getBaseDir('media').DS.'import'.DS ;
        $filename = isset($imgArray["filename"]) ? $imgArray["filename"] : ($newImageFile["name"]);
        Splash::file()->writeFile($path, $filename, $newImageFile["md5"], $newImageFile["raw"]);
        //====================================================================//
        // Create Image in Product Media Gallery
        if (file_exists($path.$filename)) {
            try {
                $this->object->addImageToMediaGallery($path.$filename, array('image'), true, false);
                $this->needUpdate();
            } catch (Exception $e) {
                Splash::log()->warTrace("Image Path (".$path.$filename.").");

                return Splash::log()->errTrace("Unable to add image (".$e->getMessage().").");
            }
        }

        return true;
    }

    /**
     * Remaining Image List Is Not Empty => Clear Remaining Local Images
     *
     * @param array $mageImgArray Magento Product Image Gallery Array
     *
     * @return bool
     */
    private function cleanUpImages($mageImgArray): bool
    {
        //====================================================================//
        // Load Images Gallery Object
        $productAttributes = $this->object->getTypeInstance()->getSetAttributes();
        if (!isset($productAttributes['media_gallery'])) {
            return true;
        }
        $imageGallery = $productAttributes['media_gallery'];
        //====================================================================//
        // Iterate All Remaining Images
        foreach ($mageImgArray as $image) {
            //====================================================================//
            // Delete Image Object
            if ($imageGallery->getBackend()->getImage($this->object, $image['file'])) {
                $imageGallery->getBackend()->removeImage($this->object, $image['file']);
                $this->needUpdate();
            }
        }

        return true;
    }
}
