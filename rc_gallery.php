<?php

defined( '_JEXEC' ) or die; // no direct access

jimport('joomla.plugin.plugin');

class plgContentRC_gallery extends JPlugin
{
	/** @var string */
	const GALLERY_TAG = "gallery"; //the bit to look for in curly braces, e.g. {gallery}photos{gallery}

	/** @var int */
	private $galleryNumber = 1;

	/** @var stdClass */
	private $rcParams;

	/** @var array */
	private $thumbnailTypes;

	/**
	 * Fire off the parent constructor
	 *
	 * @param string $subject
	 * @param array $params
	 */
	function __construct(&$subject, $params)
	{
		parent::__construct( $subject, $params );

		$lowDpiQuery =
			'(-webkit-max-resolution: 143dpi), (max-resolution: 143dpi)'
		;

		$highDpiQuery =
			'(-webkit-min-resolution: 144dpi), (min-resolution: 144dpi)'
		;

		$this->setThumbnailTypes([
			'webp' => [
				'type' => 'image/webp',
				'media' => $lowDpiQuery
			],
			'jpg' => [
				'type' => 'image/jpeg',
				'media' => $lowDpiQuery
			],
			'webp-hdpi' => [
				'type' => 'image/webp',
				'media' => $highDpiQuery
			],
			'jpg-hdpi' => [
				'type' => 'image/jpeg',
				'media' => $highDpiQuery
			]
		]);
	}

	/**
	 * Method to hook into Joomla to alter the article
	 *
	 * @param array $context
	 * @param array $article
	 * @param array $params
	 * @param integer $page
	 * @return void
	 */
	function onContentPrepare($context, &$article, &$params, $page = 0)
	{
		if (strpos($article->text, '{gallery') === false) {
			return; //bail out if plugin's not needed
		}

		$this->showGalleries($article);
	}

	/**
	 * Identify gallery tags, and replace them with an actual gallery
	 *
	 * @param array $article
	 * @param array $params
	 * @return void
	 */
	function showGalleries(&$article)
	{
		//use jQuery and joomla filesystem
		JHtml::_('jquery.framework');
		jimport('joomla.filesystem.folder');

		$doc = JFactory::getDocument();
		$plugin = JPluginHelper::getPlugin('content', 'rc_gallery');
		$pluginParams = new JRegistry($plugin->params);

		// expression to search for
		$regex = "#{".self::GALLERY_TAG.".*?}(.*?){/".self::GALLERY_TAG."}#is";

		// Find all instances of the plugin and put them in $matches
		if (preg_match_all($regex, $article->text, $tagMatches, PREG_PATTERN_ORDER) > 0) {

			// start the replace loop
			foreach ($tagMatches[0] as $tagKey => $tag) {
				// get our folder
				$tagContent = $tagMatches[1][$tagKey];

				require_once JPATH_SITE.'/plugins/content/rc_gallery/Params.php';
				$paramsObj = new Params($pluginParams, $tag);
				$this->setRCParams($paramsObj->getParams());

				// put the gallery together
				$galleryContent = $this->buildGallery($tagContent, $pluginParams, $doc);
				//do the replace
				$article->text = preg_replace("#{".self::GALLERY_TAG.".*?}".$tagContent."{/".self::GALLERY_TAG."}#s", $galleryContent, $article->text);
			}
		}
	}


	/**
	 * Produce the filter to ensure only supported image files are used
	 *
	 * @return string
	 */
	function fileFilter()
	{
		$allowedExtensions = array('jpg','png','gif');
		// Also allow filetypes in uppercase
		$allowedExtensions = array_merge($allowedExtensions, array_map('strtoupper', $allowedExtensions));
		// Build the filter. Will return something like: "jpg|png|JPG|PNG|gif|GIF"
		$filter = implode('|',$allowedExtensions);
		$filter = "^.*\.(" . implode('|',$allowedExtensions) .")$";

		return $filter;
	}

