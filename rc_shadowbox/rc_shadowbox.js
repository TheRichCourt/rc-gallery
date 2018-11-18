var rcShadowbox;

jQuery(document).ready(function () {
    "use strict";

    rcShadowbox = new RCShadowbox();
    rcShadowbox.setup();
});

// @todo:
// //maintain compatibility with old versions of the social addon:
// function rc_sb_insertButton(buttonTitle, buttonClass, buttonFunction, buttonLinkURL) {
//     console.log("Social!");
//     rcShadowbox.insertButton(buttonTitle, buttonClass, buttonFunction, buttonLinkURL);
// }

// function rc_sb_getAnchorLinkForCurrentSlide() {
//     return jQuery(rc_sb_slideRootElements[rc_sb_currentSlideID]).children().first().attr('id');
// }

var RCShadowbox = function () {
    "use strict";

    var container,
        overlay,
        toolbar,
        closeButton,
        prevButton,
        nextButton,
        titleElem,
        slides = [],
        currentSlideId,
        loadingIcon,
        body,
        initialBodyOverflow,
        open = false,
        preventKeyboard = false,
        showTitle = rc_sb_params["title_option"] == 0 || rc_sb_params["title_option"] == 2;

    return {
        setup: function () {
            this.createShadowbox();
            this.setupControls();
            this.setupSlides();

            var hashLink = window.location.hash;

            if (hashLink.substring(0,4) == "#rc_") {
                var hashLinkedAnchor = document.getElementById(hashLink.substr(1));

                hashLinkedAnchor.click();
            }
        },

        createShadowbox: function () {
            body = document.querySelector("body");

            overlay = document.createElement("div");
            overlay.id = "rc_sb_overlay";
            overlay.classList.add("rc_hidden");

            container = document.createElement("div");
            container.id = "rc_sb_container";
            container.classList.add("rc_hidden");

            toolbar = document.createElement("div");
            toolbar.id = "rc_sb_toolbar";

            closeButton = document.createElement("div");
            closeButton.id = "rc_sb_close";
            closeButton.classList.add("rc_sb_button");

            prevButton = document.createElement("div");
            prevButton.id = "rc_sb_prev";
            prevButton.classList.add("rc_sb_button");

            nextButton = document.createElement("div");
            nextButton.id = "rc_sb_next";
            nextButton.classList.add("rc_sb_button");

            loadingIcon = document.createElement("div");
            loadingIcon.classList.add("rc_sb_loading");
            loadingIcon.classList.add("rc_hidden");

            toolbar.appendChild(closeButton);
            container.appendChild(toolbar);
            container.appendChild(prevButton);
            container.appendChild(nextButton);
            container.appendChild(loadingIcon);
            body.appendChild(overlay);
            body.appendChild(container);

            if (showTitle) {
                titleElem = document.createElement("div");
                titleElem.id = "rc_sb_title";
                container.appendChild(titleElem);
            }
        },

        setupSlides: function () {
            var shadowboxAnchors = document.querySelectorAll("a[rel^='shadowbox']");
            var i = 0;

            [].forEach.call(shadowboxAnchors, function (shadowboxAnchor) {
                var slideElem = document.createElement("img");
                slideElem.id = "rc_sb_slide_" + i;
                slideElem.classList.add("rc_sb_image");

                var shadowboxAnchorImage = shadowboxAnchor.querySelector("img");
                var ratio = shadowboxAnchorImage.dataset.width / shadowboxAnchorImage.dataset.height;

                slides[i] = {
                    id: i,
                    shadowboxAnchor: shadowboxAnchor,
                    src: shadowboxAnchor.href,
                    alt: shadowboxAnchorImage.alt,
                    title: shadowboxAnchor.dataset.imagetitle,
                    slideElem: slideElem,
                    startingSize: {
                        width: shadowboxAnchorImage.dataset.width,
                        height: shadowboxAnchorImage.dataset.height
                    },
                    ratio: ratio,
                    orientation: ratio >= 1 ? "landscape" : "portrait"
                };

                container.appendChild(slideElem);
                i += 1;
            });
        },

        setupControls: function () {
            var rcShadowbox = this;
            var shadowboxAnchors = document.querySelectorAll("a[rel^='shadowbox']");

            // clicking a thumbnail
            [].forEach.call(shadowboxAnchors, function (shadowboxAnchor) {
                shadowboxAnchor.addEventListener("click", function (event) {
                    event.preventDefault();

                    var currentSlide = slides.filter(function (slideObj) {
                        return slideObj.src == shadowboxAnchor.href;
                    })[0];

                    rcShadowbox.openSlide(currentSlide);
                });
            });

            // navigation buttons
            prevButton.addEventListener("click", function () {
                rcShadowbox.prevSlide();
            });

            nextButton.addEventListener("click", function () {
                rcShadowbox.nextSlide();
            });

            closeButton.addEventListener("click", function () {
                rcShadowbox.close();
            });

            container.addEventListener("click", function (event) {
                // ignore clicks on child elements
                if (event.target !== container) {
                    return;
                }

                rcShadowbox.close();
            });

            // swiping
            // @todo: touch controls - remove jQuery mobile dependency
            // https://stackoverflow.com/questions/2264072/detect-a-finger-swipe-through-javascript-on-the-iphone-and-android
            jQuery('#rc_sb_container').on('swipeleft', function(e) {
                rcShadowbox.nextSlide();
            });
            jQuery('#rc_sb_container').on('swiperight', function(e) {
                rcShadowbox.prevSlide();
            });

            // keyboard controls
            window.addEventListener("keydown", function (event) {
                if (open && !preventKeyboard) {
                    preventKeyboard = true;
                    switch (event.key) {
                    case "ArrowRight":
                        rcShadowbox.nextSlide();
                        break;
                    case "ArrowLeft":
                        rcShadowbox.prevSlide();
                        break;
                    case "Backspace":
                    case "Escape":
                        rcShadowbox.close();
                        break;
                    }
                }
            });

            window.addEventListener("keyup", function () {
                preventKeyboard = false;
            });
        },

        openSlide: function (slide) {
            currentSlideId = slide.id;

            if (!open) {
                this.open();
            }

            if (showTitle) {
                titleElem.innerHTML = slide.title;
            }

            // just in case we're still part way through a previous transition
            slide.slideElem.classList.remove("rc_sb_hidden_left");
            slide.slideElem.classList.remove("rc_sb_hidden_right");

            // if image hasn't already been loaded...
            if (!slide.slideElem.src) {
                slide.slideElem.alt = ""; // This prevents the browser's no-image icon from showing
                loadingIcon.classList.remove("rc_hidden");
                var image = new Image();

                image.onload = function () {
                   slide.slideElem.alt = slide.alt;
                   slide.slideElem.src = slide.src;
                   slide.slideElem.classList.remove("rc_sb_hidden_centre");
                   loadingIcon.classList.add("rc_hidden");
                };

                image.src = slide.src;
            } else {
                loadingIcon.classList.add("rc_hidden");
                slide.slideElem.classList.remove("rc_sb_hidden_centre");
            }

            // deal with previous and next slides
            var prevSlide = this.getPrevSlide();

            if (!prevSlide.slideElem.classList.contains("rc_sb_hidden_centre")) {
                prevSlide.slideElem.classList.add("rc_sb_hidden_left");

                setTimeout(function () {
                    // as we're doing this after a delay, check that the user hasn't already come back to this slide
                    if (prevSlide.id !== currentSlideId) {
                        prevSlide.slideElem.classList.remove("rc_sb_hidden_left");
                        prevSlide.slideElem.classList.add("rc_sb_hidden_centre");
                    }
                }, 560);
            }

            var nextSlide = this.getNextSlide();

            if (!nextSlide.slideElem.classList.contains("rc_sb_hidden_centre")) {
                nextSlide.slideElem.classList.add("rc_sb_hidden_right");

                setTimeout(function () {
                    if (nextSlide.id !== currentSlideId) {
                        nextSlide.slideElem.classList.remove("rc_sb_hidden_right");
                        nextSlide.slideElem.classList.add("rc_sb_hidden_centre");
                    }
                }, 560);
            }
        },

        nextSlide: function () {
            this.openSlide(this.getNextSlide());
        },

        prevSlide: function () {
            this.openSlide(this.getPrevSlide());
        },

        getNextSlide: function () {
            var id = currentSlideId >= slides.length - 1
                ? 0
                : currentSlideId + 1;

            return slides[id];
        },

        getPrevSlide: function () {
            var id = currentSlideId == 0
                ? slides.length - 1
                : currentSlideId - 1;

            return slides[id];
        },

        close: function () {
            open = false;
            container.classList.add("rc_hidden");
            overlay.classList.add("rc_hidden");
            slides[currentSlideId].slideElem.classList.add("rc_sb_hidden_centre");

            setTimeout(function () {
                body.style.overflow = initialBodyOverflow;
            }, 280);
        },

        open: function () {
            open = true;
            container.classList.remove("rc_hidden");
            overlay.classList.remove("rc_hidden");

            slides.forEach(function (slide) {
                slide.slideElem.classList.add("rc_sb_hidden_centre");
                slide.slideElem.classList.remove("rc_sb_hidden_left");
                slide.slideElem.classList.remove("rc_sb_hidden_right");
            });

            initialBodyOverflow = body.style.overflow;
            body.style.overflow = "hidden";
        },

        insertButton: function (title, classes, callback, href) {
            var newButton = document.createElement("div");
            newButton.title = title;
            newButton.classList.add(classes);

            if (href) {
                newButton.href = href;
            }

            toolbar.addChild(newButton);
            newButton.addEventListener("click", callback);
        },

        getAnchorLinkForCurrentSlide: function () {
            // @todo: this needs to work in order to maintain compatibility with the social add-on
        }
    };
};
