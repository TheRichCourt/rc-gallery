<?php

namespace RichCourt\Plugin\Content\RcGallery\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Event\Content\ContentPrepareEvent;
use Joomla\CMS\Language\Text;
use Joomla\Filesystem\Folder;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;
use Joomla\Registry\Registry;
use RichCourt\Plugin\Content\RcGallery\Helper\ParamsHelper;
use RichCourt\Plugin\Content\RcGallery\Helper\TagUtils;
use RichCourt\Plugin\Content\RcGallery\Helper\ThumbnailFactory;
use RichCourt\Plugin\Content\RcGallery\Model\LabelsModel;
use RichCourt\Plugin\Content\RcGallery\View\GalleryView;

class RcGallery extends CMSPlugin implements SubscriberInterface
{
    protected $autoloadLanguage = true;

    const GALLERY_TAG = 'gallery';

    /** @var int */
    private $galleryNumber = 1;

    /** @var \stdClass */
    private $rcParams;

    /** @var array */
    private $thumbnailTypes;

    /**
     * @param DispatcherInterface $dispatcher
     * @param array $config
     */
    public function __construct(DispatcherInterface $dispatcher, array $config)
    {
        parent::__construct($dispatcher, $config);

        $lowDpiQuery  = '(-webkit-max-resolution: 143dpi), (max-resolution: 143dpi)';
        $highDpiQuery = '(-webkit-min-resolution: 144dpi), (min-resolution: 144dpi)';

        $thumbnailTypes = [
            'jpg' => [
                'type'  => 'image/jpeg',
                'media' => $lowDpiQuery,
            ],
            'jpg-hdpi' => [
                'type'  => 'image/jpeg',
                'media' => $highDpiQuery,
            ],
        ];

        if (isset(gd_info()['WebP Support']) && gd_info()['WebP Support']) {
            $thumbnailTypes['webp'] = [
                'type'  => 'image/webp',
                'media' => $lowDpiQuery,
            ];
            $thumbnailTypes['webp-hdpi'] = [
                'type'  => 'image/webp',
                'media' => $highDpiQuery,
            ];
        }

        $this->setThumbnailTypes($thumbnailTypes);
    }

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onContentPrepare' => 'onContentPrepare',
            'onAjaxMakeThumbs' => 'onAjaxMakeThumbs',
        ];
    }

    /**
     * @param ContentPrepareEvent $event
     */
    public function onContentPrepare(ContentPrepareEvent $event): void
    {
        $article = $event->getItem();

        if (!isset($article->text) || strpos($article->text, '{gallery') === false) {
            return;
        }

        $this->showGalleries($article);
    }

    /**
     * AJAX handler for asynchronous thumbnail generation.
     *
     * @param Event $event
     */
    public function onAjaxMakeThumbs(Event $event): void
    {
        $app     = $this->getApplication();
        $input   = $app->getInput();
        $imgPath = JPATH_SITE . '/' . str_replace(Uri::root(), '', $input->post->getString('img', ''));

        $this->gatherParams();

        $this->makeThumbnailsForSingleImage($imgPath, (int) $input->post->getInt('start_height', 0), $this->getRcParams()->thumbquality);

        $result = [
            'success'  => true,
            'message'  => null,
            'messages' => null,
            'data'     => [
                ['thumbnail_result' => 'Success'],
            ],
        ];

        $event->setArgument('result', $result);
    }

    /**
     * Identify gallery tags, and replace them with an actual gallery.
     *
     * @param object $article
     */
    public function showGalleries(object &$article): void
    {
        $galleryTagMatches = TagUtils::findMatches($article->text);

        if ($galleryTagMatches === null) {
            return;
        }

        [$tagsAndContentsArray, $contentsArray] = $galleryTagMatches;

        $app          = $this->getApplication();
        $doc          = $app->getDocument();
        $wa           = $doc->getWebAssetManager();
        $pluginParams = new Registry($this->params);

        foreach ($tagsAndContentsArray as $index => $tagAndContents) {
            $tagContent = $contentsArray[$index];

            $this->gatherParams($tagAndContents, $pluginParams);

            $galleryContent = $this->buildGallery($tagContent, $wa);

            $article->text = str_replace($tagAndContents, $galleryContent, $article->text);
        }
    }

    /**
     * Combine inline params with the original plugin params.
     *
     * @param string $tag
     * @param Registry|null $pluginParams
     */
    private function gatherParams(string $tag = '', ?Registry $pluginParams = null): void
    {
        if (!$pluginParams) {
            $pluginParams = new Registry($this->params);
        }

        $paramsObj = new ParamsHelper($pluginParams, $tag);
        $this->setRCParams($paramsObj->getParams());
    }

    /**
     * Produce the filter to ensure only supported image files are used.
     *
     * @return string
     */
    public function fileFilter(): string
    {
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $allowedExtensions = array_merge($allowedExtensions, array_map('strtoupper', $allowedExtensions));
        $filter = '^.*\.(' . implode('|', $allowedExtensions) . ')$';

        return $filter;
    }

    /**
     * Build a single gallery.
     *
     * @param string $tagContent
     * @param \Joomla\CMS\WebAsset\WebAssetManager $wa
     * @return string
     */
    public function buildGallery(string $tagContent, $wa): string
    {
        $galleryView = new GalleryView($this->galleryNumber, $this->getRCParams(), $wa);

        $galleryView->includeCSSandJS($this->getRCParams()->thumbnailradius);
        $galleryView->includeCustomStyling();

        if ($this->getRCParams()->shadowboxoption == 0) {
            $galleryView->includeShadowbox();
        }

        if ($this->getRCParams()->shadowboxoption == 3) {
            $galleryView->includeRCShadowbox();
        }

        $directoryPath = $this->getRCParams()->galleryfolder . '/' . $tagContent . '/';
        $directoryPath = str_replace('//', '/', $directoryPath);
        $absolutePath  = JPATH_ROOT . '/' . $directoryPath;

        if (!file_exists($absolutePath)) {
            $galleryView->errorReport(Text::_('PLG_CONTENT_RC_GALLERY_ERROR_FOLDER_NOT_FOUND'), $tagContent, $this->getRCParams()->galleryfolder);
            return $galleryView->getHTML();
        }

        if ($this->getRcParams()->ajaximages == 0) {
            $this->makeThumbnails($absolutePath);
        }

        $directoryURL = $directoryPath;
        implode('/', array_map('rawurlencode', explode('/', $directoryURL)));

        $files = Folder::files($absolutePath, $this->fileFilter());

        switch ($this->getRCParams()->sorttype) {
            case 2:
                shuffle($files);
                break;
            case 1:
                $this->resortImagesByDate($files, $absolutePath, $this->getRCParams()->sortdesc);
                break;
            case 0:
            default:
                $this->resortImagesByFileName($files, $this->getRCParams()->sortdesc);
        }

        if (!$files) {
            $galleryView->errorReport(Text::_('PLG_CONTENT_RC_GALLERY_ERROR_NO_IMAGES'), $tagContent, $this->getRCParams()->galleryfolder);
            return $galleryView->getHTML();
        }

        if ($this->getRCParams()->uselabelsfile) {
            $labelsModel = new LabelsModel();
            $labelsModel->getLabelsFromFile($absolutePath);
        }

        foreach ($files as $file) {
            $fullFilePath  = $absolutePath . $file;
            $fullFileURL   = Uri::root() . $directoryURL . rawurlencode($file);

            list($x, $y, $type, $attr) = getimagesize($fullFilePath);

            $width  = $x;
            $height = $y;

            if (function_exists('exif_read_data')) {
                switch (strtolower(pathinfo($fullFilePath, PATHINFO_EXTENSION))) {
                    case 'jpeg':
                    case 'jpg':
                        $exif = @exif_read_data($fullFilePath);
                        if ($exif !== false) {
                            if (isset($exif['Orientation'])) {
                                switch ($exif['Orientation']) {
                                    case 6:
                                    case 8:
                                        $width  = $y;
                                        $height = $x;
                                        break;
                                }
                            }
                        }
                        break;
                }
            }

            if ($this->getRCParams()->minrowheight == 0) {
                $imgWidth = 100;
            }

            if ($height == 0) {
                $height = $this->getRCParams()->minrowheight;
            }

            $ratio    = $height / $width;
            $imgWidth = $this->getRCParams()->minrowheight / $ratio;

            if ($this->getRCParams()->uselabelsfile) {
                if (!$labelsModel->getTitle($file)) {
                    $imgTitle = $this->getImageTitleFromFileName($file);
                } else {
                    $imgTitle = $labelsModel->getTitle($file);
                }
            } else {
                $imgTitle = $this->getImageTitleFromFileName($file);
            }

            $withLink = ($this->getRCParams()->shadowboxoption != 2);

            $galleryView->addImage(
                $fullFileURL,
                Uri::root() . $directoryPath,
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

        return $galleryView->getHTML();
    }

    /**
     * @param array &$files
     * @param bool $desc
     */
    private function resortImagesByFileName(array &$files, $desc = false): void
    {
        if ($desc) {
            arsort($files);
        }
    }

    /**
     * @param array &$files
     * @param string $folderPath
     * @param bool $desc
     */
    private function resortImagesByDate(array &$files, string $folderPath, $desc = false): void
    {
        $newFilesWithCreateDate = [];

        foreach ($files as $file) {
            $createDate = $this->getCreateDateFromExif($folderPath . $file);
            $newFile = [
                'path'       => $file,
                'createdate' => $createDate,
            ];
            $newFilesWithCreateDate[] = $newFile;
        }

        $path = [];
        $createdate = [];

        foreach ($newFilesWithCreateDate as $key => $row) {
            $path[$key]       = $row['path'];
            $createdate[$key] = $row['createdate'];
        }

        if (!$desc) {
            array_multisort($createdate, SORT_ASC, $path, SORT_ASC, $newFilesWithCreateDate);
        } else {
            array_multisort($createdate, SORT_DESC, $path, SORT_DESC, $newFilesWithCreateDate);
        }

        $newFiles = [];

        foreach ($newFilesWithCreateDate as $a) {
            $newFiles[] = $a['path'];
        }

        $files = $newFiles;
    }

    /**
     * @param string $path
     * @return mixed
     */
    private function getCreateDateFromExif(string $path)
    {
        if (!function_exists('exif_read_data')) {
            return 0;
        }

        switch (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            case 'jpeg':
            case 'jpg':
                $exif = @exif_read_data($path);

                if ($exif === false) {
                    return 0;
                }

                if (array_key_exists('DateTimeOriginal', $exif)) {
                    return $exif['DateTimeOriginal'];
                } else {
                    return 0;
                }

                break;
            default:
                return 0;
        }
    }

    /**
     * @param string $fileName
     * @return string
     */
    private function getImageTitleFromFileName(string $fileName): string
    {
        $imageTitle = str_replace('_', ' ', rawurldecode($fileName));
        $imageTitle = preg_replace('/\\.[^.\\s]{3,4}$/', '', $imageTitle);
        $imageTitle = ucfirst($imageTitle);

        return $imageTitle;
    }

    /**
     * @param string $directoryPath
     */
    private function makeThumbnails(string $directoryPath): void
    {
        $filter = $this->fileFilter();
        $files  = Folder::files($directoryPath, $filter);

        foreach ($files as $file) {
            $this->makeThumbnailsForSingleImage(
                $directoryPath . $file,
                $this->getRCParams()->minrowheight,
                $this->getRCParams()->thumbquality
            );
        }
    }

    /**
     * @param string $fullFilePath
     * @param int $startHeight
     * @param int $thumbQuality
     */
    private function makeThumbnailsForSingleImage(string $fullFilePath, int $startHeight, int $thumbQuality): void
    {
        $thumbFolder = dirname($fullFilePath) . '/rc_thumbs';
        $resizeObj   = null;

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
                : '.jpg';

            $extension = strrchr($fullFilePath, '.');
            $extension = strtolower($extension);

            $thumbPath = $thumbSubFolder . '/thumb_' . str_replace($extension, $newExtension, basename($fullFilePath));

            if (!file_exists($thumbPath)) {
                if ($resizeObj === null) {
                    $resizeObj = new ThumbnailFactory($fullFilePath);

                    if ($resizeObj->getImage() === false) {
                        return;
                    }
                }

                $resizeObj->resizeImage($startHeight, $thumbnailType);

                $thumbQuality = ($thumbQuality > 100 || $thumbQuality < 0)
                    ? 100
                    : $thumbQuality;

                $resizeObj->saveImage($thumbPath, $thumbQuality, $thumbnailType);
            }
        }
    }

    /**
     * @param string $fullFilePath
     * @return bool
     */
    private function thumbnailsExist(string $fullFilePath): bool
    {
        $thumbFolder = dirname($fullFilePath) . '/rc_thumbs';

        foreach ($this->getThumbnailTypes() as $thumbnailType => $thumbnailTypeProperties) {
            $thumbSubFolder = $thumbFolder . '/' . $thumbnailType;

            $newExtension = (strpos($thumbnailType, 'webp') !== false)
                ? '.webp'
                : '.jpg';

            $extension = strrchr($fullFilePath, '.');
            $extension = strtolower($extension);

            $thumbPath = $thumbSubFolder . '/thumb_' . str_replace($extension, $newExtension, basename($fullFilePath));

            if (!file_exists($thumbPath)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return \stdClass
     */
    public function getRCParams(): \stdClass
    {
        return $this->rcParams;
    }

    /**
     * @param \stdClass $rcParams
     * @return self
     */
    public function setRCParams(\stdClass $rcParams): self
    {
        $this->rcParams = $rcParams;

        return $this;
    }

    /**
     * @return array
     */
    public function getThumbnailTypes(): array
    {
        return $this->thumbnailTypes;
    }

    /**
     * @param array $thumbnailTypes
     * @return self
     */
    public function setThumbnailTypes(array $thumbnailTypes): self
    {
        $this->thumbnailTypes = $thumbnailTypes;

        return $this;
    }
}
