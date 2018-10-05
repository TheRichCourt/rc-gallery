<?php

defined( '_JEXEC' ) or die;

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

	/**
	 * Pass in params, and open the main containing div for the gallery
	 *
	 * @param int $galleryNo
	 * @param array $rcParams
	 * @param int $startHeightParam
	 * @param int $marginSizeParam
	 * @param stdClass $rcParams
	 */
	function __construct($galleryNo,  $startHeightParam, $marginSizeParam, stdClass $rcParams)
	{
		$this->setRcParams($rcParams);
		$this->galleryNumber = $galleryNo;
		$this->galleryParams = ' data-start-height="' . $startHeightParam . '" data-margin-size="' . $marginSizeParam . '"';
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
	 * @return void
	 */
	public function addImage($fullFileURL, $directory, $fileName, $height, $width, $withLink, $imgTitle, array $thumbnailTypes)
	{
		require_once JPATH_SITE.'/plugins/content/rc_gallery/views/ThumbnailView.php';

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
			$this->getImageNumber()
		);

		$this->html .= $thumbnailView->build();

		$this->imageNumber++;
    }

	/**
	 * Add CSS and JS links to the document
	 *
	 * @param mixed $doc
	 * @param int $imageBorderRadius
	 * @return void
	 */
	public function includeCSSandJS($doc, $imageBorderRadius)
	{
		$doc->addScript(JURI::root().'plugins/content/rc_gallery/assets/js/rc_gallery.js');
		$css_path = JURI::root().'plugins/content/rc_gallery/assets/css/rc_gallery.css?'.filemtime(JPATH_ROOT.'/plugins/content/rc_gallery/assets/css/rc_gallery.css');
		$doc->addStyleSheet($css_path);

		if ($imageBorderRadius > 0) {
			$style = 	'img.rc_galleryimg {
							border-radius:' . $imageBorderRadius . 'px;
						}';
			$doc->addStyleDeclaration( $style );
		}
	}

	/**
	 * Add an additional style tag to the document head, with cusotm parameters
	 *
	 * @param array $params
	 * @param mixed $doc
	 * @return void
	 */
	public function includeCustomStyling($params, $doc)
	{
		$filterOption = $params->get('thumbnailfilter', 0);

		if ($params->get('titletextoverflow', 'hidden') == 'hidden') {
			$whiteSpace = 'white-space: nowrap;';
		} else {
			$whitespace = '';
		}

		$css = '
			.rc_gallery img.rc_galleryimg {
				background-color: '. $params->get('thumbbgcolour', '#f2f2f2') .';
				border-radius: ' . $params->get('thumbnailradius', '0') . 'px;
			}

			.rc_gallery div.rc_galleryimg_container span {
				color: ' . $params->get('titletextcolour', '#fff') . ';
				font-size: ' . $params->get('titletextsize', 14) . 'px;
				line-height: ' . ($params->get('titletextsize', 14) + 6) . 'px;
				text-align: ' . $params->get('titletextalign', 'left') . ';
				overflow: ' . $params->get('titletextoverflow', 'hidden') . ';
				'. $whiteSpace .'
				font-weight: ' . $params->get('titletextweight', 'bold') . ';
			}

			#rc_sb_overlay {
				background-color: ' . $params->get('overlaycolour', '#000') . ';
				opacity: ' . $params->get('overlayopacity', '0.85') . ';
			}
		';

		if ($filterOption == 1) { // sepia
			$css .= '
				.rc_gallery img.rc_galleryimg {
					transition: -webkit-filter 0.28s ease, filter 0.28s ease;
					filter: sepia(80%);
					-webkit-filter: sepia(80%);
				}

				.rc_gallery img.rc_galleryimg:hover {
					filter: sepia(0%);
				}
			';
		}

		if ($filterOption == 2) { // black and white
			$css .= '
				.rc_gallery img.rc_galleryimg {
					transition: -webkit-filter 0.28s ease, filter 0.28s ease;
					filter: grayscale(100%);
					-webkit-filter: grayscale(100%);
				}

				.rc_gallery img.rc_galleryimg:hover {
					filter: grayscale(0%);
					-webkit-filter: grayscale(0%);
				}
			';
		}

		$doc->addStyleDeclaration($css);
	}

	/**
	 * Add JS and CSS files for the legacy shadowbox
	 *
	 * @param mixed $doc
	 * @return void
	 */
	public function includeShadowbox($doc)
	{
		$doc->addScript(JURI::root().'plugins/content/rc_gallery/shadowbox/shadowbox.js');
		$doc->addStyleSheet(JURI::root().'plugins/content/rc_gallery/shadowbox/shadowbox.css?'.filemtime(JPATH_ROOT.'/plugins/content/rc_gallery/shadowbox/shadowbox.css'));
	}

	/**
	 * Add JS and CSS files for the modern shadowbox
	 *
	 * @param mixed $doc
	 * @param int $shadowboxSize		0 - 100
	 * @return void
	 */
	public function includeRCShadowbox($doc, $shadowboxSize, $shadowboxTitleOption)
	{
		$shadowboxParams = [
			'image_folder' => JURI::root().'plugins/content/rc_gallery/rc_shadowbox/img/',
			'expand_size' => $shadowboxSize / 100,
			'title_option' => $shadowboxTitleOption
		];

		$doc->addScriptDeclaration(
			'var rc_sb_params = ' . json_encode($shadowboxParams) . ';'
		);

		/* $doc->addScriptDeclaration(
			'var rc_sb_imgFolder = "'.JURI::root().'plugins/content/rc_gallery/rc_shadowbox/img/";
			var rc_sb_expandSize = "'. $shadowboxSize / 100 .'";'
		); */
		$doc->addScript(JURI::root().'plugins/content/rc_gallery/rc_shadowbox/jquery.mobile.custom.min.js?'.filemtime(JPATH_ROOT.'/plugins/content/rc_gallery/rc_shadowbox/jquery.mobile.custom.min.js'));
		$doc->addScript(JURI::root().'plugins/content/rc_gallery/rc_shadowbox/rc_shadowbox.js?'.filemtime(JPATH_ROOT.'/plugins/content/rc_gallery/rc_shadowbox/rc_shadowbox.js'));
		$doc->addStyleSheet(JURI::root().'plugins/content/rc_gallery/rc_shadowbox/rc_shadowbox.css?'.filemtime(JPATH_ROOT.'/plugins/content/rc_gallery/rc_shadowbox/rc_shadowbox.css'));
	}

	/**
	 * Build error message html
	 *
	 * @param [type] $errorReason
	 * @param [type] $tagcontent
	 * @param [type] $rootFolder
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
}