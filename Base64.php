<?php

class Base64
{
    const URL_PREFIX = 'data:image/png;base64,';

    public function createBase64StringFromImagePath($path)
    {
        return $this->createBase64String($this->resizeImage($this->openImage($path)));
    }

    private function createBase64String($img)
    {
        ob_start();
        imagepng($this->resizeImage($img));
        $contents =  ob_get_contents();
        ob_end_clean();

        return self::URL_PREFIX . base64_encode($contents);
    }

    /**
     * Create a smaller version of the image
     *
     * @param int $newHeight
     * @return void
     */
    private function resizeImage($img)
    {
        $newWidth = 6;
        $newHeight = 6;
        $newImg = imagecreatetruecolor($newWidth, $newHeight);

        imagecopyresampled($newImg, $img, 0, 0, 0, 0, $newWidth, $newHeight, imagesx($img), imagesy($img));

        return $newImg;
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
}