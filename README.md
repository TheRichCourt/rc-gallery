# RC Gallery for Joomla 5/6

[![Version](https://img.shields.io/badge/version-5.0.2-blue.svg)](https://github.com/dious38/rc-gallery/releases/tag/v5.0.2)
[![Joomla](https://img.shields.io/badge/Joomla-5%20%2F%206-orange.svg)](https://www.joomla.org/)
[![PHP](https://img.shields.io/badge/PHP-8.1%2B-purple.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-GPL--2.0-green.svg)](LICENSE)

A responsive gallery plugin for Joomla that displays beautiful edge-to-edge image galleries in your articles via simple `{gallery}folder{/gallery}` tags.

This is an actively maintained fork of [TheRichCourt/rc-gallery](https://github.com/TheRichCourt/rc-gallery), fully rewritten for **Joomla 5/6** compatibility with modern PHP 8.1+ support.

## Features

- **Justified layout** — edge-to-edge image rows without cropping, like Flickr or Google Photos
- **Responsive** — looks great on all screen sizes, dynamically resizes with the browser
- **Automatic thumbnails** — JPEG + WebP, LDPI + HDPI, generated on first view or via AJAX
- **Built-in shadowbox** — lightweight JavaScript lightbox with keyboard and swipe navigation
- **Inline parameters** — override settings per gallery: `{gallery target-row-height="200" image-margin-size="5"}folder{/gallery}`
- **Customisable** — row height, image spacing, sorting (name/date/random), title display, and more
- **5 languages** — English, French, German, Spanish, Italian

## Installation

1. Download the latest ZIP from the [Releases page](https://github.com/dious38/rc-gallery/releases)
2. In Joomla admin: **System > Install > Upload Package File**
3. Enable the plugin in **System > Plugins** if needed
4. Configure the **Root image folder** in the plugin settings (Gallery tab)

## Requirements

- Joomla 5.x or 6.x
- PHP 8.1+

## Usage

Add a gallery to any article by inserting:

```
{gallery}my-image-folder{/gallery}
```

The folder path is relative to the root image folder configured in the plugin settings.

### Inline parameters

Override default settings for a specific gallery:

```
{gallery target-row-height="200" image-margin-size="5"}my-image-folder{/gallery}
```

## Changelog

### v5.0.2
- Fix RC Shadowbox with single-image galleries (image disappearing, prev/next arrows hidden)

### v5.0.1
- Fix AJAX thumbnail generation (missing path separator)
- Fix language strings not loaded on frontend
- Fix missing translation keys for title text alignment
- Fix dataset property mismatch in rc_gallery.js
- Add internationalization: 5 languages (EN, FR, DE, ES, IT)

### v5.0.0
- Full migration from Joomla 3/4 to Joomla 5/6
- Modern architecture: `CMSPlugin` + `SubscriberInterface`, PSR-4 namespaces, dependency injection
- All legacy APIs replaced (`JPlugin`, `JFactory`, `JFolder`, `jimport`, etc.)
- Static assets moved to `media/` with WebAssetManager support
- Fix PHP 8.2+ deprecation in ThumbnailFactory (float to int)
- Fix `exif_read_data` crash
- Handle Unicode curly quotes from Joomla 6 TinyMCE editor

## Credits

Originally created by [Rich Court](https://github.com/TheRichCourt). This fork is maintained by [dious38](https://github.com/dious38) under the same GPL-2.0 license.
