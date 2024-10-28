<?php

/**
 * Plugin Name:       Add EXIF and IPTC meta data to Attachment
 * Plugin URI:        https://wordpress.org/plugins/add-exif-and-iptc-meta-data-to-attachment/
 * Description:       Extends the attachment meta data to include a much wider range of EXIF and IPTC information when an image is uploaded. Currently supports JPG and WEBP only.
 * Version:           1.1.0
 * Requires at least: 5.2
 * Requires PHP:      7.3
 * Author:            Say Hello GmbH
 * Author URI:        https://sayhello.ch/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       shp_additional_metadata
 * Domain Path:       /languages
 */

require_once 'Classes/Plugin.php';

function shp_additional_metadata_get_instance()
{
	return SayHello\AdditionalMetadata\Plugin::getInstance(__FILE__);
}
shp_additional_metadata_get_instance();
