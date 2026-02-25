<?php

namespace RichCourt\Plugin\Content\RcGallery\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

class TagUtils
{
    const GALLERY_TAG_REGEX = '/\{gallery.*?\}(.*?)\{\/gallery\}/is';

    /**
     * Find all {gallery} tag matches.
     *
     * @param string $articleText
     * @return string[]|null (null if no matches found)
     */
    public static function findMatches($articleText)
    {
        $tagMatches = [];

        $result = preg_match_all(
            self::GALLERY_TAG_REGEX,
            $articleText,
            $tagMatches,
            PREG_PATTERN_ORDER
        );

        if ($result === false) {
            throw new \RuntimeException(Text::_('PLG_CONTENT_RC_GALLERY_ERROR_FINDING_TAGS'));
        }

        if ($result === 0) {
            return null;
        }

        return $tagMatches;
    }
}
