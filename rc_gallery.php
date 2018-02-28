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

jimport('joomla.plugin.plugin');

class plgContentRC_gallery extends JPlugin { 
	
	var $plg_tag = "gallery"; //the bit to look for in curly braces, e.g. {gallery}photos{gallery}
	private $galleryNumber = 1;
	
	function __construct(&$subject, $params) {
		parent::__construct( $subject, $params );
	}
	
	function onContentPrepare($context, &$article, &$params, $page = 0) {
		if (strpos($article->text, '{gallery') === false) return; //bail out if not needed
		$this->showGalleries($article, $params);
	}
	
	function showGalleries(&$article, &$params)	 {
		
		//use jQuery and joomla filesystem
		JHtml::_('jquery.framework');
		jimport('joomla.filesystem.folder');
		
		$doc = JFactory::getDocument();
		$plugin = JPluginHelper::getPlugin('content', 'rc_gallery');
		$plg_params = new JRegistry($plugin->params);

		// expression to search for
		//$regex = "#{".$this->plg_tag."}(.*?){/".$this->plg_tag."}#is";
		$regex = "#{".$this->plg_tag.".*?}(.*?){/".$this->plg_tag."}#is";

		
		// Find all instances of the plugin and put them in $matches
		if (preg_match_all($regex, $article->text, $matches, PREG_PATTERN_ORDER) > 0) {

			// start the replace loop
			foreach ($matches[0] as $key => $match){
				// get our folder
				$tagContent = preg_replace("/{.+?}/", "", $match);
				
				// get inline params from the opening gallery tag
				$inlineParams = str_replace('{gallery ', '', $match);
				$inlineParams = str_replace('{/gallery}', '', $inlineParams);
				$inlineParams = str_replace('}' . $tagContent, '', $inlineParams); // will end up as '{gallery' if there were no inline params
				
				// initially set override variables to -1, so that we know to ignore them
				$overrideRootImageFolder = -1;
				$overrideStartHeight = -1;
				$overrideMarginSize = -1;
				$overrideImageBorderRadius = -1;
				$overrideTitleOption = -1;
				$overrideLabelsFile = -1;
				$overrideUseShadowbox = -1;
				$overrideShadowboxSize = -1;
				$overrideUseTitleAsAlt = -1;
				
				// as long as there were some inline params...
				if ($inlineParams != '{gallery') {
					$inlineParamsArray = explode(' ', $inlineParams);
					foreach($inlineParamsArray as $inlineParam) {
						$tagParamName = substr($inlineParam, 0, strpos($inlineParam, '=')); //everything before the equals sign
						$tagParamValue = str_replace('"', '', substr($inlineParam, strpos($inlineParam, '=') + 1, strlen($inlineParam) - strpos($inlineParam, '='))); //everything before the equals sign
						// only if it's a number
						if (is_numeric($tagParamValue)) {
							switch($tagParamName) {
								case 'target-row-height':
									$overrideStartHeight = $tagParamValue;
									break;
								case 'image-margin-size':
									$overrideMarginSize = $tagParamValue;
									break;
								case 'image-border-radius':
									$overrideImageBorderRadius = $tagParamValue;
									break;
								case 'image-title-option':
									$overrideTitleOption = $tagParamValue;
									break;
								case 'use-labels-file':
									$overrideLabelsFile = $tagParamValue;
									break;
								case 'use-shadowbox':
									$overrideUseShadowbox = $tagParamValue;
									break;
								case 'shadowbox-size':
									$overrideShadowboxSize = $tagParamValue;
									break;
								case 'use-title-as-alt':
									$overrideUseTitleAsAlt = $tagParamValue;
									break;
								default:
									//do nothing
							}
						}
						// regardless of whether it's numeric
						if ($tagParamName == 'root-image-folder') {
							$overrideRootImageFolder = $tagParamValue;
						}
					}
				}

				// put the gallery together
				$galleryContent = $this->buildGallery($tagContent, $plg_params, $doc, $overrideRootImageFolder, $overrideStartHeight, $overrideMarginSize,
									$overrideImageBorderRadius, $overrideTitleOption, $overrideLabelsFile, $overrideUseShadowbox, $overrideShadowboxSize, $overrideUseTitleAsAlt);
				//do the replace
				$article->text = preg_replace("#{".$this->plg_tag.".*?}".$tagContent."{/".$this->plg_tag."}#s", $galleryContent, $article->text);
			}
		}
	}
	
