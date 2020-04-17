<?php

defined('_JEXEC') or die;

jimport('joomla.plugin.plugin');

use Joomla\CMS\Document\HtmlDocument;
use Joomla\Registry\Registry;

// @codingStandardsIgnoreStart
class PlgContentRC_gallery extends JPlugin
// @codingStandardsIgnoreEnd
{
    const GALLERY_TAG = "gallery"; //the bit to look for in curly braces, e.g. {gallery}photos{gallery}

    /** @var int */
    private $galleryNumber = 1;

    /** @var stdClass */
    private $rcParams;

    /** @var array */
    private $thumbnailTypes;

    /**
     * @param string $subject
     * @param array $params
     */
    public function __construct(&$subject, $params)
    {
        parent::__construct($subject, $params);

        $lowDpiQuery =
            '(-webkit-max-resolution: 143dpi), (max-resolution: 143dpi)'
        ;

        $highDpiQuery =
            '(-webkit-min-resolution: 144dpi), (min-resolution: 144dpi)'
        ;

        $thumbnailTypes = [
            'jpg' => [
                'type' => 'image/jpeg',
                'media' => $lowDpiQuery,
            ],
            'jpg-hdpi' => [
                'type' => 'image/jpeg',
                'media' => $highDpiQuery,
            ],
        ];

        // Only do WebP if the server supports it (please contact your hosting provider if it doesn't)
        if (isset(gd_info()['WebP Support']) && gd_info()['WebP Support']) {
            $thumbnailTypes['webp'] = [
                'type' => 'image/webp',
                'media' => $lowDpiQuery,
            ];

            $thumbnailTypes['webp-hdpi'] = [
                'type' => 'image/webp',
                'media' => $highDpiQuery,
            ];
        }

        $this->setThumbnailTypes($thumbnailTypes);
    }

    public function onContentPrepareForm($form, $data)
    {
        JForm::addFieldPath(__DIR__ . '/fields');
    }

    /**
     * Method to hook into Joomla to alter the article
     *
     * @param object $context
     * @param object $article
     * @param object $params
     * @param int $page
     * @return void
     */
    public function onContentPrepare($context, &$article, &$params, $page = 0)
    {
        if (strpos($article->text, '{gallery') === false) {
            return;
        }

        $this->showGalleries($article);
    }

    /**
     * For building thumbnails asynchronously, to avoid page timeouts when there are a lot of them
     * ?option=com_ajax&group=content&plugin=MakeThumbs&format=json&img=[image path]&start_height=[start height]
     *
     * @return array
     */
    public function onAjaxMakeThumbs()
    {
        jimport('joomla.filesystem.folder');

        $imgPath = JPATH_SITE . str_replace(JURI::root(), '', $_POST['img']);

        $this->gatherParams();

        $this->makeThumbnailsForSingleImage($imgPath, (int) $_POST['start_height'], $this->getRcParams()->thumbquality);

        // don't seem to be able to trust Joomla to reliably only return the JSON, so do this:
        echo json_encode([
            "success" => true,
            "message" => null,
            "messages" => null,
            "data" => [
                ["thumbnail_result" => "Success"],
            ],
        ]);
        exit;
    }

    /**
     * Identify gallery tags, and replace them with an actual gallery
     *
     * @param object $article
     * @return void
     */
    public function showGalleries(&$article)
    {
        require_once __DIR__ . '/src/utils/RCGalleryTagUtils.php';

        $galleryTagMatches = RCGalleryTagUtils::findMatches($article->text);

        if ($galleryTagMatches === null) {
            return;
        }

        list($tagsAndContentsArray, $contentsArray) = $galleryTagMatches;

        $doc = JFactory::getDocument();
        $plugin = JPluginHelper::getPlugin('content', 'rc_gallery');
        $pluginParams = new JRegistry($plugin->params);

        // start the replace loop
        foreach ($tagsAndContentsArray as $index => $tagAndContents) {
            // Get the given foldername
            $tagContent = $contentsArray[$index];

            // These params include inline options from the gallery tag, so create for each gallery on the page
            $this->gatherParams($tagAndContents, $pluginParams);

            // put the gallery together
            $galleryContent = $this->buildGallery($tagContent, $doc);

            //do the replace
            $article->text = str_replace($tagAndContents, $galleryContent, $article->text);
        }
    }

    /**
     * Combine inline params with the original plugin params
     *
     * @param string $tag
     * @param Registry|null $pluginParams
     * @return void
     */
    private function gatherParams($tag = '', $pluginParams = null)
    {
        if (!$pluginParams) {
            $plugin = JPluginHelper::getPlugin('content', 'rc_gallery');
            $pluginParams = new JRegistry($plugin->params);
        }

        require_once JPATH_SITE . '/plugins/content/rc_gallery/src/Params.php';
        $paramsObj = new Params($pluginParams, $tag);
        $this->setRCParams($paramsObj->getParams());
    }

    /**
     * Produce the filter to ensure only supported image files are used
     *
     * @return string
     */
    public function fileFilter()
    {
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        // Also allow filetypes in uppercase
        $allowedExtensions = array_merge($allowedExtensions, array_map('strtoupper', $allowedExtensions));
        // Build the filter. Will return something like: "jpg|png|JPG|PNG|gif|GIF"
        $filter = implode('|', $allowedExtensions);
        $filter = "^.*\.(" . implode('|', $allowedExtensions) . ")$";

        return $filter;
    }

    /**
     * Build a single gallery
     *
     * @param string $tagContent
     * @param HtmlDocument $doc
     * @return string
     */
    public function buildGallery($tagContent, $doc)
    {
        jimport('joomla.filesystem.folder');

        // Get the view class
        require_once JPATH_SITE . '/plugins/content/rc_gallery/src/views/GalleryView.php';
        $galleryView = new GalleryView($this->galleryNumber, $this->getRCParams(), $doc);

        //css and js files
        $galleryView->includeCSSandJS($this->getRCParams()->thumbnailradius);
        $galleryView->includeCustomStyling();

        if ($this->getRCParams()->shadowboxoption == 0) {
            $galleryView->includeShadowbox(); //i.e. we want to use the included shadowbox
        }

        if ($this->getRCParams()->shadowboxoption == 3) {
            $galleryView->includeRCShadowbox(); //i.e. we want to use the shiny new shadowbox!
        }

        //get all image files from the directory
        $directoryPath =  $this->getRCParams()->galleryfolder . '/' . $tagContent . '/';
        $directoryPath = str_replace('//', '/', $directoryPath); //in case there were unnecessary leading or trailing slashes in the param

        if (!file_exists($directoryPath)) {
            $galleryView->errorReport('Image folder not found.', $tagContent, $this->getRCParams()->galleryfolder);
            return $galleryView->getHTML();
        }

        if ($this->getRcParams()->ajaximages == 0) {
            $this->makeThumbnails($directoryPath);
        }

        //Get the directory URL, and sort out spaces etc
        $directoryURL = $directoryPath;
        implode('/', array_map('rawurlencode', explode('/', $directoryURL)));

        $files = JFolder::files($directoryPath, $this->fileFilter());

        switch ($this->getRCParams()->sorttype) {
            case 2:
                shuffle($files);
                break;
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
            require_once JPATH_SITE . '/plugins/content/rc_gallery/src/models/rc_labels_model.php';
            $labelsModel = new RCLabels();
            $labelsModel->getLabelsFromFile($directoryPath);
        }

        foreach ($files as $file) {
            //Get full paths
            $fullFilePath = JPATH_ROOT . '/' . $directoryPath . $file;
            $thumbFilePath = JPATH_ROOT . '/' . $directoryPath . 'rc_thumbs/jpg/' . 'thumb_' . $file;

            //Get full URLs
            $fullFileURL = JURI::root() . $directoryURL . rawurlencode($file);
            $thumbFileURL = JURI::root() .  $directoryURL . 'rc_thumbs/' . 'thumb_' .  rawurlencode($file);

            //get the width and height of the image file
            list($x, $y, $type, $attr) = getimagesize($fullFilePath);

            // initially go with the obvious values
            $width = $x;
            $height = $y;

            if (function_exists('exif_read_data')) {
                // now read the exif Orientation, and swap those dimensions if necessary
                switch (strtolower(pathinfo($fullFilePath, PATHINFO_EXTENSION))) {
                    case "jpeg":
                    case "jpg":
                        // Okay, I don't like suppressing warnings, but there are bugs in some PHP versions, and so I don't have much choice
                        // see https://stackoverflow.com/questions/37352371/php-exif-read-data-illegal-ifd-size
                        $exif = @exif_read_data($fullFilePath);
                        if ($exif !== false) {
                            if (isset($exif['Orientation'])) {
                                switch ($exif['Orientation']) {
                                    case 6:
                                    case 8:
                                        $width = $y;
                                        $height = $x;
                                        break;
                                }
                            }
                        }
                        break;
                }
            }

            if ($this->getRCParams()->minrowheight == 0) {
                $imgWidth = 100; //Just in case
            }

            if ($height == 0) {
                $height = $this->getRCParams()->minrowheight; //just in case
            }

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
                JURI::root() . $directoryPath,
                rawurlencode($file),
                $height,
                $width,
                $withLink,
                $imgTitle,
                $this->getThumbnailTypes(),
                $this->thumbnailsExist($fullFilePath)
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
        $newFilesWithCreateDate = [];

        foreach ($files as $file) {
            $createDate = $this->getCreateDateFromExif($folderPath . $file);
            $newFile = [
                "path" => $file,
                "createdate" => $createDate,
            ];
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
        $newFiles = [];

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
        if (!function_exists('exif_read_data')) {
            return 0;
        }

        switch (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            case "jpeg":
            case "jpg":
                // Okay, I don't like suppressing warnings, but there are bugs in some PHP versions, and so I don't have much choice
                // see https://stackoverflow.com/questions/37352371/php-exif-read-data-illegal-ifd-size
                $exif = @exif_read_data($path);

                if (array_key_exists('DateTimeOriginal', $exif)) {
                    $createDate = $exif['DateTimeOriginal'];

                    return $createDate;
                } else {
                    return 0;
                }

                break;
            default:
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
     * @param string $directoryPath     path of the folder chosen for this gallery
     * @param int $startHeight          in pixels
     * @param int $thumbQuality         0 - 100
     * @return void
     */
    private function makeThumbnails($directoryPath)
    {
        $filter = $this->fileFilter();
        $files = JFolder::files($directoryPath, $filter);

        foreach ($files as $file) {
            $this->makeThumbnailsForSingleImage(
                JPATH_ROOT . '/' . $directoryPath . $file,
                $this->getRCParams()->minrowheight,
                $this->getRCParams()->thumbquality
            );
        }
    }

    /**
     * @param string $fullFilePath
     * @param int $startHeight
     * @param int $thumbQuality
     * @return void
     */
    private function makeThumbnailsForSingleImage($fullFilePath, $startHeight, $thumbQuality)
    {
        $thumbFolder = dirname($fullFilePath) . '/rc_thumbs';
        $resizeObj = null;

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

            $extension = strrchr($fullFilePath, '.');
            $extension = strtolower($extension);

            $thumbPath = $thumbSubFolder . '/' . 'thumb_' . str_replace($extension, $newExtension, basename($fullFilePath));

            if (!file_exists($thumbPath)) {
                if ($resizeObj === null) {
                    require_once JPATH_SITE . '/plugins/content/rc_gallery/src/ThumbnailFactory.php';
                    $resizeObj = new ThumbnailFactory($fullFilePath);

                    if ($resizeObj == false) {
                        return;
                    }
                }

                $resizeObj->resizeImage($startHeight, $thumbnailType);

                $thumbQuality = ($thumbQuality > 100 || $thumbQuality < 0)
                    ? 100
                    : $thumbQuality
                ;

                $resizeObj->saveImage($thumbPath, $thumbQuality, $thumbnailType);
            }
        }
    }

    /**
     * @todo: Haven't implemented this yet. Might need to move this to another class.
     *
     * @param string $fullFilePath
     * @return bool
     */
    private function thumbnailsExist($fullFilePath)
    {
        $thumbsExist = true;

        $thumbFolder = dirname($fullFilePath) . '/rc_thumbs';

        foreach ($this->getThumbnailTypes() as $thumbnailType => $thumbnailTypeProperties) {
            $thumbSubFolder = $thumbFolder . '/' . $thumbnailType;

            $newExtension = (strpos($thumbnailType, 'webp') !== false)
                ? '.webp'
                : '.jpg'
            ;

            $extension = strrchr($fullFilePath, '.');
            $extension = strtolower($extension);

            $thumbPath = $thumbSubFolder . '/' . 'thumb_' . str_replace($extension, $newExtension, basename($fullFilePath));

            if (!file_exists($thumbPath)) {
                return false;
            }
        }

        return true;
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
