<?php

class RCGalleryTagUtils
{
    const GALLERY_TAG_REGEX = '/\{gallery.*?\}(.*?)\{\/gallery\}/is';

    /**
     * Find all {gallery} tag matches
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
            throw new RuntimeException(JText::_('PLG_CONTENT_RCGALLERY_ERROR_NO_TAG_FOUND'));
        }

        if ($result === 0) {
            return null;
        }

        return $tagMatches;
    }
}
