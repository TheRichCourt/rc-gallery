# Thumbnails

## Producing thumbnails

The plugin automatically creates thumbnails of your images the first time the gallery for that folder is loaded. These are placed in a folder inside the original image folder, called “rc_thumbs”.

You may notice that there are quite a few thumbnails – 4 for each image. That’s because RC Gallery makes use of the latest standards, and will automatically serve up the best image for the user’s browser / display density.

WebP images are delivered for browsers that support the format, because webp files are ~30% smaller than a jpeg of the same quality. Higher resolution thumbnails are delivered on higher density displays (4k or ‘Retina’ displays, for example).

Basically, these thumbnails are all about delivering the best possible images, at the fastest possible speed to your sites visitors.

## Thumbnail resolution

The resolution of the thumbnails is decided by the “Target Row Height” setting. The thumbnails will be saved with double the height of this setting. This ensures the page loads fast, while keeping enough resolution to fill the thumbnails at varying heights without looking pixelated. Note that if you change the “Target Row height” the thumbnails won’t be automatically recreated. You’ll need to do this yourself, as described below.

## Refresh thumnails

There are some circumstances under which you may want to recreate thumbnails. For example, if you change the thumbnail quality setting (added in v1.2.X) or the target row height, or if you’ve edited some of your images.

If you want to recreate the thumbnails, just delete the existing ones using your FTP client, or Joomla’s media manager. The plugin will then automatically make them again the next time the gallery is loaded.
