---
id: shadowbox
title: Shadowbox
sidebar_label: Shadowbox
---

# Shadowbox

## Use the included shadowbox

The shadowbox is enabled by default, and allows you to click on the images in your gallery, to open them in a pop-up box.

## Use the included shadowbox with content outside of the galleries

The included shadowbox can be used for content outside of your gallery if you wish. To do this, create a link to the image you want to show in the shadowbox, and include `rel="shadowbox"`. For example, the below markup would open `myimage.jpg` in the shadowbox:

```html
<a href="myimage.jpg" rel="shadowbox[rc_gallery]">
    Open the image in a shadowbox.
</a>
```

> [!NOTE]
> Note that this will only work on pages where you’ve also included a gallery.

## Use an alternative shadowbox

There’s loads of choice out there for shadowboxes, and so you may prefer to use a different one. If that’s the case, select the option **"Use another shadowbox"** from the plugin’s admin page. The result is that the thumbnails will include a link to the original image, with:
`rel="shadowbox[rc_gallery]"`

...included, but the plugin’s shadowbox won’t be loaded. This will mean they’ll open with whichever other shadowbox you have installed and enabled.
