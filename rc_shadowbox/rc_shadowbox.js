var rcShadowbox;

window.addEventListener("load", function () {
    "use strict";

    rcShadowbox = new RCShadowbox();
    rcShadowbox.setup();
});

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
        showTitle = rc_sb_params["title_option"] == 0 || rc_sb_params["title_option"] == 2,
        hideScrollbar = rc_sb_params["hide_scroll_bar"],
        socialAddon;

    return {
        setup: function () {
            this.createShadowbox();
            this.setupControls();
            this.setupSlides();

            var hashLink = window.location.hash;

            if (hashLink.substring(0, 4) === "#rc_") {
                var hashLinkedAnchor = document.getElementById(hashLink.substr(1));
                hashLinkedAnchor.click();
            }
        },

        setupSocialAddonButtons: function () {
            // Confirm the existence of the social addon
            if (typeof insertButton === "function") {
                this.insertSocialButton(rc_gallery_social_addon_button1);
                this.insertSocialButton(rc_gallery_social_addon_button2);
                this.insertSocialButton(rc_gallery_social_addon_button3);
                this.insertSocialButton(rc_gallery_social_addon_button4);
            }
        },

        /**
         * Remove all buttons, except the close button
         */
        removeButtons: function () {
            var buttonElems = toolbar.querySelectorAll("a");

            [].forEach.call(buttonElems, function (buttonElem) {
                buttonElem.parentNode.removeChild(buttonElem);
            });
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
            jQuery('#rc_sb_container').on('swipeleft', function () {
                rcShadowbox.nextSlide();
            });

            jQuery('#rc_sb_container').on('swiperight', function () {
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

        insertSocialButton: function (number) {
            if (!number) {
                return;
            }

            var rcShadowbox = this,
                socialNetworkName,
                baseShareLink,
                buttonClass;

            switch (number) {
                case 1:
                    socialNetworkName = "Facebook";
                    baseShareLink = rc_fb_shareURL;
                    buttonClass = "rc_fbshareaddon_button";
                break;
                case 2:
                    socialNetworkName = "Twitter";
                    baseShareLink = rc_twitter_shareURL;
                    buttonClass = "rc_twittershareaddon_button";
                break;
                case 3:
                    socialNetworkName = "Google+";
                    baseShareLink = rc_gp_shareURL;
                    buttonClass = "rc_gpshareaddon_button";
                break;
                case 4:
                    socialNetworkName = "Tumblr";
                    baseShareLink = rc_tumblr_shareURL;
                    buttonClass = "rc_tumblrshareaddon_button";
                break;
            }

            var newButton = this.insertButton(
                "Share on " + socialNetworkName,
                [buttonClass, "rc_sb_button", "rc_sb_addonbutton"],
                "#"
            );

            newButton.addEventListener("click", function (event) {
                event.preventDefault();
                var top = (window.innerHeight - 600) / 2;
                var left = (window.innerWidth - 600) / 2;
                var newWindowSettings = "menubar=no,toolbar=no,resizable=yes,scrollbars=yes,height=600,width=600,top=" + top + ",left=" + left;
                var hashLinkToCurrentSlide = rcShadowbox.getCurrentSlide().shadowboxAnchor.childNodes[0].id;
                var fullUrl = baseShareLink + rc_gallery_social_addon_pageURL + "%23" + hashLinkToCurrentSlide;

                window.open(
                    fullUrl,
                    "",
                    newWindowSettings
                );
            });
        },

        openSlide: function (slide) {
            currentSlideId = slide.id;

            if (!open) {
                this.open();
            }

            this.removeButtons();
            this.setupSocialAddonButtons();

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
                    // force computing style, to ensure transitions always run on class change
                    void window.getComputedStyle(slide.slideElem).getPropertyValue("transition");
                    slide.slideElem.classList.remove("rc_sb_hidden_centre");
                    loadingIcon.classList.add("rc_hidden");
                };

                image.src = slide.src;
            } else {
                // force computing style, to ensure transitions always run on class change
                void window.getComputedStyle(slide.slideElem).getPropertyValue("transition");
                slide.slideElem.classList.remove("rc_sb_hidden_centre");
                loadingIcon.classList.add("rc_hidden");
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

        getCurrentSlide: function () {
            return slides[currentSlideId];
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

            if (hideScrollbar === "1") {
                setTimeout(function () {
                    body.style.overflow = initialBodyOverflow;
                }, 280);
            }
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

            if (hideScrollbar === "1") {
                initialBodyOverflow = body.style.overflow;
                body.style.overflow = "hidden";
            }
        },

        insertButton: function (title, classes, href) {
            var newButton = document.createElement("div");
            var newButtonAnchor = document.createElement("a");

            newButtonAnchor.title = title;
            newButtonAnchor.href = href;

            classes.forEach(function (buttonClass) {
                newButton.classList.add(buttonClass);
            });

            if (href) {
                newButton.href = href;
            }

            newButtonAnchor.appendChild(newButton);

            toolbar.appendChild(newButtonAnchor);

            return newButton;
        },

        getAnchorLinkForCurrentSlide: function () {
            // @todo: this needs to work in order to maintain compatibility with the social add-on
        }
    };
};
