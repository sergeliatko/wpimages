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

	public const TIMEOUT_DELAY = 60;
	public const INVALID_URL = 'Invalid URL';
	public const INVALID_FILE_NAME = 'Invalid file name';
	public const HAS_MULTIPLE_EXTENSIONS = 'File has multiple extensions';

	/**
	 * Downloads the image from URL to media library and returns its ID. Returns WP_Error object on failure.
	 *
	 * @param string $url
	 * @param string $title
	 * @param int $parent_id
	 * @param string|null $file_name_overwrite
	 * @param array|null $attachment_post
	 * @param string|null $log_file
	 *
	 * @return int|WP_Error
	 * @noinspection PhpUnused
	 */
	public static function fromURL(
		string $url,
		string $title = '',
		int $parent_id = 0,
		?string $file_name_overwrite = null,
		?array $attachment_post = null,
		?string $log_file = null
	): WP_Error|int {
		// make sure all functions are loaded before going further
		self::makeSureFunctionsAreLoaded();
		//sanitize url
		if ( self::isEmpty( $sanitized_url = Tools::sanitizeURL( $url ) ) ) {
			return self::maybeLogError(
				new WP_Error(
					'invalid_url',
					self::INVALID_URL,
					[
						'url'                 => $url,
						'title'               => $title,
						'parent_id'           => $parent_id,
						'file_name_overwrite' => $file_name_overwrite,
						'sanitized_url'       => $sanitized_url,
					]
				),
				$log_file
			);
		}
		//check for multiple extensions
		if ( Tools::hasMultipleExtensions( $sanitized_url ) ) {
			return self::maybeLogError(
				new WP_Error(
					'has_multiple_extensions',
					self::HAS_MULTIPLE_EXTENSIONS,
					[
						'url'                 => $url,
						'title'               => $title,
						'parent_id'           => $parent_id,
						'file_name_overwrite' => $file_name_overwrite,
						'sanitized_url'       => $sanitized_url,
					]
				),
				$log_file
			);
		}
		//sanitize file name
		if ( self::isEmpty( $file_name = Tools::getSanitizedFileName( $sanitized_url ) ) ) {
			return self::maybeLogError(
				new WP_Error(
					'invalid_file_name',
					self::INVALID_FILE_NAME,
					[
						'url'                 => $url,
						'title'               => $title,
						'parent_id'           => $parent_id,
						'file_name_overwrite' => $file_name_overwrite,
						'sanitized_url'       => $sanitized_url,
						'file_name'           => $file_name,
					]
				),
				$log_file
			);
		}
		// try to download the image to temporary file
		if ( is_wp_error( $tmp_file = download_url( $sanitized_url, self::TIMEOUT_DELAY ) ) ) {
			$tmp_file->add(
				'failed_to_download_file',
				'Failed to download file',
				[
					'url'                 => $url,
					'title'               => $title,
					'parent_id'           => $parent_id,
					'file_name_overwrite' => $file_name_overwrite,
					'sanitized_url'       => $sanitized_url,
					'file_name'           => $file_name,
				]
			);

			/** @var WP_Error $tmp_file */
			return self::maybeLogError(
				$tmp_file,
				$log_file
			);
		}
		//if file extension is empty try to get it from temp file
		if ( self::isEmpty( Tools::getFileExtension( $file_name ) ) ) {
			if ( self::isEmpty( $extension = Tools::getImageRealExtension( $tmp_file ) ) ) {
				return self::maybeLogError(
					new WP_Error(
						'invalid_file_name',
						self::INVALID_FILE_NAME,
						[
							'url'                 => $url,
							'title'               => $title,
							'parent_id'           => $parent_id,
							'file_name_overwrite' => $file_name_overwrite,
							'sanitized_url'       => $sanitized_url,
							'file_name'           => $file_name,
							'tmp_file'            => $tmp_file,
							'extension'           => $extension,
						]
					),
					$log_file
				);
			}
			//add real extension to the file name
			$file_name = implode( '.', [ $file_name, $extension ] );
		}
		// maybe overwrite file name
		if ( ! is_null( $file_name_overwrite ) ) {
			$file_name = Tools::overwriteImageFileName( $file_name, $file_name_overwrite );
		}
		// handle the attachment post data
		if ( ! is_array( $attachment_post ) ) {
			$attachment_post = [];
		}
		$post_title = self::isEmpty( $title ) ? $file_name : sanitize_text_field( $title );
		// try to add data (if present) to media post
		$media_post = [
			'post_title' => $post_title,
			'meta_input' => [
				'_wp_attachment_image_alt' => $title,
			]
		];
		// merge with passed attachment post data
		$attachment_post = wp_parse_args( $attachment_post, $media_post );
		// handle downloaded image to media library and get the id or error
		$id = media_handle_sideload(
			[
				'name'     => $file_name,
				/** @var string $tmp_file */
				'tmp_name' => $tmp_file,
			],
			$parent_id,
			$title,
			$attachment_post
		);
		// delete the temporary file
		@unlink( $tmp_file );

		if ( is_wp_error( $id ) ) {
			$id->add(
				'failed_to_upload_to_library',
				'Failed to upload to media library',
				[
					'url'                 => $url,
					'title'               => $title,
					'parent_id'           => $parent_id,
					'file_name_overwrite' => $file_name_overwrite,
					'sanitized_url'       => $sanitized_url,
					'file_name'           => $file_name,
					'tmp_file'            => $tmp_file,
				]
			);

			return self::maybeLogError( $id, $log_file );
		}

		/** @var int|WP_Error $id */
		return $id;
	}

	/**
	 * Loads WordPress functions if necessary.
	 */
	public static function makeSureFunctionsAreLoaded(): void {
		if ( ! function_exists( 'download_url' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
		}
		if ( ! function_exists( 'media_handle_sideload' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/media.php' );
		}
		if ( ! function_exists( 'wp_read_image_metadata' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
		}
	}

	/**
	 * @param WP_Error $error
	 * @param string $log_file
	 *
	 * @return WP_Error
	 */
	protected static function log( WP_Error $error, string $log_file = '' ): WP_Error {
		// trim file name
		$log_file = trim( $log_file );
		// if file name is empty add error and return
		if ( empty( $log_file ) ) {
			$log_file = self::get_default_log_file();
		}
		$message = '';
		$time    = current_time( 'mysql', true );
		foreach ( $error->get_error_codes() as $error_code ) {
			$data        = $error->get_error_data( $error_code );
			$data_string = empty( $data ) ? '' : strval( json_encode( $data ) );
			$message     .= "\r\n" . join(
					"\r\n",
					array_filter( [
						sprintf(
							'[%1$s UTC] %2$s %3$s',
							$time,
							$error_code,
							$error->get_error_message( $error_code )
						),
						$data_string,
					] )
				);
		}
		// log error
		if ( false === error_log( $message, 3, $log_file ) ) {
			$error->add(
				'failed_to_log_error',
				'Failed to log error',
				[
					'log_file' => $log_file,
					'message'  => $message,
				]
			);
		}

		return $error;
	}

	/**
	 * @return string
	 */
	protected static function get_default_log_file(): string {
		if ( defined( 'WP_DEBUG_LOG' ) ) {
			// If it's a string path, that's the custom location
			/** @noinspection PhpStrictComparisonWithOperandsOfDifferentTypesInspection */
			if ( is_string( WP_DEBUG_LOG ) && ( WP_DEBUG_LOG !== '' ) && file_exists( WP_DEBUG_LOG ) ) {
				return WP_DEBUG_LOG;
			}
			// If it's a boolean true, WordPress defaults to wp-content/debug.log
			if ( WP_DEBUG_LOG === true ) {
				return WP_CONTENT_DIR . '/debug.log';
			}
		}

		// Default to wp-content/debug.log if WP_DEBUG is true
		return ( defined( 'WP_DEBUG' ) && ( WP_DEBUG === true ) ) ? WP_CONTENT_DIR . '/debug.log' : '';
	}

	/**
	 * @param WP_Error $error
	 * @param string|null $log_file
	 *
	 * @return WP_Error
	 */
	public static function maybeLogError( WP_Error $error, ?string $log_file = null ): WP_Error {
		return empty( $log_file ) ? $error : self::log( $error, $log_file );
	}

}