	function fileFilter() {
		$allowedExtensions = array('jpg','png','gif');
		// Also allow filetypes in uppercase	
		$allowedExtensions = array_merge($allowedExtensions, array_map('strtoupper', $allowedExtensions));
		// Build the filter. Will return something like: "jpg|png|JPG|PNG|gif|GIF"
		$filter = implode('|',$allowedExtensions);	
		$filter = "^.*\.(" . implode('|',$allowedExtensions) .")$";	

		return $filter;
	}
	
	function buildGallery($tagContent, $plg_params, $doc, $overrideRootImageFolder, $overrideStartHeight, $overrideMarginSize,
							$overrideImageBorderRadius,	$overrideTitleOption, $overrideLabelsFile, $overrideUseShadowbox, $overrideShadowboxSize, $overrideUseTitleAsAlt) {		
		// Get params. For overrides (inline settings) -1 means they aren't to be used
		if ($overrideRootImageFolder == -1) {$rootFolder = $plg_params->get('galleryfolder','images');} else {$rootFolder = $overrideRootImageFolder;}
		if ($overrideStartHeight == -1) {$startHeight = $plg_params->get('minrowheight', 100);} else {$startHeight = $overrideStartHeight;}
		if ($overrideMarginSize == -1) {$imgMargin = $plg_params->get('imagemargin', 2);} else {$imgMargin = $overrideMarginSize;}
		if ($overrideImageBorderRadius == -1) {$imageBorderRadius = $plg_params->get('imageborderradius', 0);} else {$imageBorderRadius = $overrideImageBorderRadius;}
		if ($overrideTitleOption == -1) {$imgTitleOption = $plg_params->get('imageTitle', 0);} else {$imgTitleOption = $overrideTitleOption;}
		if ($overrideLabelsFile == -1) {$useLabelsFile = ($plg_params->get('uselabelsfile', 0) == 1);} else {$useLabelsFile = ($overrideLabelsFile == 1);}
		if ($overrideUseShadowbox == -1) {$shadowboxOption = $plg_params->get('shadowboxoption', 0);} else {$shadowboxOption = $overrideUseShadowbox;}
		if ($overrideShadowboxSize == -1) {$shadowboxSize = $plg_params->get('shadowboxsize', 100);} else {$shadowboxSize = $overrideShadowboxSize;}
		if ($overrideUseTitleAsAlt == -1) {$useTitleAsAlt = $plg_params->get('usetitleasalt', 1);} else {$useTitleAsAlt = $overrideUseTitleAsAlt;}
		// overriding thumb quality not allowed - avoids confucion if multiple galleries for the same folder are created with different options
		$thumbQuality = $plg_params->get('thumbquality', 100); 
		
		// Uncomment this section to display all applicable settings on the page with the gallery
		//echo '<div class="rc_gallery_debug_info"><h3>RC Gallery Debug Info</h3>' .
		//	'<p>Root image folder: ' . $rootFolder . '</br>' .
		//	'Target row height: ' . $startHeight . '</br>' .
		//	'Image margin size: ' . $imgMargin . '</br>' .
		//	'Image border radius: ' . $imageBorderRadius . '</br>' .
		//	'Image title option: ' . $imgTitleOption . '</br>' .
		//	'Labels file option: ' . $useLabelsFile . '</br>' .
		//	'Shadowbox option: ' . $shadowboxOption . '</br>' .
		//	'Shadowbox size: ' . $shadowboxSize . '</br>' .
		//	'</p></div>';
		
		// Get the view class
		include_once(JPATH_SITE.'/plugins/content/rc_gallery/views/rc_gallery_view.php');
		$galleryView = new RCGalleryView($this->galleryNumber, $startHeight, $imgMargin);	

		//css and js files
		$galleryView->includeCSSandJS($doc, $imageBorderRadius);
		if ($shadowboxOption == 0) $galleryView->includeShadowbox($doc); //i.e. we want to use the included shadowbox
		if ($shadowboxOption == 3) $galleryView->includeRCShadowbox($doc, $shadowboxSize); //i.e. we want to use the shiny new shadowbox!
		
		//get all image files from the directory
		$directoryPath =  $rootFolder . '/' . $tagContent . '/';				
		$directoryPath = str_replace('//', '/', $directoryPath); //in case there were unnecessary leading or trailing slashes in the param
	
		if (! file_exists($directoryPath)) {
			$galleryView->errorReport('Image folder not found.', $tagContent, $rootFolder);
			return $galleryView->getHTML();
		}
		
		//Get the directory URL, and sort out spaces etc
		$directoryURL = $directoryPath;
		implode('/', array_map('rawurlencode', explode('/', $directoryURL)));
		
		$this->makeThumbnails($directoryPath, $startHeight, $thumbQuality);
		
		$files = JFolder::files($directoryPath, $this->fileFilter());

		if (!$files) {
			$galleryView->errorReport('No images found in specified folder.', $tagContent, $rootFolder);
			return $galleryView->getHTML();
		}
		
		if ($useLabelsFile) {
			//get the custom image titles & descriptions ready for this folder from labels.txt
			include_once(JPATH_SITE.'/plugins/content/rc_gallery/models/rc_labels_model.php');
			$labelsModel = new RCLabels;
			$labelsModel->getLabelsFromFile($directoryPath);
		}
		
		foreach ($files as $file) {

			//Get full paths
			$fullFilePath = JPATH_ROOT . '/' . $directoryPath . $file;
			$thumbFilePath = JPATH_ROOT . '/' . $directoryPath . 'rc_thumbs/' . 'thumb_' . $file;
			
			//Get full URLs
			$fullFileURL = JURI::root(true) . '/' . $directoryURL . rawurlencode($file);
			$thumbFileURL = JURI::root(true) . '/' .  $directoryURL . 'rc_thumbs/' . 'thumb_' .  rawurlencode($file);
			
			//get the width and height of the image file
			list($width, $height, $type, $attr) = getimagesize($thumbFilePath);		
			$rc_imgHeight = $plg_params->get('minrowheight', 100);
			
			if ($rc_imgHeight == 0) $imgWidth = 100; //Just in case
			if ($height == 0) $height = $rc_imgHeight; //just in case
			
			$ratio = $height / $width;
			$imgWidth = $rc_imgHeight / $ratio;
			
			// get the image title
			if ($useLabelsFile) {
				if (!$labelsModel->getTitle($file)) {
					// from the file name
					$imgTitle = $this->getImageTitleFromFileName($file);
				} else {
					// from labels.txt
					$imgTitle = $labelsModel->getTitle($file);
				}
			} else {
				// from the file name
				$imgTitle = $this->getImageTitleFromFileName($file);
			}
			//add the image to the view
			if ($shadowboxOption != 2) { $withLink = true; } else { $withLink = false; };
			$galleryView->addImage($fullFileURL, $thumbFileURL, $height, $width, $withLink, $imgMargin, $imgTitleOption, $imgTitle, $useTitleAsAlt);

		}
		
		$this->galleryNumber++;
		
		// close HTML in the view, and return it
		return $galleryView->getHTML();
		
	}
	
