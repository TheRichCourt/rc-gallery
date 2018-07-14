// Copyright Rich Court, January 2018
var rc_sb_slideURLs = new Array;
var rc_sb_slideIDs = new Array;
var rc_sb_slideRootElements = new Array;
var rc_sb_slideTitles = new Array;
var rc_sb_currentSlideID;
var rc_sb_keyPressed = false;
var rc_sb_currentPosition;
var rc_sb_shadowboxHTML;
var rc_sb_buttonID = 1;
var rc_sb_toolbarAddonButtonsHTML = '';
var rc_sb_addonFunctions = new Array();
var rc_sb_runAfter = true;

jQuery(function() // document ready
{
    // put the html in place, ready to be used
    rc_sb_initialiseShadowboxMarkup();

    // find all the shadowbox links
    rc_sb_setupSlides();
    // preload all the UI images
    rc_sb_preload(rc_sb_params['image_folder'] + 'loading.gif', function() {});
    rc_sb_preload(rc_sb_params['image_folder'] + 'close.png', function() {});
    rc_sb_preload(rc_sb_params['image_folder'] + 'prev.png', function() {});
    rc_sb_preload(rc_sb_params['image_folder'] + 'next.png', function() {});

    // if we're loading a page with an anchor link URL, open the slide
    rc_sb_openAnchorLinkedSlide();
});

function rc_sb_insertButton(buttonTitle, buttonClass, buttonFunction, buttonLinkURL)
{
    var html = '';

    if (typeof buttonLinkURL != 'undefined') {
        html += '<a href="' + buttonLinkURL + '" title="' + buttonTitle + '">';
    }

    var fullButtonID = 'rc_sb_addonbutton_' + rc_sb_buttonID;
    html += '<div title="' + buttonTitle + '" class="rc_sb_button rc_sb_addonbutton ' + buttonClass + '" id="' + fullButtonID + '"></div>';

    if (typeof buttonLinkURL != 'undefined') {
        html += '</a>';
    }

    rc_sb_toolbarAddonButtonsHTML += html;
    // add the function to the array of functions...
    rc_sb_addonFunctions[rc_sb_buttonID] = function() {
        buttonFunction();
    }
    rc_sb_buttonID++;
}

jQuery(window).on('hashchange', function()
{
   // if a hash link to one of the slides is clicked...
   rc_sb_openAnchorLinkedSlide();
});

function rc_sb_preload(imageSrc, after)
{
	rc_sb_runAfter = true; //by default we always want to run the 'after' function
	jQuery('<img/>').load(function() {
		if (rc_sb_runAfter) { //if things have changed while loading the image, we don't run this bit
			after();
		}
	}).attr('src', imageSrc);
}

function rc_sb_setupSlides()
{
    //get details for all potential slides
    jQuery("a[rel^='shadowbox']").each(function() {
        rc_sb_slideURLs[rc_sb_slideURLs.length] = jQuery(this).attr('href');
        rc_sb_slideIDs[rc_sb_slideIDs.length] = 'rc_sb_slide_' + rc_sb_slideIDs.length;
        rc_sb_slideRootElements[rc_sb_slideRootElements.length] = this;
        rc_sb_slideTitles[rc_sb_slideTitles.length] = this.getAttribute("data-image-title");
    });

    jQuery("a[rel^='shadowbox']").on('click', function(e) {
        e.preventDefault(); //stop the link from being followed
        rc_sb_createShadowbox();
        rc_sb_openSlide(rc_sb_slideURLs.indexOf(jQuery(this).attr('href')), false, 280);
    });
}

function rc_sb_openSlide(slideID, after, delay)
{
    // fetch the image first...
    rc_sb_preload(rc_sb_slideURLs[slideID], function() {
        // these bits won't run until the image has loaded
        rc_sb_giveFocus(slideID, delay);
        rc_sb_currentSlideID = slideID;
        rc_sb_updateNavigationButtons(slideID);
        // put the img source in!
        jQuery('#' + rc_sb_slideIDs[slideID]).attr('src', rc_sb_slideURLs[slideID]);
    });

    // now put the html together and insert the element
    var html = '<img class="rc_sb_image" id="' + rc_sb_slideIDs[slideID] +  '" />';

    if (jQuery('.rc_sb_image').length == 0) { // if this is the first one...
        jQuery('#rc_sb_container').append(html);
    } else {
        // if this one hasn't already been created...
        if (jQuery('#' + rc_sb_slideIDs[slideID]).length == 0) {
            // either put it before or after the current slide...
            if (after) {
                jQuery(html).insertAfter('#' + rc_sb_slideIDs[rc_sb_currentSlideID]);
            } else {
                jQuery(html).insertBefore('#' + rc_sb_slideIDs[rc_sb_currentSlideID]);
            }
        }
    }

    // if show or autohide
    if (rc_sb_params['title_option'] === 0 || rc_sb_params['title_option'] === 2) {
        rc_sb_showTitle(slideID);
    }

    rc_sb_putSlideInStartingPosition(slideID, delay); //ready for the functions that come once the image has loaded :)
}

