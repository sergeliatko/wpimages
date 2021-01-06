<?php


namespace SergeLiatko\WPImages;

use WP_Error;

/**
 * Class Import
 *
 * @package SergeLiatko\WPImages
 */
class Import {

	use IsEmptyTrait;

	public const TIMEOUT_DELAY           = 30;
	public const INVALID_URL             = 'Invalid URL';
	public const INVALID_FILE_NAME       = 'Invalid file name';
	public const HAS_MULTIPLE_EXTENSIONS = 'File has multiple extensions';

	/**
	 * Downloads the image from URL to media library and returns its ID. Returns WP_Error object on failure.
	 *
	 * @param string      $url
	 * @param string      $title
	 * @param int         $parent_id
	 * @param string|null $file_name_overwrite
	 *
	 * @return int|\WP_Error
	 * @noinspection PhpUnused
	 */
	public static function fromURL( string $url, string $title = '', int $parent_id = 0, ?string $file_name_overwrite = null ) {
		// make sure all functions are loaded before going further
		self::makeSureFunctionsAreLoaded();
		//sanitize url
		if ( self::isEmpty( $url = Tools::sanitizeURL( $url ) ) ) {
			return new WP_Error( 'invalid_url', self::INVALID_URL, array( 'url' => $url ) );
		}
		//check for multiple extensions
		if ( Tools::hasMultipleExtensions( $url ) ) {
			return new WP_Error( 'has_multiple_extensions', self::HAS_MULTIPLE_EXTENSIONS, array( 'url' => $url ) );
		}
		//sanitize file name
		if ( self::isEmpty( $file_name = Tools::getSanitizedFileName( $url ) ) ) {
			return new WP_Error( 'invalid_file_name', self::INVALID_FILE_NAME, array( 'url' => $url ) );
		}
		// try to download the image to temporary file
		if ( is_wp_error( $tmp_file = download_url( $url, self::TIMEOUT_DELAY ) ) ) {
			/** @var \WP_Error $tmp_file */
			return $tmp_file;
		}
		//if file extension is empty try to get it from temp file
		if ( self::isEmpty( Tools::getFileExtension( $file_name ) ) ) {
			if ( self::isEmpty( $extension = Tools::getImageRealExtension( $tmp_file ) ) ) {
				return new WP_Error( 'invalid_file_name', self::INVALID_FILE_NAME, array( 'url' => $url ) );
			}
			//add real extension to the file name
			$file_name = implode( '.', array( $file_name, $extension ) );
		}
		// maybe overwrite file name
		if ( !is_null( $file_name_overwrite ) ) {
			$file_name = Tools::overwriteImageFileName( $file_name, $file_name_overwrite );
		}
		// try to add data (if present) to media post
		$media_post = self::isEmpty( $title = sanitize_text_field( $title ) ) ?
			array()
			: array(
				'post_title' => $title,
				'meta_input' => array(
					'_wp_attachment_image_alt' => $title,
				),
			);
		// handle downloaded image to media library and get the id or error
		$id = media_handle_sideload(
			array(
				'name'     => $file_name,
				/** @var string $tmp_file */
				'tmp_name' => $tmp_file,
			),
			$parent_id,
			$title,
			$media_post
		);
		// delete the temporary file
		@unlink( $tmp_file );

		/** @var int|\WP_Error $id */
		return $id;
	}

	/**
	 * Loads WordPress functions if necessary.
	 */
	public static function makeSureFunctionsAreLoaded(): void {
		if ( !function_exists( 'download_url' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
		}
		if ( !function_exists( 'media_handle_sideload' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/media.php' );
		}
		if ( !function_exists( 'wp_read_image_metadata' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
		}
	}

}
