<?php

namespace SayHello\AdditionalMetadata;

class Plugin
{
	private static $instance;
	public $file = '';
	public $name = '';
	public $version = '';

	/**
	 * Creates an instance if one isn't already available,
	 * then return the current instance.
	 * @param  string $file The file from which the class is being instantiated.
	 * @return object       The class instance.
	 */
	public static function getInstance($file)
	{
		if (!isset(self::$instance) && !(self::$instance instanceof Plugin)) {
			if (!function_exists('get_plugin_data')) {
				require_once(ABSPATH . 'wp-admin/includes/plugin.php');
			}
			self::$instance = new Plugin;
			self::$instance->run();
			$data = get_plugin_data($file);
			self::$instance->file = $file;
			self::$instance->name = $data['Name'];
			self::$instance->version = $data['Version'];
		}
		return self::$instance;
	}

	/**
	 * Execution function which is called after the class has been initialized.
	 * This contains hook and filter assignments, etc.
	 */
	private function run()
	{
		add_action('plugins_loaded', [$this, 'loadPluginTextdomain']);
		add_filter('wp_read_image_metadata', [$this, 'additionalImageMeta'], 10, 3);
	}

	/**
	 * Load translation files from the indicated directory.
	 */
	public function loadPluginTextdomain()
	{
		load_plugin_textdomain('shp_additional_metadata', false, dirname(plugin_basename($this->file)) . '/languages');
	}

	/**
	 * Extend the basic meta data stored in the database with additional values.
	 *
	 * @param array  $meta            The array of meta data which WordPress usually stores in the database.
	 * @param string $file_path            The fully-qualified path to the file being processed.
	 * @param int    $sourceImageType The MIME type of the file being processed, in binary integer format.
	 *
	 * @return array The processed and extended meta data.
	 */
	public function additionalImageMeta($meta, $file_path, $sourceImageType)
	{
		$image_file_types = apply_filters(
			'wp_read_image_metadata_types',
			[IMAGETYPE_WEBP, IMAGETYPE_JPEG]
		);

		if (in_array($sourceImageType, $image_file_types) && function_exists('iptcparse')) {
			$meta['shp_additional_metadata'] = $this->buildEXIFArray($file_path, false);
		}

		return $meta;
	}

