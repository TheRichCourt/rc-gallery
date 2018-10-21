jQuery(document).ready(function() {
    resizeGallery();
    lazyLoadImages();
});

jQuery(window).resize(function() {
    resizeGallery();
});

function lazyLoadImages() {
    jQuery(".rc_galleryimg_container").each(function () {
        if (jQuery(this).attr("data-thumbs-exist") == "true") {
            populateThumbnail(this);
            return;
        }

        var imgUrl = jQuery(this).parent().attr('href');
        var xhttp = new XMLHttpRequest();
        var startHeight = jQuery('.rc_gallery').attr("data-start-height");
        var requestURl = 'http://localhost/2018JoomlaTransferTest/?option=com_ajax&group=content&plugin=rc_gallery&format=json&img=' + imgUrl + '&start_height=' + startHeight;
        var $container = this;

        xhttp.onreadystatechange = function () {
            if (this.readyState == 4 && this.status == 200) {
                var response = JSON.parse(this.responseText);
                populateThumbnail($container);
            }
        };

        xhttp.open("GET", requestURl, true);
        xhttp.send();
    });
}

function populateThumbnail($container) {
    jQuery($container).find("picture").children().each(function () {
        if (jQuery(this).prop("tagName") == "IMG") {
            jQuery(this).attr("src", jQuery(this).attr("data-src"));
            jQuery(this).attr("alt", jQuery(this).attr("data-alt"));
        } else if (jQuery(this).prop("tagName") == "SOURCE") {
            jQuery(this).attr("srcset", jQuery(this).attr("data-srcset"));
        }
    });
}

function resizeGallery() {
    jQuery('.rc_gallery').each(function() {
        //get the params for this gallery
        var startHeight = jQuery(this).attr("data-start-height");
        var marginSize = jQuery(this).attr("data-margin-size");
        var targetLineWidth = jQuery(this).width() - 1; //1px narrower to avoid raounding errors messing everything up
        var currentLineWidth = 0;
        var imageWidthArray = new Array();
        var imageRatioArray = new Array();
        var imageLineNumberArray = new Array();
        var lineWidthArray = new Array();
        var imageID = 0;
        var lineID = 0;

        var imageCount = jQuery(this).find('.rc_galleryimg').length;

        // get the data we need about each image
        jQuery(this).find('.rc_galleryimg').each(function() {
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
        var targetImgWidthArray = new Array();
        var targetLineHeightArray = new Array();
        var currentLineNo = -1;

        // now calculate the target widths etc
        for (var i = 0; i <= imageCount; i++) {
            var imgWidth = imageWidthArray[i];
            var lineNo = imageLineNumberArray[i];
            var lineWidth = lineWidthArray[lineNo];

            if (!lineWidth) {
                lineWidth = targetLineWidth - (2 * marginSize);
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
        jQuery(this).find('.rc_galleryimg').each(function() {
            jQuery(this).width(targetImgWidthArray[imageID]);
            jQuery(this).height(targetLineHeightArray[imageLineNumberArray[imageID]]);
            imageID++;
        });
    });
}
