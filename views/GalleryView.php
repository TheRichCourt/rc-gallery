<?php

defined( '_JEXEC' ) or die;

use Joomla\CMS\Document\HtmlDocument;

Class GalleryView
{
	/** @var string */
	private $html;

	/** @var array */
	private $galleryParams;

	/** @var stdClass */
	private $rcParams;

	/** @var int */
	private $imageNumber = 1;

	/** @var int */
	private $galleryNumber = 0;

	/** @var HtmlDocument */
	private $doc;

	/**
	 * Pass in params, and open the main containing div for the gallery
	 *
	 * @param int $galleryNo
	 * @param array $rcParams
	 * @param HtmlDocument $doc
	 * @param stdClass $rcParams
	 */
	function __construct($galleryNo, stdClass $rcParams, $doc)
	{
		$this->setRcParams($rcParams);
		$this->setDoc($doc);
		$this->galleryNumber = $galleryNo;
		$this->galleryParams = ' data-start-height="' . $this->getRCParams()->minrowheight . '" data-margin-size="' . $this->getRCParams()->imagemargin . '"';
		$this->html = '<div class="rc_gallery" '. $this->galleryParams .'>';
	}

	/**
	 * @param string $fullFileURL
	 * @param string $thumbFileURL
	 * @param int $height
	 * @param int $width
	 * @param bool $withLink
	 * @param string $imgTitle
	 * @param array $thumbnailTypes
	 * @param bool $thumbsExist
	 * @return void
	 */
	public function addImage($fullFileURL, $directory, $fileName, $height, $width, $withLink, $imgTitle, array $thumbnailTypes, $thumbsExist)
	{
		require_once JPATH_SITE . '/plugins/content/rc_gallery/views/ThumbnailView.php';

		$images = [];

		foreach ($thumbnailTypes as $thumbnailType => $thumbnailTypeAttrributes) {
			$thumbExtension = (strpos($thumbnailType, 'webp') !== false)
				? '.webp'
				: '.jpg'
			;

			$mainImgExtension = strrchr($fileName, '.');
			$mainImgExtension = strtolower($mainImgExtension);

			$thumbFileName = 'thumb_' . str_replace($mainImgExtension, $thumbExtension, $fileName);

			$thumbnailTypeAttrributes['srcset'] = $directory . 'rc_thumbs/' . $thumbnailType . '/' . $thumbFileName . '';
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
	 * Add CSS and JS links to the document
	 *
	 * @param int $imageBorderRadius
	 * @return void
	 */
	public function includeCSSandJS($imageBorderRadius)
	{
		JHtml::_('jquery.framework');
		$jsPath = JURI::root().'plugins/content/rc_gallery/assets/js/rc_gallery.js?'.filemtime(JPATH_ROOT.'/plugins/content/rc_gallery/assets/js/rc_gallery.js');
		$this->getDoc()->addScript($jsPath);
		$cssPath = JURI::root().'plugins/content/rc_gallery/assets/css/rc_gallery.css?'.filemtime(JPATH_ROOT.'/plugins/content/rc_gallery/assets/css/rc_gallery.css');
		$this->getDoc()->addStyleSheet($cssPath);

		if ($imageBorderRadius > 0) {
			$style =
				'.rc_galleryimg {
					border-radius:' . $imageBorderRadius . 'px;
				}'
			;
			$this->getDoc()->addStyleDeclaration( $style );
		}
	}

	/**
	 * Add an additional style tag to the document head, with cusotm parameters
	 *
	 * @return void
	 */
	public function includeCustomStyling()
	{
		$filterOption = $this->getRcParams()->thumbnailfilter;

		if ($this->getRcParams()->titletextoverflow == 'hidden') {
			$whiteSpace = 'white-space: nowrap;';
		} else {
			$whitespace = '';
		}

		$css = '
			.rc_gallery .rc_galleryimg {
				background-color: '. $this->getRcParams()->thumbbgcolour .';
				border-radius: ' . $this->getRcParams()->thumbnailradius . 'px;
			}

			.rc_gallery div.rc_galleryimg_container span {
				color: ' . $this->getRcParams()->titletextcolour . ';
				font-size: ' . $this->getRcParams()->titletextsize . 'px;
				line-height: ' . ($this->getRcParams()->titletextsize + 6) . 'px;
				text-align: ' . $this->getRcParams()->titletextalign . ';
				overflow: ' . $this->getRcParams()->titletextoverflow . ';
				'. $whiteSpace .'
				font-weight: ' . $this->getRcParams()->titletextweight . ';
			}

			#rc_sb_overlay {
				background-color: ' . $this->getRcParams()->overlaycolour . ';
				opacity: ' . $this->getRcParams()->overlayopacity . ';
			}
		';

		if ($filterOption == 1) { // sepia
			$css .= '
				.rc_gallery .rc_galleryimg {
					transition: -webkit-filter 0.28s ease, filter 0.28s ease;
					filter: sepia(80%);
					-webkit-filter: sepia(80%);
				}

				.rc_gallery .rc_galleryimg:hover {
					filter: sepia(0%);
				}
			';
		}

		if ($filterOption == 2) { // black and white
			$css .= '
				.rc_gallery .rc_galleryimg {
					transition: -webkit-filter 0.28s ease, filter 0.28s ease;
					filter: grayscale(100%);
					-webkit-filter: grayscale(100%);
				}

				.rc_gallery .rc_galleryimg:hover {
					filter: grayscale(0%);
					-webkit-filter: grayscale(0%);
				}
			';
		}

		$this->getDoc()->addStyleDeclaration($css);
	}

	/**
	 * Add JS and CSS files for the legacy shadowbox
	 *
	 * @return void
	 */
	public function includeShadowbox()
	{
		$this->getDoc()->addScript(JURI::root().'plugins/content/rc_gallery/shadowbox/shadowbox.js?'.filemtime(JPATH_ROOT.'/plugins/content/rc_gallery/shadowbox/shadowbox.js'));
		$this->getDoc()->addStyleSheet(JURI::root().'plugins/content/rc_gallery/shadowbox/shadowbox.css?'.filemtime(JPATH_ROOT.'/plugins/content/rc_gallery/shadowbox/shadowbox.css'));
	}

	/**
	 * Add JS and CSS files for the modern shadowbox
	 *
	 * @return void
	 */
	public function includeRCShadowbox()
	{
		$shadowboxParams = [
			'image_folder' => JURI::root().'plugins/content/rc_gallery/rc_shadowbox/img/',
			'expand_size' => $this->getRCParams()->shadowboxsize / 100,
			'title_option' => $this->getRCParams()->shadowboxtitle
		];

		$this->getDoc()->addScriptDeclaration(
			'var rc_sb_params = ' . json_encode($shadowboxParams) . ';'
		);

		$this->getDoc()->addScript(JURI::root().'plugins/content/rc_gallery/rc_shadowbox/jquery.mobile.custom.min.js?'.filemtime(JPATH_ROOT.'/plugins/content/rc_gallery/rc_shadowbox/jquery.mobile.custom.min.js'));
		$this->getDoc()->addScript(JURI::root().'plugins/content/rc_gallery/rc_shadowbox/rc_shadowbox.js?'.filemtime(JPATH_ROOT.'/plugins/content/rc_gallery/rc_shadowbox/rc_shadowbox.js'));
		$this->getDoc()->addStyleSheet(JURI::root().'plugins/content/rc_gallery/rc_shadowbox/rc_shadowbox.css?'.filemtime(JPATH_ROOT.'/plugins/content/rc_gallery/rc_shadowbox/rc_shadowbox.css'));
	}

	/**
	 * Build error message html
	 *
	 * @param string $errorReason
	 * @param string $tagcontent
	 * @param string $rootFolder
	 * @return void
	 */
	public function errorReport($errorReason, $tagcontent, $rootFolder)
	{
		//Forget everything else, and replace it with the error message
        $this->html = '<div class="rc_gallery_error">';
		$this->html .= '<h3>' . $errorReason . '</h3>';
		$this->html .= '<p>Looked for images in: "' .  $tagcontent .  '"</p> <p>Under your root image folder: "' . $rootFolder . '"</p>';
		$this->html .= '</div>';
	}

	/**
	 * Closes and returns the HTML built in his object
	 *
	 * @return void
	 */
	public function getHTML()
	{
		//close off the open html tags, and return the lot
		$this->html .= '</div>';
		return $this->html;
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
	 * @return  self
	 */
	public function setRcParams(stdClass $rcParams)
	{
		$this->rcParams = $rcParams;

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
	 * @return HtmlDocument
	 */
	public function getDoc()
	{
		return $this->doc;
	}

	/**
	 * @param HtmlDocument $doc
	 * @return self
	 */
	public function setDoc(HtmlDocument $doc)
	{
		$this->doc = $doc;

		return $this;
	}
}
