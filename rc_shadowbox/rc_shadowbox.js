jQuery(document).ready(function () {
    var rcShadowbox = new RCShadowbox();
    rcShadowbox.setup();
});

var RCShadowbox = function () {
    "use strict";

    var container,
        overlay,
        toolbar,
        closeButton,
        prevButton,
        nextButton;

    return {
        setup: function () {
            var body = document.querySelector("body");

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

            toolbar.appendChild(closeButton);
            container.appendChild(toolbar);
            container.appendChild(prevButton);
            container.appendChild(nextButton);
            body.appendChild(overlay);
            body.appendChild(container);
        },

        setupSlides: function () {

        },

        setupControls: function () {

        },

        openSlide: function () {

        },

        nextSlide: function () {

        },

        prevSlide: function () {

        },

        close: function () {

        },

        insertButton: function () {

        },

        preLoad: function () {

        },

        showTitle: function () {

        },

        createShadowbox: function () {

        },

        updateNavigationButtons: function () {

        },

        giveSlideFocus: function () {

        },

        onResize: function () {

        },

        onScroll: function () {

        },

        placeSlideOverSourceElement: function () {

        },

        placeSlideInCentreAtBack: function () {

        },

        getRatio: function () {

        },

        openAnchorLinkedSlide: function () {

        },

        getAnchorLinkForCurrentSlide: function () {

        }
    };
};
