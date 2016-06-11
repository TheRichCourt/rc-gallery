<?php

/********************************************************************
Product		: RC Justified Gallery
Date		: 19/01/2016
Copyright	: Rich Court 2016
Contact		: http://www.therichcourt.com
Licence		: GNU General Public License
*********************************************************************/

// no direct access
defined( '_JEXEC' ) or die;

Class RCResize {

	private $image;
	private $width;
	private $height;
	private $imageResized;
 
	function __construct($fileName) {
		// Open up the file
		$fileName = $fileName;
		$this->image = $this->openImage($fileName);
		
		if ($this->image === false) {
			return false;
		}
		
		// Get width and height
		$this->width  = imagesx($this->image);
		$this->height = imagesy($this->image);
	}

	private function openImage($file) {
		// Get file extension
		$extension = strtolower(strrchr($file, '.'));

		switch($extension) {
			case '.jpg':
			case '.jpeg':
				$img = @imagecreatefromjpeg($file);
				break;
			case '.gif':
				$img = @imagecreatefromgif($file);
				break;
			case '.png':
				$img = @imagecreatefrompng($file);
				break;
			default:
				$img = false;
				break;
		}
		return $img;
	}

	public function resizeImage($newHeight) {
		
		$newWidth = $this->getWidth($newHeight);
		
		$this->imageResized = imagecreatetruecolor($newWidth, $newHeight);
		//imagealphablending($this->imageResized, false);
		//imagesavealpha($this->imageResized, true);
		imagecopyresampled($this->imageResized, $this->image, 0, 0, 0, 0, $newWidth, $newHeight, $this->width, $this->height);
	}

	private function getWidth($newHeight) {
		$ratio = $this->width / $this->height;
		$newWidth = $newHeight * $ratio;
		return $newWidth;
	}

	public function saveImage($savePath, $imageQuality="100") {
		// Get file extension
		$extension = strrchr($savePath, '.');
		$extension = strtolower($extension);

		switch($extension) {
			case '.jpg':
			case '.jpeg':
				if (imagetypes() & IMG_JPG) {
					$success = imagejpeg($this->imageResized, $savePath, $imageQuality);
				}
				break;

			case '.gif':
				if (imagetypes() & IMG_GIF) {
					$success = imagegif($this->imageResized, $savePath);
				}
				break;

			case '.png':
				// Scale quality from 0-100 to 0-9
				$scaleQuality = round(($imageQuality/100) * 9);

				// Invert quality setting as 0 is best, not 9
				$invertScaleQuality = 9 - $scaleQuality;

				if (imagetypes() & IMG_PNG) {
					$success = imagepng($this->imageResized, $savePath, $invertScaleQuality);
				}
				break;
				// etc

			default:
				// No extension - No save.
				break;
		}
		
		imagedestroy($this->imageResized);
	}
}