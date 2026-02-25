---
id: include-a-gallery-in-a-module
title: Include in a module
sidebar_label: Include in a module
---

# Include in a module

> [!NOTE]
> Note that this step assumes you've already installed and enabled the plugins.

## Using the module add-on

The module add-on allows you to place galleries inside Joomla module positions (sidebars, footers, etc.) rather than only in articles.

1. Install the module add-on (see [Module add-on](add-ons/module-add-on.md))
2. Go to **Content > Site Modules > New**
3. Select the RC Gallery module type
4. Choose the image folder for your gallery
5. Assign the module to a position and the pages where it should appear
6. Save

The gallery will render in the chosen module position using the same settings as the main plugin.

## Without the module add-on

If you don't have the module add-on, you can use a standard Joomla **Custom HTML module** with the content plugin filter enabled. Insert the `{gallery}folder{/gallery}` tag in the module content, and ensure that content plugins are processed for that module in its settings.
