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

Class RCGalleryView {
    
    private $html;
	private $jsVars;
	private $galleryParams;
    
    function __construct($startHeightParam, $marginSizeParam) {
		//pass in params, and open the main containing div for the gallery
		$this->galleryParams = ' data-start-height="' . $startHeightParam . '" data-margin-size="' . $marginSizeParam . '"';
		$this->html = '<div class="rc_gallery" '. $this->galleryParams .'>';
	}
	
    public function addImage($fullFileURL, $thumbFileURL, $height, $width, $withLink, $imgMargin, $imgTitleOption, $imgTitle) {
        
		//add a link if we're including the shadowbox
		if ($withLink) $this->html .= '<a href="' . $fullFileURL . '" rel="shadowbox[rc_gallery]">';
		
		//construct the image's containing div, and the image to go inside it
		$this->html .= '<div class="rc_galleryimg_container">';
		$this->html .= '<img class="rc_galleryimg" class="rc_galleryimg" src="';
		$this->html .= $thumbFileURL . '" style="margin:' . $imgMargin . 'px;"';
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
    }
    
	public function includeCSSandJS($doc) {
		$doc->addScript(JURI::root().'plugins/content/rc_gallery/assets/js/rc_gallery.js');
		$css_path = JURI::root().'plugins/content/rc_gallery/assets/css/rc_gallery.css?'.filemtime(JPATH_ROOT.'/plugins/content/rc_gallery/assets/css/rc_gallery.css');
		$doc->addStyleSheet($css_path);
	}
	
	public function includeShadowbox($doc) {
		$doc->addScript(JURI::root().'plugins/content/rc_gallery/shadowbox/shadowbox.js');
		$doc->addStyleSheet(JURI::root().'plugins/content/rc_gallery/shadowbox/shadowbox.css?'.filemtime(JPATH_ROOT.'/plugins/content/rc_gallery/shadowbox/shadowbox.css'));
	}
	
	public function includeRCShadowbox($doc) {
		$doc->addScriptDeclaration('var rc_sb_imgFolder = "'.JURI::root().'plugins/content/rc_gallery/rc_shadowbox/img/";');
		$doc->addScript(JURI::root().'plugins/content/rc_gallery/rc_shadowbox/rc_shadowbox.js');
		$doc->addStyleSheet(JURI::root().'plugins/content/rc_gallery/rc_shadowbox/rc_shadowbox.css?'.filemtime(JPATH_ROOT.'/plugins/content/rc_gallery/rc_shadowbox/rc_shadowbox.css'));
	}
	
    public function errorReport($errorReason, $tagcontent, $rootFolder) {
		//Forget everything else, and replace it with the error message
        $this->html = '<div class="rc_gallery_error">';
		$this->html .= '<h3>' . $errorReason . '</h3>';
		$this->html .= '<p>Looked for images in: "' .  $tagcontent .  '"</p> <p>Under your root image folder: "' . $rootFolder . '"</p>';
		$this->html .= '</div>';
	}
	
	public function getHTML() {
		//close off the open html tags, and return the lot
		$this->jsVars .= '</script>';
		$this->html .= '</div>';
		return $this->jsVars . ' ' . $this->html;
	}
}