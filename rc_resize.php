<?php

defined( '_JEXEC' ) or die;

Class RCResize
{
	/** @var resource */
	private $image;

	/** @var int */
	private $width;

	/** @var int */
	private $height;

	/** @var resource */
	private $imageResized;

	/**
	 * @param string $fileName
	 */
	function __construct($fileName)
	{
		$this->setImage($this->openImage($fileName));

		if ($this->getImage() === false) {
			return false;
		}

		$this->setWidth(imagesx($this->getImage()));
		$this->setHeight(imagesy($this->getImage()));
	}

	/**
	 * @param string $file
	 * @return resource|false
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
			case '.webp':
				$img = @imagecreatefromwebp($file);
				break;
			case '.bmp':
				$img = @imagecreatefrombmp($file);
				break;
			case '.wbmp':
				$img = @imagecreatefromwbmp($file);
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
	public function resizeImage($newHeight, $type)
	{
		if (strpos($type, 'hdpi') !== false) {
			$newHeight *= 2.4;
		} else {
			$newHeight *= 1.2;
		}

		$newWidth = $this->calculateWidth($newHeight);

		$this->setImageResized(imagecreatetruecolor($newWidth, $newHeight));

		imagecopyresampled($this->getImageResized(), $this->getImage(), 0, 0, 0, 0, $newWidth, $newHeight, $this->getWidth(), $this->getHeight());
	}

	/**
	 * @param int $newHeight
	 * @return void
	 */
	private function calculateWidth($newHeight)
	{
		$ratio = $this->getWidth() / $this->getHeight();
		$newWidth = $newHeight * $ratio;
		return $newWidth;
	}

	/**
	 * Save image object as a file for future use. Optionally save as WebP as well.
	 *
	 * @param string $savePath
	 * @param string $imageQuality
	 * @return void
	 */
	public function saveImage($savePath, $imageQuality, $type)
	{
		// Get file extension
		$extension = strrchr($savePath, '.');
		$extension = strtolower($extension);

		if (strpos($type, 'webp') !== false) {
			$webpSavePath = str_replace($extension, '.webp', $savePath);
			$success = imagewebp($this->getImageResized(), $webpSavePath, $imageQuality);
		} else {
			$jpgSavePath = str_replace($extension, '.jpg', $savePath);
			$success = imagejpeg($this->getImageResized(), $jpgSavePath, $imageQuality);
		}

		imagedestroy($this->getImageResized());
	}

	/**
	 * @return resource
	 */
	public function getImage()
	{
		return $this->image;
	}

	/**
	 * @param resource $image
	 * @return  self
	 */
	public function setImage($image)
	{
		$this->image = $image;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getWidth()
	{
		return $this->width;
	}

	/**
	 * @param int $width
	 * @return  self
	 */
	public function setWidth($width)
	{
		$this->width = $width;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getHeight()
	{
		return $this->height;
	}

	/**
	 * @param int $height
	 * @return  self
	 */
	public function setHeight($height)
	{
		$this->height = $height;
		return $this;
	}

	/**
	 * @return resource
	 */
	public function getImageResized()
	{
		return $this->imageResized;
	}

	/**
	 * @param resource $imageResized
	 * @return  self
	 */
	public function setImageResized($imageResized)
	{
		$this->imageResized = $imageResized;

		return $this;
	}
}