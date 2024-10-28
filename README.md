# Add EXIF and IPTC meta data to Attachment Post

Contributors: markhowellsmead
Plugin Name: Add EXIF and IPTC meta data to Attachment Post
Plugin URI: https://wordpress.org/plugins/add-exif-and-iptc-meta-data-to-attachment/
Tags: attachment, image, exif, iptc, upload, media, meta, metadata
Author URI: https://sayhello.ch/
Author: Say Hello GmbH
Requires at least: 5.6
Tested up to: 6.6.1
Stable tag: trunk
Version: 1.1.0

Extends the attachment meta data to include a much wider range of EXIF and IPTC information when an image is uploaded.

This plugin does not output any data on the public views of the website or in the WordPress Admin area. If this is required, a developer will need to amend the Theme or Plugin which generates the HTML for the website.

## Description

WordPress extracts image meta data from a file when it is uploaded and adds it to the newly created Attachment Post in the database. This Plugin extracts and saves a much wider range of EXIF and IPTC information than WordPress Core usually stores.

This Plugin currently supports JPEG and WEBP files.

This plugin does not output any data on the website or in the WordPress Admin area. If this is required, a developer will need to amend the Theme or Plugin which generates the HTML for the website.

The Plugin does not add any information to Attachment Posts which have already been created. (The Plugin does, however, update the stored meta data when a new image is uploaded to an existing Attachment Post; for example when the [Enable Media Replace Plugin](https://wordpress.org/plugins/enable-media-replace/) is used.)

The information is not visible in the Media editor, but is available to developers when using the `wp_get_attachment_metadata` function. The data is stored in a subset of the array returned by this function, within the array key `shp_additional_metadata`.

e.g.

`

<?php
$attachment_metadata = wp_get_attachment_metadata($attachment_id);
var_dump($attachment_metadata['shp_additional_metadata']);
`

### Hooks

The data array can be manipulated using `add_filter` once it has been retrieved from the file, using the following hooks.

#### All additional data

All IPTC data which is added by the plugin.

`
<?php
apply_filters('shp_additional_metadata/iptc', $exif['iptc'], $source_path);
`

#### IPTC data

All data which is added by the plugin.

`
<?php
apply_filters('shp_additional_metadata/all_exif', $exif, $source_path);
`

## Installation

1. Install from the WordPress Plugin Directory or upload the `shp_additional_metadata` folder to the installation's plugins directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.

## Changelog

### 1.1.0

* Add WEBP support
* Confirm functionality in WordPress 5.9.

### 1.0.1

* Fix EXIF checks

### 1.0.0

* Initial release.
