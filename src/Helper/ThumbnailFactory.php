<?php

namespace RichCourt\Plugin\Content\RcGallery\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

class ThumbnailFactory
{
    /** @var \GdImage|false */
    private $image;

    /** @var int */
    private $width;

    /** @var int */
    private $height;

    /** @var \GdImage|false */
    private $imageResized;

    /**
     * @param string $fileName
     */
    public function __construct(string $fileName)
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
     */
    private function correctRotation(string $fileName): void
    {
        if (!function_exists('exif_read_data')) {
            return;
        }

        switch (strtolower(pathinfo($fileName, PATHINFO_EXTENSION))) {
            case 'jpeg':
            case 'jpg':
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
     * @return \GdImage|false
     */
    private function openImage(string $file)
    {
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
        }

        return $img;
    }

    /**
     * Create a smaller version of the image to use as a thumbnail.
     *
     * @param int $newHeight
     * @param string $type
     */
    public function resizeImage(int $newHeight, string $type): void
    {
        if (strpos($type, 'hdpi') !== false) {
            $newHeight *= 2;
        }

        $newWidth = $this->calculateWidth($newHeight);

        $this->setImageResized(imagecreatetruecolor($newWidth, $newHeight));

        imagecopyresampled($this->getImageResized(), $this->getImage(), 0, 0, 0, 0, $newWidth, $newHeight, $this->getWidth(), $this->getHeight());
    }

    /**
     * @param int $newHeight
     * @return int
     */
    private function calculateWidth(int $newHeight): int
    {
        $ratio    = $this->getWidth() / $this->getHeight();
        $newWidth = $newHeight * $ratio;

        return (int) round($newWidth);
    }

    /**
     * Save image object as a file for future use.
     *
     * @param string $savePath
     * @param int $imageQuality
     * @param string $type
     */
    public function saveImage(string $savePath, int $imageQuality, string $type): void
    {
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
            throw new \Exception(Text::sprintf('PLG_CONTENT_RC_GALLERY_ERROR_THUMBNAIL_CREATE', $finalSavePath));
        }

        clearstatcache();

        if (!file_exists($finalSavePath)) {
            throw new \Exception(Text::sprintf('PLG_CONTENT_RC_GALLERY_ERROR_THUMBNAIL_SAVE', $finalSavePath));
        }
    }

    /**
     * @return \GdImage|false
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @param \GdImage|false $image
     * @return self
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
     * @return self
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
     * @return self
     */
    public function setHeight($height)
    {
        $this->height = $height;
        return $this;
    }

    /**
     * @return \GdImage|false
     */
    public function getImageResized()
    {
        return $this->imageResized;
    }

    /**
     * @param \GdImage|false $imageResized
     * @return self
     */
    public function setImageResized($imageResized)
    {
        $this->imageResized = $imageResized;

        return $this;
    }
}
