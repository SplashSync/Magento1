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

namespace Splash\Local\Objects\Product;

// Splash Namespaces
use Splash\Core\SplashCore      as Splash;

// Magento Namespaces
use Mage;
use Mage_Core_Exception;

/**
 * @abstract    Magento 1 Products Images Fields Access
 */
trait ImagesTrait {
    
    
    
    
    /**
    *   @abstract     Build Fields using FieldFactory
    */
    private function buildImagesFields() {
        
        //====================================================================//
        // Product Cover Image Position
//        $this->FieldsFactory()->Create(SPL_T_INT)
//                ->Identifier("cover_image")
//                ->Name($this->spl->l("Cover"))
//                ->MicroData("http://schema.org/Product","coverImage")
//                ->NotTested();
        
        //====================================================================//
        // Product Images List
        $this->FieldsFactory()->Create(SPL_T_IMG)
                ->Identifier("image")
                ->InList("images")
                ->Name("Images")
                ->Group("Images")
                ->MicroData("http://schema.org/Product","image");
        
        return;
    }

    /**
     *  @abstract     Read requested Field
     * 
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     * 
     *  @return         none
     */
    private function getImagesFields($Key,$FieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($FieldName)
        {
            
                case 'image@images':     
                    $this->getImageGallery();
                    break;
                case 'cover_image':
                    $CoverImage             = Image::getCover((int) $this->ProductId);
                    $this->Out[$FieldName]  = isset($CoverImage["position"])?$CoverImage["position"]:0;
                    break;
                
            default:
                return;
        }
        
        if (!is_null($Key)) {
            unset($this->In[$Key]);
        }
    }
    
    
    /**
     *  @abstract     Write Given Fields
     * 
     *  @param        string    $FieldName              Field Identifier / Name
     *  @param        mixed     $Data                   Field Data
     * 
     *  @return         none
     */
    private function setImagesFields($FieldName,$Data) 
    {
        //====================================================================//
        // WRITE Field
        switch ($FieldName)
        {

            case 'images':
                $this->setImageGallery($Data);
                break;
            case 'cover_image':
//                //====================================================================//
//                // Read Product Images List
//                $ObjectImagesList   =   Image::getImages($this->LangId,$this->ProductId);
//                //====================================================================//
//                // Disable Wrong Images Cover
//                foreach ($ObjectImagesList as $ImageArray) {
//                    //====================================================================//
//                    // Is Cover Image but shall not
//                    if ($ImageArray["cover"] && ($ImageArray["position"] != $Data) ) {
//                        $ObjectImage = new Image($ImageArray["id_image"],  $this->LangId);
//                        $ObjectImage->cover     =   0;
//                        $this->update           =   True;
//                        $ObjectImage->update();
//                    }
//                }
//                //====================================================================//
//                // Enable New Image Cover
//                foreach ($ObjectImagesList as $ImageArray) {
//                    //====================================================================//
//                    // Is Cover Image but shall not
//                    if (!$ImageArray["cover"] && ($ImageArray["position"] == $Data) ) {
//                        $ObjectImage = new Image($ImageArray["id_image"],  $this->LangId);
//                        $ObjectImage->cover     =   1;
//                        $this->update           =   True;
//                        $ObjectImage->update();
//                    }
//                }
                break;

                
            default:
                return;
        }
        unset($this->In[$FieldName]);
    }
    
    
    /**
     *   @abstract     Return Product Image Array from Prestashop Object Class
     */
    public function getImageGallery() 
    {
        //====================================================================//
        // Load Object Images List
        $ObjectImagesList   =   $this->Object->getMediaGallery();
        $Media              =   $this->Object->getMediaConfig(); 
        //====================================================================//
        // Init Images List
        if ( !isset($this->Out["images"]) ) {
            $this->Out["images"] = array();
        }
        //====================================================================//
        // Images List is Empty
        if ( !isset($ObjectImagesList["images"]) || !count ($ObjectImagesList["images"]) ) {
            return True;
        }
        //====================================================================//
        // Create Images List
        foreach ($ObjectImagesList["images"] as $key => $Image) {
            //====================================================================//
            // Init Image List Item
            if ( !isset($this->Out["images"][$key]) ) {
                $this->Out["images"][$key] = array();
            }
            //====================================================================//
            // Insert Image in Output List
            $this->Out["images"][$key]["image"] = $this->Images()->Encode(
                    $Image["label"]?$Image["label"]:$this->Object->getSku(),// Image Legend/Label
                    basename($Image["file"]),                               // Image File Filename
                    dirname($Media->getMediaPath($Image['file'])) . DS,     // Image Server Path (Without Filename)
                    $Media->getMediaUrl($Image['file'])                     // Image Public Url 
            );
        }
            
        return True;
    }
         
     
    /**
    *   @abstract     Update Product Image Array from Server Data
    *   @param        array   $Data             Input Image List for Update    
    */
    public function setImageGallery($Data) 
    {
        //====================================================================//
        // Safety Check
        if ( !is_array($Data) && !is_a($Data, "ArrayObject")) { 
            return False; 
        }
        //====================================================================//
        // Load Current Object Images List
        //====================================================================//

        //====================================================================//
        // Load Object Images List
        $ObjectImagesList   =   $this->Object->getMediaGallery();
        
        //====================================================================//
        // If New PRODUCT => Reload Product to get Stcok Item 
        if ( empty($ObjectImagesList) )
        {
            //====================================================================//
            // Media gallery initialization
            $this->Object->setMediaGallery (array('images'=>array (), 'values'=>array ())); 
            //====================================================================//
            // Load Object Images List
            $ObjectImagesList   =   $this->Object->getMediaGallery();
        }
        //====================================================================//
        // UPDATE IMAGES LIST
        //====================================================================//
        $this->ImgPosition = 0;
        //====================================================================//
        // Given List Is Not Empty
        foreach ($Data as $InValue) {
            if ( !isset($InValue["image"]) || empty ($InValue["image"]) ) {
                continue;
            }
            $this->ImgPosition++;
            $InImage = $InValue["image"];
            //====================================================================//
            // Search For Image In Current List
            $ImageFound = False;
            foreach ($ObjectImagesList["images"] as $key => $Image) {
                //====================================================================//
                // Compute Md5 CheckSum for this Image 
                $CheckSum = md5_file( $this->Object->getMediaConfig()->getMediaPath($Image['file']) );
                //====================================================================//
                // If CheckSum are Different => Continue
                if ( $InImage["md5"] !== $CheckSum ) {
                    continue;
                }
                //====================================================================//
                // If Positions are Different => Continue
                if ( $this->ImgPosition !== $Image['position'] ) {
                    continue;
                }
                
                $ImageFound = $Image;
                //====================================================================//
                // If Object Found, Unset from Current List
                unset ($ObjectImagesList["images"][$key]);
                break;
            }

            //====================================================================//
            // If found => Next
            if ( $ImageFound ) {
                continue;
            }
            //====================================================================//
            // If Not found, Add this object to list
            $this->addImage($InImage);
        }
        
        //====================================================================//
        // If Remaining Image List Is Not Empty => Clear Remaining Local Images
        //====================================================================//
        if ( isset($ObjectImagesList["images"]) && !empty($ObjectImagesList["images"]) ) {
            $this->cleanUpImages($ObjectImagesList["images"]);
        }
        
        return True;
    }
            