	private function getImageTitleFromFileName($fileName) {
		$imageTitle = str_replace('_', ' ', rawurldecode($fileName));
		$imageTitle = preg_replace('/\\.[^.\\s]{3,4}$/', '', $imageTitle);
		$imageTitle = ucfirst($imageTitle);
		return $imageTitle;
	}
	
	private function makeThumbnails($directoryPath, $startHeight, $thumbQuality) {
		
		$filter = $this->fileFilter();
		$files = JFolder::files($directoryPath, $filter);
		include_once(JPATH_SITE.'/plugins/content/rc_gallery/rc_resize.php');
			
		foreach ($files as $file) {
			
			$fullFilePath = JPATH_ROOT . '/' . $directoryPath . $file;

			$thumbPath = $directoryPath . 'rc_thumbs/' . 'thumb_' . $file;
			$thumbFolder = $directoryPath . 'rc_thumbs';
			
			if (!file_exists($thumbFolder)) {
				mkdir($thumbFolder);
			}
			
			if (!file_exists($thumbPath)) {
				$this->makeSingleThumbnail($fullFilePath, $startHeight, $thumbPath, $thumbQuality);
			}
		}
	}
	
	private function makeSingleThumbnail($fullFilePath, $startHeight, $thumbPath, $thumbQuality) {
		$resizeObj = new RCResize($fullFilePath);				
		if ($resizeObj != false) { //don't bother resizing if an image couldn't be opened e.g. because of a bad path
			$resizeObj -> resizeImage($startHeight * 2);
			if ($thumbQuality > 100 || $thumbQuality < 0) $thumbQuality = 100;
			$resizeObj -> saveImage($thumbPath, $thumbQuality);
		}
	}
}