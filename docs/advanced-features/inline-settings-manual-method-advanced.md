# Per-gallery settings - manual method

> [!NOTE] **This is an advanced topic** -
> This is the more complicated (but free) way of setting per-gallery settings. Check out the editor button add-on, to avoid having to learn any of what's on this page.

If you prefer not to use the editor button, you can override settings manually for each gallery, by including attributes in your opening `{gallery}` tag, similarly to how you would when writing html. For example:

```
{gallery image-margin-size="5" target-row-height="100" image-title-option="1"}myfolder{/gallery}
```

If you mistype the name of a setting or if you provide a setting value that isn’t valid for that setting, it will be ignored, and the setting you’ve chosen in the plugin’s admin page will be used instead.

The below provides a reference on which settings can be overridden, what mark-up to use, and what values are allowed for each setting. For info on what each of these settings does, refer back to the settings section of this guide.

## Root image folder

```html
root-image-folder="<string>"
```

Text. Must be a valid file path.

> [!NOTE]
> For this setting, an invalid file path will be allowed, but you’ll see an error when the gallery is loaded. Be careful when overriding this setting.


## Target row height

```html
target-row-height="<positive number>"
```

## Thumbnail quality
> [!NOTE]
> It's not possible to override this setting inline. This prevents conflicts if you include the same folder of images on multiple pages.

## Image margin size

```html
image-margin-size="<number>"
```

## Image title

```html
image-title-option="<0|1|2>"
```

* `0` - Don't show image titles.
* `1` - Show image titles only when cursor hovers over the image.
* `2` - Show image titles all the time.

## Use image title as alt tag

```html
use-title-as-alt="<0|1>"
```

* `0` - No, don't put alt tags on the images.
* `1` - Yes, use image titles for alt tags.

## Get titles from `labels.txt`

```html
use-labels-file="<0|2>"
```

* `0` - No, don’t use the labels file (even if it exists).
* `2` - Yes, use the labels file (if it exists).

## Shadowbox

```html
use-shadowbox="<0|1|2|3>"
```

* `0` - Use the legacy shadowbox (from before version 3.0 – you might prefer it, but I don’t think it’s as good).
* `1` - Use another shadowbox.
* `2` - No shadowbox.
* `3` - Use the awesome new shadowbox.

> [!NOTE]
> You should never include two galleries on the same page with different shadowbox options – the result is that you’ll end up opening both!

## Sort images by

```html
sort-type="<0|1>"
```

* `0` - File name.
* `1` - Date.

## Sort direction

```html
sort-desc="<0|1>"
```

* `0` (false) - Ascending.
* `1` (true) - Descending.

## Thumbnail filter

```html
thumbnail-filter="<0|1|2>"
```

* `0` - No filter.
* `1` - Sepia.
* `2` - Greyscale (black and white).

## Title text overflow

```html
title-text-overflow="<hidden|auto>"
```

## Thumbnail background colour


```html
thumb-bg-colour="<css colour>"
```

Any acceptable CSS colour (usually hex, RGB or RGBA).

## Title text colour

```html
title-text-colour="<css colour>"
```

Any acceptable CSS colour (usually hex, RGB or RGBA).

## Title text size

```html
title-text-size="<number>"
```

## Align title text

```html
title-text-align="<left|center|right>"
```

## Title text weight

```html
title-text-weight="<bold|normal>"
```

## Shadowbox overlay colour

```html
overlay-colour="<css colour>"
```

Any acceptable CSS colour (usually hex, RGB or RGBA).

## Shadowbox overlay opacity

```html
overlay-opacity="<0-1>"
```

## Shadowbox image titles

```html
shadowbox-title-option="<0|1>"
```

* `0` - Show.
* `1` - Hide.
