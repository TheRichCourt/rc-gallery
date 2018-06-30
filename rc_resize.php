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

Class RCResize
{
	/** @var image */
	private $image;

	/** @var int */
	private $width;

	/** @var int */
	private $height;

	/** @var image */
	private $imageResized;

	/**
	 * Get set up
	 *
	 * @param string $fileName
	 */
	function __construct($fileName)
	{
		// Open up the file
		$fileName = $fileName;
		$this->setImage($this->openImage($fileName));

		if ($this->image === false) {
			return false;
		}

		// Get width and height
		$this->setWidth(imagesx($this->image));
		$this->setHeight(imagesy($this->image));
	}

	/**
	 * Open the image file, and return it
	 *
	 * @param string $file
	 * @return image|false
	 */
	private function openImage($file)
	{
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
				return false;
				break;
		}
		return $img;
	}

	/**
	 * Create a smaller version of the image to use as a thumbnail
	 *
	 * @param int $newHeight
	 * @return void
	 */
	public function resizeImage($newHeight)
	{
		$newWidth = $this->calculateWidth($newHeight);

		$this->imageResized = imagecreatetruecolor($newWidth, $newHeight);
		//imagealphablending($this->imageResized, false);
		//imagesavealpha($this->imageResized, true);
		imagecopyresampled($this->imageResized, $this->image, 0, 0, 0, 0, $newWidth, $newHeight, $this->width, $this->height);
	}

	/**
	 * Work out the new width of the image
	 *
	 * @param int $newHeight
	 * @return void
	 */
	private function calculateWidth($newHeight)
	{
		$ratio = $this->width / $this->height;
		$newWidth = $newHeight * $ratio;
		return $newWidth;
	}

	/**
	 * Save image object as a file for future use
	 *
	 * @param string $savePath
	 * @param string $imageQuality
	 * @return void
	 */
	public function saveImage($savePath, $imageQuality="100")
	{
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
					$success = imagepng($this->getImageResized(), $savePath, $invertScaleQuality);
				}
				break;
				// etc

			default:
				// No extension - No save.
				break;
		}

		imagedestroy($this->getImageResized());
	}

	/**
	 * Get the value of image
	 */
	public function getImage()
	{
		return $this->image;
	}

	/**
	 * Set the value of image
	 *
	 * @return  self
	 */
	public function setImage($image)
	{
		$this->image = $image;
		return $this;
	}

	/**
	 * Get the value of width
	 */
	public function getWidth()
	{
		return $this->width;
	}

	/**
	 * Set the value of width
	 *
	 * @return  self
	 */
	public function setWidth($width)
	{
		$this->width = $width;
		return $this;
	}

	/**
	 * Get the value of height
	 */
	public function getHeight()
	{
		return $this->height;
	}

	/**
	 * Set the value of height
	 *
	 * @return  self
	 */
	public function setHeight($height)
	{
		$this->height = $height;
		return $this;
	}

	/**
	 * Get the value of imageResized
	 */
	public function getImageResized()
	{
		return $this->imageResized;
	}

	/**
	 * Set the value of imageResized
	 *
	 * @return  self
	 */
	public function setImageResized($imageResized)
	{
		$this->imageResized = $imageResized;
		return $this;
	}
}