function rc_sb_showTitle(slideID)
{
    // TODO: autohide
    jQuery("#rc_sb_title").remove();
    html = '<div id="rc_sb_title">' + rc_sb_slideTitles[slideID] + '</div>';
    jQuery('#rc_sb_container').append(html);
}

function rc_sb_initialiseShadowboxMarkup()
{
    // TODO: Either add, or remove the download button
    rc_sb_shadowboxHTML = '<div id="rc_sb_overlay"></div><div id="rc_sb_container"><div id="rc_sb_toolbar"><div id="rc_sb_close" class="rc_sb_button"></div><div id="rc_sb_download" class="rc_sb_button" href="http://localhost/2018JoomlaTransferTest/images/projects/rc_gallery/demo/Masts.jpg" onclick="javascript:window.location.href=\'http://localhost/2018JoomlaTransferTest/images/projects/rc_gallery/demo/Masts.jpg\'; return false;"></div></div>';
    rc_sb_shadowboxHTML += '<div id="rc_sb_prev" class="rc_sb_button"></div><div id="rc_sb_next" class="rc_sb_button"></div></div>';
}

function rc_sb_createShadowbox()
{
    // if the shadowbox isn't already open...
    if (jQuery('#rc_sb_overlay').length == 0) {
        // create the shadowbox...
        jQuery(rc_sb_shadowboxHTML).hide().prependTo('body').fadeIn(280);
        jQuery('#rc_sb_toolbar').append(rc_sb_toolbarAddonButtonsHTML);
        rc_sb_initialiseControls();
    }
}

function rc_sb_updateNavigationButtons(slideID)
{
    if (slideID == 0) { // the beginning
        jQuery('#rc_sb_prev').fadeOut(140);
    } else {
        jQuery('#rc_sb_prev').fadeIn(140);
    }

    if (slideID == rc_sb_slideIDs.length - 1) { //the end
        jQuery('#rc_sb_next').fadeOut(140);
    } else {
        jQuery('#rc_sb_next').fadeIn(140);
    }
}

function rc_sb_closeShadowbox()
{
    // Get rid of everything...
    jQuery('#rc_sb_overlay').fadeOut(280, function() { jQuery(this).remove();});
    rc_sb_placeSlideOverSourceElement(rc_sb_currentSlideID, 280, 0);
    jQuery('.rc_sb_button').remove();
    setTimeout(function() {
        jQuery('#rc_sb_container').remove();
    }, 280);

	//prevent any events from running after image has finished loading (if there's one currently loading)
	rc_sb_runAfter = false;
}

function rc_sb_putSlideInStartingPosition(slideID, delay)
{
    if (jQuery('.rc_sb_image').length == 1) { // if this is the first slide...
        rc_sb_placeSlideOverSourceElement(slideID, 0, 0);
    } else {
        rc_sb_placeSlideInCenterAtBack(slideID, 0, 0);
    }
}

function rc_sb_giveFocus(slideID, delay)
{
    rc_sb_expandSlideToRequiredSize(slideID, 280, delay); // now make it full size
}

function rc_sb_loseFocus(slideID, direction)
{
    //swipe off the screen...
    switch (direction) {
        case 'left':
            jQuery('#' + rc_sb_slideIDs[slideID]).animate({
                left: '-50%',
                zIndex: '99996'
            }, 280);
            break;
        case 'right':
        default:
            jQuery('#' + rc_sb_slideIDs[slideID]).animate({
                left: '150%',
                zIndex: '99996'
            }, 280);
            break;
    }
}

function rc_sb_next()
{
    if (rc_sb_currentSlideID < rc_sb_slideIDs.length - 1) {
        rc_sb_loseFocus(rc_sb_currentSlideID, 'left');
        rc_sb_openSlide(rc_sb_currentSlideID + 1, true);
    }
}

function rc_sb_prev()
{
    if (rc_sb_currentSlideID > 0) {
        rc_sb_loseFocus(rc_sb_currentSlideID, 'right');
        rc_sb_openSlide(rc_sb_currentSlideID - 1, false);
    }
}

jQuery(window).resize(function()
{
    // if it's already open, then resize image to fill the given space...
    rc_sb_expandSlideToRequiredSize(rc_sb_currentSlideID, 0, 0);
});

jQuery(window).scroll(function()
{
   if (rc_sb_currentPosition == "OVERLAID") {
        rc_sb_placeSlideOverSourceElement(rc_sb_currentSlideID, 0, 0);
   }
});

