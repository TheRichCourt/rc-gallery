<?php

namespace RichCourt\Plugin\Content\RcGallery\View;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\WebAsset\WebAssetManager;

class GalleryView
{
    /** @var string */
    private $html;

    /** @var \stdClass */
    private $rcParams;

    /** @var int */
    private $imageNumber = 1;

    /** @var int */
    private $galleryNumber = 0;

    /** @var WebAssetManager */
    private $wa;

    /**
     * @param int $galleryNo
     * @param \stdClass $rcParams
     * @param WebAssetManager $wa
     */
    public function __construct(int $galleryNo, \stdClass $rcParams, WebAssetManager $wa)
    {
        $this->setRcParams($rcParams);
        $this->setWa($wa);
        $this->galleryNumber = $galleryNo;

        $galleryParams = ' data-rooturl="' . Uri::root() . '"'
            . ' data-startheight="' . $this->getRCParams()->minrowheight . '"'
            . ' data-marginsize="' . $this->getRCParams()->imagemargin . '"';

        $layout = $this->getRcParams()->layout === null
            ? ''
            : $this->getRcParams()->layout;

        $galleryClass = strtolower($layout);

        $this->html = '<div id="rc_gallery_' . $this->getGalleryNumber() . '" class="rc_gallery rc_' . $galleryClass . '" ' . $galleryParams . '>';
    }

    /**
     * @param string $fullFileURL
     * @param string $directory
     * @param string $fileName
     * @param int $height
     * @param int $width
     * @param bool $withLink
     * @param string $imgTitle
     * @param array $thumbnailTypes
     * @param bool $thumbsExist
     */
    public function addImage($fullFileURL, $directory, $fileName, $height, $width, $withLink, $imgTitle, array $thumbnailTypes, $thumbsExist): void
    {
        $images = [];

        foreach ($thumbnailTypes as $thumbnailType => $thumbnailTypeAttrributes) {
            $thumbExtension = (strpos($thumbnailType, 'webp') !== false)
                ? '.webp'
                : '.jpg';

            $mainImgExtension = strrchr($fileName, '.');
            $mainImgExtension = strtolower($mainImgExtension);

            $thumbFileName = 'thumb_' . str_replace($mainImgExtension, $thumbExtension, $fileName);

            $thumbnailTypeAttrributes['srcset'] = $directory . 'rc_thumbs/' . $thumbnailType . '/' . $thumbFileName;
            $images[$thumbnailType] = $thumbnailTypeAttrributes;
        }

        $thumbnailView = new ThumbnailView(
            $this->getRcParams(),
            $imgTitle,
            $fullFileURL,
            $width,
            $height,
            $images,
            $this->getGalleryNumber(),
            $this->getImageNumber(),
            $thumbsExist
        );

        $this->html .= $thumbnailView->build();

        $this->imageNumber++;
    }

    /**
     * Add CSS and JS links via WebAssetManager.
     *
     * @param int $imageBorderRadius
     */
    public function includeCSSandJS($imageBorderRadius): void
    {
        $wa       = $this->getWa();
        $mediaUrl = 'media/plg_content_rc_gallery/';

        // jQuery
        $wa->useScript('jquery');

        // Main gallery script
        $wa->registerAndUseScript(
            'plg_content_rc_gallery.gallery',
            $mediaUrl . 'js/rc_gallery.min.js',
            ['version' => 'auto'],
            [],
            ['jquery']
        );

        // Gallery CSS
        $wa->registerAndUseStyle(
            'plg_content_rc_gallery.gallery',
            $mediaUrl . 'css/rc_gallery_layout.css',
            ['version' => 'auto']
        );

        if (!$this->getRCParams()->layout) {
            // Default layout JS
            $wa->registerAndUseScript(
                'plg_content_rc_gallery.gallery_layout',
                $mediaUrl . 'js/rc_gallery_layout.min.js',
                ['version' => 'auto']
            );
        } else {
            // Custom layout from external layout media folder
            $layoutName = $this->getRcParams()->layout;

            $wa->registerAndUseScript(
                'plg_content_rc_gallery.layout_custom',
                'media/plg_rc_gallery_layouts/' . $layoutName . '/rc_gallery_layout.min.js',
                ['version' => 'auto']
            );
            $wa->registerAndUseStyle(
                'plg_content_rc_gallery.layout_custom',
                'media/plg_rc_gallery_layouts/' . $layoutName . '/rc_gallery_layout.css',
                ['version' => 'auto']
            );
        }
    }

