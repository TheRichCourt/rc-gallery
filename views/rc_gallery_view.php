<?php

/********************************************************************
Product		: RC Justified Gallery
Date		: 28/02/2018
Copyright	: Rich Court 2018
Contact		: http://www.therichcourt.com
Licence		: GNU General Public License
*********************************************************************/

// no direct access
defined( '_JEXEC' ) or die;

Class RCGalleryView
{
	/** @var string */
	private $html;

	/** @var array */
	private $galleryParams;

	/** @var int */
	private $imageNumber = 1;

	/** @var int */
	private $galleryNumber = 0;

	/**
	 * Pass in params, and open the main containing div for the gallery
	 *
	 * @param int $galleryNo
	 * @param int $startHeightParam
	 * @param int $marginSizeParam
	 */
	function __construct($galleryNo, $startHeightParam, $marginSizeParam)
	{
		$this->galleryNumber = $galleryNo;
		$this->galleryParams = ' data-start-height="' . $startHeightParam . '" data-margin-size="' . $marginSizeParam . '"';
		$this->html = '<div class="rc_gallery" '. $this->galleryParams .'>';
	}

	/**
	 * Add am image to the gallery markup
	 *
	 * @param string $fullFileURL
	 * @param string $thumbFileURL
	 * @param int $height
	 * @param int $width
	 * @param bool $withLink
	 * @param int $imgMargin
	 * @param int $imgTitleOption
	 * @param string $imgTitle
	 * @param bool $useTitleAsAlt
	 * @return void
	 */
	public function addImage($fullFileURL, $thumbFileURL, $height, $width, $withLink, $imgMargin, $imgTitleOption, $imgTitle, $useTitleAsAlt)
	{
		//add a link if we're including the shadowbox
		if ($withLink) $this->html .= '<a href="' . $fullFileURL . '" rel="shadowbox[rc_gallery]" data-image-title="'.$imgTitle.'">';

		//construct the image's containing div, and the image to go inside it
		$this->html .= '<div class="rc_galleryimg_container" ';
		$this->html .= 'id="rc_'. $imgTitle .'_'. $this->galleryNumber .'_'. $this->imageNumber .'"';
		$this->html .= '>';
		$this->html .= '<img class="rc_galleryimg"';

		$this->html .= ' src="'.$thumbFileURL.'"';
		if ($useTitleAsAlt == 1) {
			$this->html .= ' alt="'.$imgTitle;
		}
		$this->html .= '" style="margin:'.$imgMargin.'px;"';
		$this->html .= ' data-width="'.$width.'"';
		$this->html .= ' data-height="'.$height.'"';
		$this->html .= '/>';

		//Image titles
		if ($imgTitleOption == 1 || $imgTitleOption == 2) {
			$this->html .= '<span style="';
			$this->html .= 'margin:' . $imgMargin . 'px; ';
			$this->html .= 'width:calc(100% - ' . (2 * $imgMargin) . 'px); ';

			//start fully opaque if 'always show' option is selected
			if ($imgTitleOption == 2) $this->html .= 'opacity:1 !important;';

			$this->html .= '">';
			$this->html .= $imgTitle . '</span>';
		}

		$this->html .= '</div>'; //close the image container <div>

        if ($withLink) $this->html .= '</a>'; //close the shadowbox link, if it was used
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
	public function includeRCShadowbox($doc, $shadowboxSize)
	{
		$doc->addScriptDeclaration('var rc_sb_imgFolder = "'.JURI::root().'plugins/content/rc_gallery/rc_shadowbox/img/";
									var rc_sb_expandSize = "'. $shadowboxSize / 100 .'";');
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
	 * Returs the HTML built in his object
	 *
	 * @return void
	 */
	public function getHTML()
	{
		//close off the open html tags, and return the lot
		$this->html .= '</div>';
		return $this->html;
	}
}