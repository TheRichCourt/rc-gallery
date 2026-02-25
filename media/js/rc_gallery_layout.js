jQuery(document).ready(function () {
    resizeGallery();
});

jQuery(window).resize(function () {
    resizeGallery();
});

function resizeGallery()
{
    jQuery('.rc_gallery').each(function () {
        //get the params for this gallery
        var startHeight = jQuery(this).attr("data-startheight");
        var marginSize = jQuery(this).attr("data-marginsize");
        var targetLineWidth = jQuery(this).width() - 1; //1px narrower to avoid rounding errors messing everything up
        var currentLineWidth = 0;
        var imageWidthArray = [];
        var imageRatioArray = [];
        var imageLineNumberArray = [];
        var lineWidthArray = [];
        var imageID = 0;
        var lineID = 0;

        var imageCount = jQuery(this).find('.rc_galleryimg').length;

        // get the data we need about each image
        jQuery(this).find('.rc_galleryimg').each(function () {
            var w = jQuery(this).attr("data-width");
            var h = jQuery(this).attr("data-height");

            imageRatioArray[imageID] = w / h;
            jQuery(this).height(startHeight);
            jQuery(this).width((startHeight / h) * w);

            // if this image would put us over the target width by more than 50% of its width...
            if (currentLineWidth + ((jQuery(this).width() + (2 * marginSize)) / 2) >= targetLineWidth) {
                // put the image on a new line
                lineWidthArray[lineID] = currentLineWidth;
                lineID++;
                currentLineWidth = jQuery(this).width() + (2 * marginSize);
            } else {
                // add the image to the current line
                currentLineWidth = currentLineWidth + jQuery(this).width() + (2 * marginSize);
                // if it's the very last image, and it takes us over the target line width...
                    // note that this will be by less than 50% the width of the image, because of the parent if statement

                if (imageID == imageCount - 1 && currentLineWidth < targetLineWidth) {
                    lineWidthArray[lineID] = targetLineWidth;
                } else {
                    lineWidthArray[lineID] = currentLineWidth;
                }
            }
            imageWidthArray[imageID] = jQuery(this).width() + (2 * marginSize);
            imageLineNumberArray[imageID] = lineID;
            imageID++;
        });

        var imageCount = imageWidthArray.length;
        var targetImgWidthArray = [];
        var targetLineHeightArray = [];
        var currentLineNo = -1;

        // now calculate the target widths etc
        for (var i = 0; i <= imageCount; i++) {
            var imgWidth = imageWidthArray[i];
            var lineNo = imageLineNumberArray[i];
            var lineWidth = lineWidthArray[lineNo];

            if (!lineWidth) {
                // -1 so we don't go over because of rounding
                lineWidth = targetLineWidth - (2 * marginSize) - 1;
            }

            targetImgWidthArray[i] = ((imgWidth / lineWidth) * targetLineWidth) - (2 * marginSize);

            if (targetImgWidthArray[i] + (2 * marginSize) > (targetLineWidth - (2 * marginSize))) {
                targetImgWidthArray[i] = targetLineWidth - (2 * marginSize);
            }

            if (currentLineNo != lineNo) {
                targetLineHeightArray[lineNo] = (targetImgWidthArray[i] - (2 * marginSize)) / imageRatioArray[i];
                currentLineNo = lineNo;
            }
        }
        imageID = 0;

        // do the resizing
        jQuery(this).find('.rc_galleryimg').each(function () {
            jQuery(this).width(targetImgWidthArray[imageID]);
            jQuery(this).height(targetLineHeightArray[imageLineNumberArray[imageID]]);
            imageID++;
        });
    });
}