    /**
     * Add custom inline styles.
     */
    public function includeCustomStyling(): void
    {
        $wa = $this->getWa();
        $filterOption = $this->getRcParams()->thumbnailfilter;

        $whiteSpace = $this->getRcParams()->titletextoverflow == 'hidden'
            ? 'white-space: nowrap;'
            : '';

        $css = '
			#rc_gallery_' . $this->getGalleryNumber() . '.rc_gallery .rc_galleryimg {
				background-color: ' . $this->getRcParams()->thumbbgcolour . ';
				border-radius: ' . $this->getRcParams()->thumbnailradius . 'px;
				margin: ' . $this->getRcParams()->imagemargin . 'px !important;
			}

			#rc_gallery_' . $this->getGalleryNumber() . '.rc_gallery div.rc_galleryimg_container span {
				color: ' . $this->getRcParams()->titletextcolour . ';
				font-size: ' . $this->getRcParams()->titletextsize . 'px;
				line-height: ' . ($this->getRcParams()->titletextsize + 6) . 'px;
				text-align: ' . $this->getRcParams()->titletextalign . ';
				overflow: ' . $this->getRcParams()->titletextoverflow . ';
				' . $whiteSpace . '
				font-weight: ' . $this->getRcParams()->titletextweight . ';
			}

			#rc_sb_overlay {
				background-color: ' . $this->getRcParams()->overlaycolour . ';
				opacity: ' . $this->getRcParams()->overlayopacity . ';'
                . ($this->getRcParams()->overlayblur ? 'backdrop-filter: blur(' . $this->getRcParams()->overlayblur . 'px);' : '') . '
			}
		';

        if ($filterOption == 1) {
            $css .= '
				#rc_gallery_' . $this->getGalleryNumber() . '.rc_gallery .rc_galleryimg {
					transition: -webkit-filter 0.28s ease, filter 0.28s ease;
					filter: sepia(80%);
					-webkit-filter: sepia(80%);
				}

				#rc_gallery_' . $this->getGalleryNumber() . '.rc_gallery div.rc_galleryimg_container:hover .rc_galleryimg {
					filter: sepia(0%);
				}
			';
        }

        if ($filterOption == 2) {
            $css .= '
				#rc_gallery_' . $this->getGalleryNumber() . '.rc_gallery .rc_galleryimg {
					transition: -webkit-filter 0.28s ease, filter 0.28s ease;
					filter: grayscale(100%);
					-webkit-filter: grayscale(100%);
				}

				#rc_gallery_' . $this->getGalleryNumber() . '.rc_gallery .rc_galleryimg:hover {
					filter: grayscale(0%);
					-webkit-filter: grayscale(0%);
				}
			';
        }

        if ($this->getRcParams()->imageTitle == 1) {
            $css .= '
				#rc_gallery_' . $this->getGalleryNumber() . '.rc_gallery div.rc_galleryimg_container span {
					opacity: 0;
				}

				#rc_gallery_' . $this->getGalleryNumber() . '.rc_gallery div.rc_galleryimg_container:hover span {
					opacity: 1;
				}
			';
        }

        $wa->addInlineStyle($css);
    }

    /**
     * Add JS and CSS for the legacy shadowbox.
     */
    public function includeShadowbox(): void
    {
        $wa       = $this->getWa();
        $mediaUrl = 'media/plg_content_rc_gallery/';

        $wa->registerAndUseScript(
            'plg_content_rc_gallery.shadowbox_legacy',
            $mediaUrl . 'shadowbox/shadowbox.js',
            ['version' => 'auto']
        );
        $wa->registerAndUseStyle(
            'plg_content_rc_gallery.shadowbox_legacy',
            $mediaUrl . 'shadowbox/shadowbox.css',
            ['version' => 'auto']
        );
    }

    /**
     * Add JS and CSS for the modern RC shadowbox.
     */
    public function includeRCShadowbox(): void
    {
        $wa       = $this->getWa();
        $mediaUrl = 'media/plg_content_rc_gallery/';

        $shadowboxParams = [
            'image_folder'    => Uri::root() . $mediaUrl . 'rc_shadowbox/img/',
            'title_option'    => $this->getRCParams()->shadowboxtitle,
            'hide_scroll_bar' => $this->getRCParams()->hidescrollbar,
        ];

        $wa->addInlineScript(
            'var rc_sb_params = ' . json_encode($shadowboxParams) . ';'
        );

        $wa->registerAndUseScript(
            'plg_content_rc_gallery.rc_shadowbox_swipe',
            $mediaUrl . 'rc_shadowbox/jquery.mobile.custom.min.js',
            ['version' => 'auto'],
            [],
            ['jquery']
        );
        $wa->registerAndUseScript(
            'plg_content_rc_gallery.rc_shadowbox',
            $mediaUrl . 'rc_shadowbox/rc_shadowbox.min.js',
            ['version' => 'auto'],
            [],
            ['jquery']
        );

        // Animation CSS loaded dynamically based on user parameter
        $animationName = $this->getRcParams()->shadowboxanimations;
        $wa->registerAndUseStyle(
            'plg_content_rc_gallery.rc_shadowbox_animation',
            $mediaUrl . 'rc_shadowbox/css/' . $animationName . '.css',
            ['version' => 'auto']
        );
    }

    /**
     * Build error message html.
     *
     * @param string $errorReason
     * @param string $tagcontent
     * @param string $rootFolder
     */
    public function errorReport(string $errorReason, string $tagcontent, string $rootFolder): void
    {
        $this->html = '<div class="rc_gallery_error">';
        $this->html .= '<h3>' . $errorReason . '</h3>';
        $this->html .= '<p>' . Text::sprintf('PLG_CONTENT_RC_GALLERY_ERROR_LOOKED_FOR', $tagcontent) . '</p>';
        $this->html .= '<p>' . Text::sprintf('PLG_CONTENT_RC_GALLERY_ERROR_UNDER_ROOT_FOLDER', $rootFolder) . '</p>';
        $this->html .= '</div>';
    }

    /**
     * Closes and returns the HTML.
     *
     * @return string
     */
    public function getHTML(): string
    {
        $this->html .= '</div>';

        return $this->html;
    }

    /**
     * @return \stdClass
     */
    public function getRcParams(): \stdClass
    {
        return $this->rcParams;
    }

    /**
     * @param \stdClass $rcParams
     * @return self
     */
    public function setRcParams(\stdClass $rcParams): self
    {
        $this->rcParams = $rcParams;

        return $this;
    }

    /**
     * @return int
     */
    public function getGalleryNumber(): int
    {
        return $this->galleryNumber;
    }

    /**
     * @param int $galleryNumber
     * @return self
     */
    public function setGalleryNumber(int $galleryNumber): self
    {
        $this->galleryNumber = $galleryNumber;

        return $this;
    }

    /**
     * @return int
     */
    public function getImageNumber(): int
    {
        return $this->imageNumber;
    }

    /**
     * @param int $imageNumber
     * @return self
     */
    public function setImageNumber(int $imageNumber): self
    {
        $this->imageNumber = $imageNumber;

        return $this;
    }

    /**
     * @return WebAssetManager
     */
    public function getWa(): WebAssetManager
    {
        return $this->wa;
    }

    /**
     * @param WebAssetManager $wa
     * @return self
     */
    public function setWa(WebAssetManager $wa): self
    {
        $this->wa = $wa;

        return $this;
    }
}
