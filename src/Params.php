<?php

defined('_JEXEC') or die;

use Joomla\Registry\Registry;

class Params
{
    /** @var Registry */
    private $pluginParams;

    /** @var array */
    private $overrideParams = [];

    /** @var array */
    private $paramsMap;

    /** @var array */
    private $defaultValues;

    /** @var stdClass */
    private $params;

    /**
     * @param Registry $pluginParams
     * @param array $overridePluginParams
     */
    public function __construct(Registry $pluginParams, $galleryTagString = '')
    {
        $this->setPluginParams($pluginParams);

        $this->setParamsMap([
            'galleryfolder' => 'root-image-folder',
            'minrowheight' => 'target-row-height',
            'imagemargin' => 'image-margin-size' ,
            'imageTitle' => 'image-title-option',
            'uselabelsfile' => 'use-labels-file',
            'shadowboxoption' => 'use-shadowbox',
            'usetitleasalt' => 'use-title-as-alt',
            'sorttype' => 'sort-type',
            'sortdesc' => 'sort-desc',
            'thumbquality' => 'thumb-quality',
            'thumbnailfilter' => 'thumbnail-filter',
            'titletextoverflow' => 'title-text-overflow',
            'thumbbgcolour' => 'thumb-bg-colour',
            'thumbnailradius' => 'thumbnail-radius',
            'titletextcolour' => 'title-text-colour',
            'titletextsize' => 'title-text-size',
            'titletextalign' => 'title-text-align',
            'titletextweight' => 'title-text-weight',
            'overlaycolour' => 'overlay-colour',
            'overlayopacity' => 'overlay-opacity',
            'overlayblur' => 'overlay-blur',
            'shadowboxtitle' => 'shadowbox-title-option',
            'ajaximages' => 'ajaximages',
            'layout' => 'layout',
            'shadowboxanimations' => 'shadowboxanimations',
            'hidescrollbar' => 'hidescrollbar',
        ]);

        $this->setDefaultValues([
            'galleryfolder' => 'images',
            'minrowheight' => 100,
            'imagemargin' => 2,
            'imageTitle' => 0,
            'uselabelsfile' => 0,
            'shadowboxoption' => 0,
            'usetitleasalt' => 1,
            'sorttype' => 0,
            'sortdesc' => false,
            'thumbquality' => 100,
            'thumbnailfilter' => 0,
            'titletextoverflow' => 'hidden',
            'thumbbgcolour' => '#f2f2f2',
            'thumbnailradius' => '0',
            'titletextcolour' => '#fff',
            'titletextsize' => 14,
            'titletextalign' => 'left',
            'titletextweight' => 'bold',
            'overlaycolour' => 'rgba(0, 0, 0, .85)',
            'overlayopacity' => '1',
            'overlayblur' => 0,
            'shadowboxtitle' => 0,
            'ajaximages' => 0,
            'layout' => null,
            'shadowboxanimations' => 'default',
            'hidescrollbar' => 0,
        ]);

        if ($galleryTagString !== '') {
            $this->setOverrideParams($this->assessOverrideParams($galleryTagString));
        }

        $this->setParams($this->combineParams());
    }

    /**
     * Puts params into a simple keyed array, overriding any params that are set in overrideParams
     *
     * @return stdClass
     */
    public function combineParams()
    {
        $combinedParams = [];

        foreach ($this->getDefaultValues() as $paramName => $defaultValue) {
            $overrideParamName = $this->getParamsMap()[$paramName];

            if (isset($this->getOverrideParams()[$overrideParamName])) {
                $combinedParams[$paramName] = $this->getOverrideParams()[$overrideParamName];
            } else {
                $combinedParams[$paramName] = $this->getPluginParams()->get($paramName, $defaultValue);
            }
        }

        return json_decode(json_encode($combinedParams));
    }

    /**
     * @param string $inlineParams
     * @return array
     */
    private function assessOverrideParams($galleryTag)
    {
        // get inline params from the opening gallery tag
        $tagContent = preg_replace("/{.+?}/", "", $galleryTag);
        $inlineParamsString = str_replace('{gallery ', '', $galleryTag);
        $inlineParamsString = str_replace('{/gallery}', '', $inlineParamsString);
        $inlineParamsString = str_replace('}' . $tagContent, '', $inlineParamsString); // will end up as '{gallery' if there were no inline params

        if ($inlineParamsString == '{gallery') {
            return []; // there aren't any inline params, so just leave it alone
        }

        $inlineParamsArray = explode(' ', $inlineParamsString);

        $overrideParams = [];

        foreach ($inlineParamsArray as $inlineParam) {
            $key =  substr($inlineParam, 0, strpos($inlineParam, '='));
            $value = str_replace('"', '', substr($inlineParam, strpos($inlineParam, '=') + 1, strlen($inlineParam) - strpos($inlineParam, '=')));
            $overrideParams[$key] = $value;
        };

        return $overrideParams;
    }

    /**
     * @return array
     */
    public function getOverrideParams()
    {
        return $this->overrideParams;
    }

    /**
     * @param array $overridePluginParams
     * @return self
     */
    public function setOverrideParams(array $overrideParams)
    {
        $this->overrideParams = $overrideParams;
        return $this;
    }

    /**
     * @return Registry
     */
    public function getPluginParams()
    {
        return $this->pluginParams;
    }

    /**
     * @var Registry $pluginParams
     * @return  self
     */
    public function setPluginParams(Registry $pluginParams)
    {
        $this->pluginParams = $pluginParams;
        return $this;
    }

    /**
     * @return array
     */
    public function getParamsMap()
    {
        return $this->paramsMap;
    }

    /**
     * @param array $paramsMap
     * @return  self
     */
    public function setParamsMap(array $paramsMap)
    {
        $this->paramsMap = $paramsMap;
        return $this;
    }

    /**
     * @return array
     */
    public function getDefaultValues()
    {
        return $this->defaultValues;
    }

    /**
     * @param array $defaultValues
     * @return  self
     */
    public function setDefaultValues(array $defaultValues)
    {
        $this->defaultValues = $defaultValues;
        return $this;
    }

    /**
     * @return stdClass
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @param stdClass $params
     * @return self
     */
    public function setParams(stdClass $params)
    {
        $this->params = $params;
        return $this;
    }
}