    /**
    *   @abstract     Import a Product Image from Server Data
    *   @param        array   $ImgArray             Splash Image Definition Array    
    */
    public function addImage($ImgArray) 
    {
        //====================================================================//
        // Read Image Raw Data From Splash Server
        $NewImageFile    =   Splash::File()->GetFile($ImgArray["file"],$ImgArray["md5"]);
        //====================================================================//
        // File Imported => Write it Here
        if ( $NewImageFile == False ) {
            return False;
        }
        //====================================================================//
        // Write Image On Local Import Folder
        $Path       = Mage::getBaseDir('media') . DS . 'import' . DS ;
        $Filename  = isset($ImgArray["filename"]) ? $ImgArray["filename"] : ( $NewImageFile["name"] );
        Splash::File()->WriteFile($Path,$Filename,$NewImageFile["md5"],$NewImageFile["raw"]); 
        //====================================================================//
        // Create Image in Product Media Gallery
        if ( file_exists($Path . $Filename) ) {
            try {
                $this->Object->addImageToMediaGallery($Path . $Filename, array('image'), true, false);
                $this->update = True;
            } catch (Exception $e) {
                Splash::Log()->War("ErrLocalTpl",__CLASS__,__FUNCTION__,"Image Path (" . $Path . $Filename);
                return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,"Unable to add image (" . $e->getMessage() . ").");
            } catch (Mage_Core_Exception $e) {
                Splash::Log()->War("ErrLocalTpl",__CLASS__,__FUNCTION__,"Image Path (" . $Path . $Filename);
                return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,"Unable to add image (" . $e->getMessage() . ").");
            }
        }
        return True;
    }
    
    /**
    *   @abstract   Remaining Image List Is Not Empty => Clear Remaining Local Images
    *   @param      array   $MageImgArray             Magento Product Image Gallery Array   
    */
    public function cleanUpImages($MageImgArray) 
    {
        //====================================================================//
        // Load Images Gallery Object
        $ProductAttributes = $this->Object->getTypeInstance()->getSetAttributes();
        if (!isset($ProductAttributes['media_gallery'])) {
            return True;
        }
        $ImageGallery = $ProductAttributes['media_gallery'];
        //====================================================================//
        // Iterate All Remaining Images 
        foreach ($MageImgArray as $Image) {
            //====================================================================//
            // Delete Image Object
            if ($ImageGallery->getBackend()->getImage($this->Object, $Image['file'])) {
                $ImageGallery->getBackend()->removeImage($this->Object, $Image['file']);
                $this->update = True;
            }
        }
        return True;
    }      
    
}
