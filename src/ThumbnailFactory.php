<?php

defined('_JEXEC') or die;

class ThumbnailFactory
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
    public function __construct($fileName)
    {
        $this->setImage($this->openImage($fileName));

        if ($this->getImage() === false) {
            return;
        }

        $this->correctRotation($fileName);
        $this->setWidth(imagesx($this->getImage()));
        $this->setHeight(imagesy($this->getImage()));
    }

    /**
     * Some images are only rotated by their EXIF. This corrects that, so that their pixels are actually rotated.
     *
     * @param string $fileName
     * @return void
     */
    private function correctRotation($fileName)
    {
        if (!function_exists('exif_read_data')) {
            return;
        }

        switch (strtolower(pathinfo($fileName, PATHINFO_EXTENSION))) {
            case "jpeg":
            case "jpg":
                // Okay, I don't like suppressing warnings, but there are bugs in some PHP versions, and so I don't have much choice
                // see https://stackoverflow.com/questions/37352371/php-exif-read-data-illegal-ifd-size
                $exif = @exif_read_data($fileName);

                if (!$exif) {
                    return;
                }

                if (!isset($exif['Orientation'])) {
                    return;
                }

                switch ($exif['Orientation']) {
                    case 3:
                        $this->setImage(imagerotate($this->getImage(), 180, 0));
                        break;
                    case 6:
                        $this->setImage(imagerotate($this->getImage(), -90, 0));
                        break;
                    case 8:
                        $this->setImage(imagerotate($this->getImage(), 90, 0));
                        break;
                }

                break;
        }
    }

    /**
     * @param string $file
     * @return resource|false
     */
    private function openImage($file)
    {
        // Get file extension
        $extension = strtolower(strrchr($file, '.'));

        switch ($extension) {
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
        // double resolution for hdpi displays
        if (strpos($type, 'hdpi') !== false) {
            $newHeight *= 2;
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
            $finalSavePath = str_replace($extension, '.webp', $savePath);
            $success = imagewebp($this->getImageResized(), $finalSavePath, $imageQuality);
        } else {
            $finalSavePath = str_replace($extension, '.jpg', $savePath);
            $success = imagejpeg($this->getImageResized(), $finalSavePath, $imageQuality);
        }

        imagedestroy($this->getImageResized());

        if (!$success) {
            throw new Exception("Thumbnail image {$finalSavePath} couldn't be created. Check the original image file for problems.");
        }

        clearstatcache();

        if (!file_exists($finalSavePath)) {
            throw new Exception("Image {$finalSavePath} couldn't be saved. Check permissions on that directory.");
        }
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
