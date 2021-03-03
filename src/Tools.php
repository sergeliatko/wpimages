<?php


namespace SergeLiatko\WPImages;

/**
 * Class Tools
 *
 * @package SergeLiatko\WPImages
 */
class Tools {

	/**
	 * @param string $file_name
	 *
	 * @return string
	 */
	public static function getFileExtension( string $file_name ): string {
		return is_null( $extension = pathinfo( $file_name, PATHINFO_EXTENSION ) ) ? '' : $extension;
	}

	/**
	 * @param string $file_name
	 *
	 * @return string
	 */
	public static function getFileNameWithoutExtension( string $file_name ): string {
		return is_null( $name = pathinfo( $file_name, PATHINFO_FILENAME ) ) ? '' : $name;
	}

	/**
	 * @param string $url
	 *
	 * @return string
	 */
	public static function getSanitizedFileName( string $url ): string {
		return sanitize_file_name( basename( $url ) );
	}

	/**
	 * @param string $url
	 *
	 * @return bool
	 */
	public static function hasMultipleExtensions( string $url ): bool {
		$name = self::getFileNameWithoutExtension( self::getSanitizedFileName( $url ) );

		return ( false !== strpos( $name, '.' ) );
	}

	/**
	 * @param string $file
	 *
	 * @return string|null
	 */
	public static function getImageRealExtension( string $file ): ?string {
		$mime = wp_get_image_mime( $file );
		if ( empty( $mime ) || ( 0 !== strpos( $mime, 'image/' ) ) ) {
			return null;
		}
		/**
		 * Filters the list mapping image mime types to their respective extensions.
		 *
		 * @param array $mime_to_ext Array of image mime types and their matching extensions.
		 *
		 * @since 3.0.0
		 *
		 */
		$mime_to_ext = apply_filters(
			'getimagesize_mimes_to_exts',
			array(
				'image/jpeg' => 'jpg',
				'image/png'  => 'png',
				'image/gif'  => 'gif',
				'image/bmp'  => 'bmp',
				'image/tiff' => 'tif',
			)
		);

		return empty( $mime_to_ext[ $mime ] ) ? null : $mime_to_ext[ $mime ];
	}

	/**
	 * @param string $original_file_name
	 * @param string $new_text
	 *
	 * @return string
	 */
	public static function overwriteImageFileName( string $original_file_name, string $new_text ): string {
		$result = preg_replace(
			'/^[^.]+/',
			sanitize_title_with_dashes(
			// remove file extension at the end of the file name replacement
				( ( false === strpos( $new_text, '.' ) ) ? $new_text : pathinfo( $new_text, PATHINFO_FILENAME ) )
			),
			$original_file_name
		);

		return empty( $result ) ? $original_file_name : $result;
	}

	/**
	 * @param string $url
	 *
	 * @return string
	 */
	public static function sanitizeURL( string $url ): string {
		return esc_url_raw( $url, self::getAcceptedProtocols() );
	}

	/**
	 * @return string[]
	 */
	public static function getAcceptedProtocols(): array {
		return array( 'http', 'https' );
	}

}
