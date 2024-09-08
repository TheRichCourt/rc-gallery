# Settings


RC Gallery is pre-configured to give you beautiful responsive galleries straight out of the box. However, if there’s anything you do want to change, the following settings are available from the plugin’s admin page.

> [!NOTE] **Inline settings** -
> As well as being able to change these settings on the plugin’s admin page, you can also change some settings individually for each gallery. See the inline settings section of the docs for more info.

## Root image folder

Default: `images`

Folder in which your gallery folders can be found.

## Sort images by

Default: `File name`

Order in which the images will be displayed.

> [!NOTE] **Sorting by date** -
> If you choose to sort by date, the plugin will check the image file for EXIF data. If there is no EXIF data for a file, or the EXIF data doesn’t include the date the image was taken, then it can’t be sorted. The result will be that those without the date will appear last. If more than one file is missing the date, then they will be shown in order of file name after those with dates.

## Sort direction

Default: `Ascending`

Self-explanatory.

## Target row height

Default: `100`

The rows in the gallery will vary in height in order to justify the width to the left and right margins. This is the target height the plugin will start from for each row. In reality, rows will almost always be larger or smaller than the number specified here, however it’s useful for controlling the approximate height, and therefore size of the thumbnails. Measured in `px`.

## Thumbnail quality (0-100)

Default: `80`

The quality of thumbnail files created by the plugin. 0 is poor quality, but low file size. 100 is excellent quality, but high file size. The slider moves in increments of 10.

> [!NOTE] **Thumbnails created with legacy versions** -
> This option was new in version 1.2.X of the plugin. Thumbnails created by older versions were always created at 100% quality.

See [Refresh thumbnails](refresh-thumbnails.html) if you want this setting to apply to thumbnails that have already been created before you changed this setting.

## Image margin size

Default: `2`

The transparent margin around each image. Measured in `px`.

## Image title

Default: `Don't show image titles`

Option to display the image title over each image, either on hover only, all the time, or not at all. Image titles are formed using the image’s filename, although the following is changed automatically:

*   First character is forced to uppercase.
*   Underscores are replaced with spaces.
*   File extensions are removed.


You may choose to override these titles if you prefer, with the following option…

## Use image title as `alt` tag

Default: `Yes`

Use the image title as the 'alt' tag for the image thumbnail. Note that this is useful if you have descriptive titles for your images, but if they're just something like 'DSC00023.JPG', then it's less useful and you may want to turn this off.

You can override your image titles if your filenames aren't descriptive. See the next setting for details.

Overall this feature is good for SEO and accessibility, but only if you have descriptive image titles. More info on the SEO side of this is available at [https://support.google.com/webmasters/answer/114016](https://support.google.com/webmasters/answer/114016) under the "Create great alt text” section.

## Get titles from `labels.txt`

Default: `No`

Option to get image titles from a text file you’ve created, instead of just using the file name. This option defaults to no, in case you’ve previously used another plugin the used the ‘labels.txt’ file, and used a different format. Refer to the image title section above for more information on how this works.

## Shadowbox

Default: `Use included shadowbox`

Whether or not to include the bundled shadowbox. See the shadowbox section of this guide for more info.

> [!NOTE] **Legacy shadowbox** -
> There’s also an option to use the **legacy shadowbox** – this is the old shadowbox from before version 3.0. I don’t think it’s as good, but you might prefer it, so the options there for now (though it may be removed in a future release). Note that if you’ve upgraded from a previous version, the plugin won’t automatically switch to the new style shadowbox (you’ll need to do this yourself from the settings).

## Thumbnail background colour

Default: `#F2F2F2` _(very light grey)_

This background colour is only visible while the thumbnails are loading, but on pages with a lot of images, or for users with slow connections that can be a while. This colour is what will show while the images are loading.

## Image title colour

Default: `#FFFFFF` _(white)_

Colour of the image title text shown over the thumbnails (unless you’ve chosen not to show image titles).

## Title text size

Default: `14px`

Size of the image title text shown over the thumbnails (unless you’ve chosen not to show image titles).

## Title text weight

Default: `Bold`

Weight (bold or normal) of the image title text shown over the thumbnails (unless you’ve chosen not to show image titles).

## Align title text

Default: `Left`

Alignment (left, right or centre) of the image title text shown over the thumbnails (unless you’ve chosen not to show image titles).

## Title text overflow

Default: `Hidden`

What to do if the text is wider than the thumbnail.

## Thumbnail corner radius (px)

Default: `0`

The radius of the corners of the image thumbnails. Measured in ‘px’. Smaller numbers tend to work better, but you can go crazy and have really circular image thumbnails if you like.

0 gives ‘square’ corners.

## Thumbnail filter

Default: `No filter`

Apply a filter to the thumbnails. The filter will fade into full colour when the mouse is hovered over the thumbnail.

> [!NOTE] **Browser support** -
> Not all browsers support this feature. See [https://caniuse.com/#feat=css-filters](https://caniuse.com/#feat=css-filters) for up-to-date info.

## Overlay colour

Default: `#000000` _(black)_

The colour used to cover the rest of the site when the shadowbox is opened. Note that the appearance of this colour will also be affected by the opacity setting.

## Overlay opacity

Default: `0.85`

Sets the opacity of the overlay. For a solid background, slide all the way to the right (1), or for an invisible overlay, slide all the way to the left (0).