	public function buildEXIFArray($source_path)
	{
		$exif = @exif_read_data($source_path, 'ANY_TAG');

		if (!$exif) {
			return false;
		}

		/*
		Example of the values in the file's EXIF data:

		[GPSLatitudeRef] => N
		[GPSLatitude] => Array
		(
		[0] => 57/1
		[1] => 31/1
		[2] => 21334/521
		)

		[GPSLongitudeRef] => W
		[GPSLongitude] => Array
		(
		[0] => 4/1
		[1] => 16/1
		[2] => 27387/1352
		)
		*/

		$GPS = [];

		if (isset($exif['GPSLatitude'])) {
			$GPS['lat']['deg'] = explode('/', $exif['GPSLatitude'][0]);
			$GPS['lat']['deg'] = $GPS['lat']['deg'][1] > 0 ? $GPS['lat']['deg'][0] / $GPS['lat']['deg'][1] : 0;
			$GPS['lat']['min'] = explode('/', $exif['GPSLatitude'][1]);
			$GPS['lat']['min'] = $GPS['lat']['min'][1] > 0 ? $GPS['lat']['min'][0] / $GPS['lat']['min'][1] : 0;
			$GPS['lat']['sec'] = explode('/', $exif['GPSLatitude'][2]);

			$lat_sec_0 = floatval($GPS['lat']['sec'][0]);
			$lat_sec_1 = floatval($GPS['lat']['sec'][1]);

			if ($lat_sec_0 > 0 && $lat_sec_1 > 0) {
				$GPS['lat']['sec'] = $lat_sec_0 / $lat_sec_1;
			} else {
				$GPS['lat']['sec'] = 0;
			}

			$exif['GPSLatitudeDecimal'] = $this->DMStoDEC($GPS['lat']['deg'], $GPS['lat']['min'], $GPS['lat']['sec']);
			if ($exif['GPSLatitudeRef'] == 'S') {
				$exif['GPSLatitudeDecimal'] = 0 - $exif['GPSLatitudeDecimal'];
			}
		} else {
			$exif['GPSLatitudeDecimal'] = null;
			$exif['GPSLatitudeRef'] = null;
		}

		if (isset($exif['GPSLongitude'])) {
			$GPS['lon']['deg'] = explode('/', $exif['GPSLongitude'][0]);
			$GPS['lon']['deg'] = $GPS['lon']['deg'][1] > 0 ? $GPS['lon']['deg'][0] / $GPS['lon']['deg'][1] : 0;
			$GPS['lon']['min'] = explode('/', $exif['GPSLongitude'][1]);
			$GPS['lon']['min'] = $GPS['lon']['min'][1] > 0 ? $GPS['lon']['min'][0] / $GPS['lon']['min'][1] : 0;
			$GPS['lon']['sec'] = explode('/', $exif['GPSLongitude'][2]);

			$lon_sec_0 = floatval($GPS['lon']['sec'][0]);
			$lon_sec_1 = floatval($GPS['lon']['sec'][1]);

			if ($lon_sec_0 > 0 && $lon_sec_1 > 0) {
				$GPS['lon']['sec'] = $lon_sec_0 / $lon_sec_1;
			} else {
				$GPS['lon']['sec'] = 0;
			}

			$exif['GPSLongitudeDecimal'] = $this->DMStoDEC($GPS['lon']['deg'], $GPS['lon']['min'], $GPS['lon']['sec']);
			if ($exif['GPSLongitudeRef'] == 'W') {
				$exif['GPSLongitudeDecimal'] = 0 - $exif['GPSLongitudeDecimal'];
			}
		} else {
			$exif['GPSLongitudeDecimal'] = null;
			$exif['GPSLongitudeRef'] = null;
		}

		if ($exif['GPSLatitudeDecimal'] && $exif['GPSLongitudeDecimal']) {
			$exif['GPSCalculatedDecimal'] = $exif['GPSLatitudeDecimal'] . ',' . $exif['GPSLongitudeDecimal'];
		} else {
			$exif['GPSCalculatedDecimal'] = null;
		}

		$exif['iptc'] = [];

		$size = @getimagesize($source_path, $info);
		if ($size && isset($info['APP13'])) {
			$iptc = iptcparse($info['APP13']);

			if (is_array($iptc)) {
				$exif['iptc']['caption'] = $iptc['2#120'][0] ?? '';
				$exif['iptc']['graphic_name'] = $iptc['2#005'][0] ?? '';
				$exif['iptc']['urgency'] = $iptc['2#010'][0] ?? '';
				$exif['iptc']['category'] = $iptc['2#015'][0] ?? '';

				// supp_categories sometimes contains multiple entries!
				$exif['iptc']['supp_categories'] = $iptc['2#020'][0] ?? '';
				$exif['iptc']['spec_instr'] = $iptc['2#040'][0] ?? '';
				$exif['iptc']['creation_date'] = $iptc['2#055'][0] ?? '';
				$exif['iptc']['photog'] = $iptc['2#080'][0] ?? '';
				$exif['iptc']['credit_byline_title'] = $iptc['2#085'][0] ?? '';
				$exif['iptc']['city'] = $iptc['2#090'][0] ?? '';
				$exif['iptc']['state'] = $iptc['2#095'][0] ?? '';
				$exif['iptc']['country'] = $iptc['2#101'][0] ?? '';
				$exif['iptc']['otr'] = $iptc['2#103'][0] ?? '';
				$exif['iptc']['headline'] = $iptc['2#105'][0] ?? '';
				$exif['iptc']['source'] = $iptc['2#110'][0] ?? '';
				$exif['iptc']['photo_source'] = $iptc['2#115'][0] ?? '';
				$exif['iptc']['caption'] = $iptc['2#120'][0] ?? '';

				$exif['iptc']['keywords'] = $iptc['2#025'] ?? '';
			}
		}

		$exif['iptc'] = apply_filters('shp_additional_metadata/iptc', $exif['iptc'], $source_path);

		return apply_filters('shp_additional_metadata/all_exif', $exif, $source_path);
	}

	/**
	 * Convert degrees, minutes, seconds to decimal format (longitude/latitude).
	 *
	 * @param int $deg Degrees
	 * @param int $min Minutes
	 * @param int $sec Seconds
	 *
	 * @return int The converted decimal-format value.
	 */
	private function DMStoDEC($deg, $min, $sec)
	{
		return $deg + ((($min * 60) + ($sec)) / 3600);
	}
}
