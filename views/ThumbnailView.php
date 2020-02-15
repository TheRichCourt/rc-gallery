<?php

defined('_JEXEC') or die;

class ThumbnailView
{
    /** @var stdClass */
    private $rcParams;

    /** @var string */
    private $title;

    /** @var string */
    private $targetUrl;

    /** @var int */
    private $width;

    /** @var int */
    private $height;

    /** @var array */
    private $images = [];

    /** @var int */
    private $galleryNumber;

    /** @var int */
    private $imageNumber;

    /** @var DOMDocument */
    private $dom;

    /** @var bool */
    private $thumbsExist;

    /**
     * @param stdCLass $rcParams
     * @param string $title
     * @param string $targetUrl
     * @param int $width
     * @param int $height
     * @param array $images
     * @param int $galleryNumber
     * @param int $imageNumber
     * @param bool $thumbsExist
     */
    public function __construct(stdClass $rcParams, $title, $targetUrl, $width, $height, array $images, $galleryNumber, $imageNumber, $thumbsExist)
    {
        $this
            ->setRcParams($rcParams)
            ->setTitle($title)
            ->setTargetUrl($targetUrl)
            ->setWidth($width)
            ->setHeight($height)
            ->setImages($images)
            ->setGalleryNumber($galleryNumber)
            ->setImageNumber($imageNumber)
            ->setThumbsExist($thumbsExist)
            ->setDom(new DOMDocument())
        ;
    }

    /**
     * @return string
     */
    public function build()
    {
        $linkElem = $this->buildLink();
        $divElem = $this->getDom()->createElement('div');
        $divElem->setAttribute('class', 'rc_galleryimg_container');
        $divElem->setAttribute('data-thumbsexist', $this->getThumbsExist() ? 'true' : 'false');
        $divElem->setAttribute('id', str_replace(' ', '_', sprintf(
            'rc_%s_%d_%d',
            $this->getTitle(),
            $this->getGalleryNumber(),
            $this->getImageNumber()
        )));

        $pictureElem = $this->buildPicture();

        $divElem->appendChild($pictureElem);

        if ($this->getRcParams()->imageTitle == 1 || $this->getRcParams()->imageTitle == 2) {
            $divElem->appendChild($this->buildTitle());
        }

        $shadowboxOption = $this->getRcParams()->shadowboxoption;

        if ($shadowboxOption == 0 || $shadowboxOption == 1 || $shadowboxOption == 3) {
            $linkElem = $this->buildLink();
            $linkElem->appendChild($divElem);
            $this->getDom()->appendChild($linkElem);
            return $this->getDom()->saveHTML();
        }

        $this->getDom()->appendChild($divElem);
        return $this->getDom()->saveHTML();
    }

    private function buildLink()
    {
        $elem = $this->getDom()->createElement('a');
        $elem->setAttribute('href', $this->getTargetUrl());
        $elem->setAttribute('rel', 'shadowbox[rc_gallery]');
        $elem->setAttribute('data-imageTitle', $this->getTitle());

        return $elem;
    }

    private function buildPicture()
    {
        $elem = $this->getDom()->createElement('picture');

        foreach ($this->getImages() as $image) {
            $elem->appendChild($this->buildSource($image));
        }

        $elem->appendChild($this->buildImage($this->getImages()['jpg']['srcset']));

        return $elem;
    }

    /**
     * @param array $image
     * @return DOMElement
     */
    private function buildSource(array $image)
    {
        $elem = $this->getDom()->createElement('source');

        foreach ($image as $attributeName => $attributeValue) {
            if ($attributeName == 'media' && $attributeValue == '') {
                continue;
            }

            if ($attributeName == 'srcset') {
                $elem->setAttribute("data-{$attributeName}", $attributeValue);
                continue;
            }

            $elem->setAttribute($attributeName, $attributeValue);
        }

        return $elem;
    }

    /**
     * using the 'data-___' attributes so the image can be lazy loaded
     *
     * @param string $src
     * @return DOMElement
     */
    private function buildImage($src)
    {
        $elem = $this->getDom()->createElement('img');
        $elem->setAttribute('class', 'rc_galleryimg');
        $elem->setAttribute('data-src', $src);
        $elem->setAttribute('style', sprintf(
            "margin: %dpx;",
            $this->getRcParams()->imagemargin
        ));

        if ($this->getRcParams()->usetitleasalt) {
            $elem->setAttribute('data-alt', $this->getTitle());
        }

        $elem->setAttribute('alt', '');

        $elem->setAttribute('data-width', $this->getWidth());
        $elem->setAttribute('data-height', $this->getHeight());

        return $elem;
    }

    /**
     * @return DOMElement
     */
    private function buildTitle()
    {
        $elem = $this->getDom()->createElement('span', htmlspecialchars($this->getTitle()));

        $opacity = $this->getRcParams()->imageTitle === 2
            ? 'opacity:1 !important;'
            : ''
        ;

        $elem->setAttribute('style', sprintf(
            "margin: %dpx; width: calc(100%s - %dpx); %s",
            $this->getRcParams()->imagemargin,
            "%",
            $this->getRcParams()->imagemargin * 2,
            $opacity
        ));

        return $elem;
    }

    /**
     * @return stdClass
     */
    public function getRcParams()
    {
        return $this->rcParams;
    }

    /**
     * @param stdClass $rcParams
     * @return self
     */
    public function setRcParams(stdClass $rcParams)
    {
        $this->rcParams = $rcParams;

        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return self
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string
     */
    public function getTargetUrl()
    {
        return $this->targetUrl;
    }

    /**
     * @param string $targetUrl
     * @return self
     */
    public function setTargetUrl($targetUrl)
    {
        $this->targetUrl = $targetUrl;

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
     * @return array
     */
    public function getImages()
    {
        return $this->images;
    }

    /**
     * @param array $images
     * @return self
     */
    public function setImages(array $images)
    {
        $this->images = $images;

        return $this;
    }

    /**
     * @return DOMDocument
     */
    public function getDom()
    {
        return $this->dom;
    }

    /**
     * @param DOMDocument
     * @return  self
     */
    public function setDom(DOMDocument $dom)
    {
        $this->dom = $dom;

        return $this;
    }

    /**
     * @return int
     */
    public function getGalleryNumber()
    {
        return $this->galleryNumber;
    }

    /**
     * @param int $galleryNumber
     * @return  self
     */
    public function setGalleryNumber($galleryNumber)
    {
        $this->galleryNumber = $galleryNumber;

        return $this;
    }

    /**
     * @return int
     */
    public function getImageNumber()
    {
        return $this->imageNumber;
    }

    /**
     * @param int $imageNumber
     * @return self
     */
    public function setImageNumber($imageNumber)
    {
        $this->imageNumber = $imageNumber;

        return $this;
    }

    /**
     * @return bool
     */
    public function getThumbsExist()
    {
        return $this->thumbsExist;
    }

    /**
     * @param bool $thumbsExist
     * @return self
     */
    public function setThumbsExist($thumbsExist)
    {
        $this->thumbsExist = $thumbsExist;

        return $this;
    }
}