	/**
	 * Build a single gallery
	 *
	 * @param string $tagContent
	 * @param array $pluginParams
	 * @param [type] $doc
	 * @return void
	 */
	function buildGallery($tagContent, $pluginParams, $doc)
	{
		// Get the view class
		require_once JPATH_SITE.'/plugins/content/rc_gallery/views/GalleryView.php';
		$galleryView = new GalleryView($this->galleryNumber, $this->getRCParams()->minrowheight, $this->getRCParams()->imagemargin, $this->getRCParams());

		//css and js files
		$galleryView->includeCSSandJS($doc, $this->getRCParams()->thumbnailradius);
		$galleryView->includeCustomStyling($pluginParams, $doc);

		if ($this->getRCParams()->shadowboxoption == 0) {
			$galleryView->includeShadowbox($doc); //i.e. we want to use the included shadowbox
		}

		if ($this->getRCParams()->shadowboxoption == 3) {
			$galleryView->includeRCShadowbox($doc, $this->getRCParams()->shadowboxsize, $this->getRCParams()->shadowboxtitle); //i.e. we want to use the shiny new shadowbox!
		}

		//get all image files from the directory
		$directoryPath =  $this->getRCParams()->galleryfolder . '/' . $tagContent . '/';
		$directoryPath = str_replace('//', '/', $directoryPath); //in case there were unnecessary leading or trailing slashes in the param

		if (! file_exists($directoryPath)) {
			$galleryView->errorReport('Image folder not found.', $tagContent, $rootFolder);
			return $galleryView->getHTML();
		}

		//Get the directory URL, and sort out spaces etc
		$directoryURL = $directoryPath;
		implode('/', array_map('rawurlencode', explode('/', $directoryURL)));

		$this->makeThumbnails($directoryPath, $this->getRCParams()->minrowheight, $this->getRCParams()->thumbquality);

		$files = JFolder::files($directoryPath, $this->fileFilter());

		switch($this->getRCParams()->sorttype) {
			case 1: // by date
				$this->resortImagesByDate($files, $fullFilePath = JPATH_ROOT . '/' . $directoryPath, $this->getRCParams()->sortdesc);
				break;
			case 0: // by file name
			default:
				$this->resortImagesByFileName($files, $this->getRCParams()->sortdesc);
		}

		if (!$files) {
			$galleryView->errorReport('No images found in specified folder.', $tagContent, $this->getRCParams()->galleryfolder);
			return $galleryView->getHTML();
		}

		if ($this->getRCParams()->uselabelsfile) {
			//get the custom image titles & descriptions ready for this folder from labels.txt
			require_once JPATH_SITE.'/plugins/content/rc_gallery/models/rc_labels_model.php';
			$labelsModel = new RCLabels;
			$labelsModel->getLabelsFromFile($directoryPath);
		}

		foreach ($files as $file) {
			//Get full paths
			$fullFilePath = JPATH_ROOT . '/' . $directoryPath . $file;
			$thumbFilePath = JPATH_ROOT . '/' . $directoryPath . 'rc_thumbs/jpg/' . 'thumb_' . $file;

			//Get full URLs
			$fullFileURL = JURI::root(true) . '/' . $directoryURL . rawurlencode($file);
			$thumbFileURL = JURI::root(true) . '/' .  $directoryURL . 'rc_thumbs/' . 'thumb_' .  rawurlencode($file);

			//get the width and height of the image file
			list($width, $height, $type, $attr) = getimagesize($thumbFilePath);

			if ($this->getRCParams()->minrowheight == 0) $imgWidth = 100; //Just in case

			if ($height == 0) $height = $this->getRCParams()->minrowheight; //just in case

			$ratio = $height / $width;
			$imgWidth = $this->getRCParams()->minrowheight / $ratio;

			// get the image title
			if ($this->getRCParams()->uselabelsfile) {
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

			$withLink = ($this->getRCParams()->shadowboxoption != 2);

			//add the image to the view
			$galleryView->addImage(
				$fullFileURL,
				JURI::root(true) . '/' . $directoryPath,
				rawurlencode($file),
				$height,
				$width,
				$withLink,
				$imgTitle,
				$this->getRCParams()->usetitleasalt,
				$this->getThumbnailTypes()
			);
		}

		$this->galleryNumber++;

		// close HTML in the view, and return it
		return $galleryView->getHTML();
	}

	/**
	 * Put images into order of filename
	 *
	 * @param array &$files
	 * @param boolean $desc
	 * @return void
	 */
	private function resortImagesByFileName(&$files, $desc = false)
	{
		if ($desc) {
			arsort($files);
		}
	}

	/**
	 * Put images into order by date (where date is available in Exif)
	 *
	 * @param array $files
	 * @param string $folderPath
	 * @param boolean $desc
	 * @return void
	 */
	private function resortImagesByDate(&$files, $folderPath, $desc = false)
	{
		// build a new array, adding in the create date from exif (where available)
		$newFilesWithCreateDate = array();

		foreach ($files as $file) {
			$createDate = $this->getCreateDateFromExif($folderPath . $file);
			$newFile = array(
				"path" => $file,
				"createdate" => $createDate,
			);
			array_push($newFilesWithCreateDate, $newFile);
		}

		// do the sorting
		foreach ($newFilesWithCreateDate as $key => $row) {
			$path[$key] = $row['path'];
			$createdate[$key] = $row['createdate'];
		}

		if (!$desc) {
			array_multisort($createdate, SORT_ASC, $path, SORT_ASC, $newFilesWithCreateDate);
		} else {
			array_multisort($createdate, SORT_DESC, $path, SORT_DESC, $newFilesWithCreateDate);
		}

		// now remove the create date again, and go back to a simple array of files
		$newFiles = array();

		foreach ($newFilesWithCreateDate as $a) {
			array_push($newFiles, $a['path']);
		}

		$files = $newFiles;
	}

	/**
	 * Get the exif date of an image file, if it exists
	 *
	 * @param string $path
	 * @return mixed
	 */
	private function getCreateDateFromExif($path)
	{
		$exif = exif_read_data($path);
		if (array_key_exists('DateTimeOriginal', $exif)) {
			$createDate = $exif['DateTimeOriginal'];
			return $createDate;
		} else {
			return 0;
		}
	}

	/**
	 * Create a plain-english title, using the file name
	 *
	 * @param string $fileName
	 * @return string
	 */
	private function getImageTitleFromFileName($fileName)
	{
		$imageTitle = str_replace('_', ' ', rawurldecode($fileName));
		$imageTitle = preg_replace('/\\.[^.\\s]{3,4}$/', '', $imageTitle);
		$imageTitle = ucfirst($imageTitle);
		return $imageTitle;
	}

	/**
	 * Create image thumbnails, and save them to a subdirectory "rc_thumbs/"
	 *
	 * @param string $directoryPath		path of the folder chosen for this gallery
	 * @param int $startHeight			in pixels
	 * @param int $thumbQuality			0 - 100
	 * @return void
	 */
	private function makeThumbnails($directoryPath, $startHeight, $thumbQuality)
	{
		$filter = $this->fileFilter();
		$files = JFolder::files($directoryPath, $filter);
		require_once JPATH_SITE.'/plugins/content/rc_gallery/ThumbnailFactory.php';

		foreach ($files as $file) {

			$fullFilePath = JPATH_ROOT . '/' . $directoryPath . $file;
			$thumbFolder = $directoryPath . 'rc_thumbs';

			if (!file_exists($thumbFolder)) {
				mkdir($thumbFolder);
			}

			foreach ($this->getThumbnailTypes() as $thumbnailType => $thumbnailTypeProperties) {
				$thumbSubFolder = $thumbFolder . '/' . $thumbnailType;

				if (!file_exists($thumbSubFolder)) {
					mkdir($thumbSubFolder);
				}

				$newExtension = (strpos($thumbnailType, 'webp') !== false)
					? '.webp'
					: '.jpg'
				;

				$extension = strrchr($file, '.');
				$extension = strtolower($extension);

				$thumbPath = $thumbSubFolder . '/' . 'thumb_' . str_replace($extension, $newExtension, $file);

				if (!file_exists($thumbPath)) {
					$this->makeSingleThumbnail($fullFilePath, $startHeight, $thumbPath, $thumbQuality, $thumbnailType);
				}
			}
		}
	}

	/**
	 * @param string $fullFilePath
	 * @param int $startHeight
	 * @param string $thumbPath
	 * @param int $thumbQuality
	 * @param string type
	 * @return void
	 */
	private function makeSingleThumbnail($fullFilePath, $startHeight, $thumbPath, $thumbQuality, $type)
	{
		$resizeObj = new ThumbnailFactory($fullFilePath);

		if ($resizeObj != false) { //don't bother resizing if an image couldn't be opened e.g. because of a bad path
			$resizeObj -> resizeImage($startHeight, $type);

			if ($thumbQuality > 100 || $thumbQuality < 0) $thumbQuality = 100;

			$resizeObj->saveImage($thumbPath, $thumbQuality, $type);
		}
	}

	/**
	 * @return stdClass
	 */
	public function getRCParams()
	{
		return $this->rcParams;
	}

	/**
	 * @param stdClass $params
	 * @return self
	 */
	public function setRCParams(stdClass $rcParams)
	{
		$this->rcParams = $rcParams;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getThumbnailTypes()
	{
		return $this->thumbnailTypes;
	}

	/**
	 * @param array $thumbnailTypes
	 * @return self
	 */
	public function setThumbnailTypes(array $thumbnailTypes)
	{
		$this->thumbnailTypes = $thumbnailTypes;

		return $this;
	}
}