function rc_sb_expandSlideToRequiredSize(slideID, duration, delay)
{
    rc_sb_currentPosition = "FULL_SCREEN"
    // takes slide from wherever it is to fill the screen
    var slideRatio = rc_sb_getRatio(jQuery(rc_sb_slideRootElements[slideID]).children().first());
    var windowRatio = rc_sb_getRatio(jQuery('#rc_sb_container'));

    var targetWidth = jQuery('#rc_sb_container').width() * rc_sb_params['expand_size'];
    var targetHeight = jQuery('#rc_sb_container').height() * rc_sb_params['expand_size'];

    if (slideRatio >= windowRatio) {
        targetHeight = targetWidth / slideRatio;
    } else {
        targetWidth = targetHeight * slideRatio;
    }

    jQuery('#' + rc_sb_slideIDs[slideID]).delay(delay).animate({
        marginLeft: -(targetWidth / 2) + 'px',
        left:'50%',
        top: (jQuery('#rc_sb_container').height() / 2) + 'px',
        width: targetWidth + 'px',
        height: targetHeight + 'px',
        zIndex: '99997'
    }, duration, 'linear');
}

function rc_sb_placeSlideOverSourceElement(slideID, duration, delay)
{
    rc_sb_currentPosition = "OVERLAID";
    // get position & size of root element, and use that as our starting point...
    var rootElement = jQuery(rc_sb_slideRootElements[slideID]).children().first();
    var width = jQuery(rootElement).width();
    var height = jQuery(rootElement).height();
    var top = jQuery(rootElement).position().top + (height / 2) - jQuery(window).scrollTop(); //add half the height to compensate for the transform
    var left = jQuery(rootElement).position().left - jQuery(window).scrollLeft();

    jQuery('#' + rc_sb_slideIDs[slideID]).animate({
        marginLeft: '0',
        top: top + 'px',
        left: left + 'px',
        width: width + 'px',
        height: height + 'px',
        zIndex: '99996'
    }, duration);
}

function rc_sb_placeSlideInCenterAtBack(slideID, duration)
{
    rc_sb_currentPosition = "CENTER"
    //make it really small and stick it in the centre behind the current image, ready to pop forward...
    jQuery('#' + rc_sb_slideIDs[slideID]).animate({
        marginLeft: '0',
        top: (jQuery('#rc_sb_container').height() / 2) - 35 + 'px',
        left: (jQuery('#rc_sb_container').width() / 2) - 35 + 'px',
        width: '70px',
        height: '70px',
        zIndex: '99996'
    }, duration);
}

// ************ Mathsy bit *************************************************
function rc_sb_getRatio(element)
{
    //returns ratio of width to height
    //if <1, then image is portrait
    initWidth = jQuery(element).width();
    initHeight = jQuery(element).height();
    return initWidth / initHeight;
}

// ************ Interaction ************************************************
function rc_sb_initialiseControls()
{
    // addon button controls
    jQuery('.rc_sb_addonbutton').on('click', function() {
        var number = jQuery(this).attr('id').replace('rc_sb_addonbutton_', ''); //work out which function to call, based on the number's ID
        rc_sb_addonFunctions[number]();
    });


    // on-screen controls
    jQuery('#rc_sb_prev').on('click', function() {
        rc_sb_prev();
    });
    jQuery('#rc_sb_next').on('click', function() {
        rc_sb_next();
    });
    jQuery('#rc_sb_close').on('click', function() {
        rc_sb_closeShadowbox();
    });
    jQuery('#rc_sb_container').on('click', function(e) {
        if( e.target != this ) return false; // ignore clicks on child elements
        rc_sb_closeShadowbox();
    });

    // touch controls
    jQuery('#rc_sb_container').on('swipeleft', function(e) {
        rc_sb_next();
    });
    jQuery('#rc_sb_container').on('swiperight', function(e) {
        rc_sb_prev();
    });

    //keyboard controls
    jQuery(document).on('keydown', function(event) {
        if (!rc_sb_keyPressed) {
            switch (event.which) {
                case 37: //left
                    rc_sb_prev();
                    break;
                case 39: //right
                    rc_sb_next();
                    break;
                case 27: //escape
                case 8: //backspace
                    rc_sb_closeShadowbox();
                    break;
                default:
                    //do nothing
                    break;
            }
        }
        rc_sb_keyPressed = true;
    });
    jQuery(document).on('keyup', function() {
        rc_sb_keyPressed = false;
    });
}

// *********************************************************************
// ********** Opening image on page load with anchor link **************
// *********************************************************************

function rc_sb_openAnchorLinkedSlide()
{
    if (window.location.hash.substring(0,4) == "#rc_") {
        jQuery(window.location.hash).parent().trigger('click');
    }
}

function rc_sb_getAnchorLinkForCurrentSlide()
{
    return jQuery(rc_sb_slideRootElements[rc_sb_currentSlideID]).children().first().attr('id');
}

// *********************************************************************
// ********** SLIDESHOW!!! *********************************************
// *********************************************************************

var isSlideshow = false;
var slideShowInterval = 5000;

function beginSlideShow()
{
    rc_sb_createShadowbox();
    rc_sb_openSlide(rc_sb_slideURLs.indexOf(jQuery(this).attr('href')), false, 280);
    setTimeout(stepSlideShow, slideShowInterval);
}

function stepSlideShow()
{
    setTimeout(stepSlideShow, slideShowInterval);
}

