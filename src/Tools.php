<?php


namespace SergeLiatko\WPImages;

/**
 * Class Tools
 *
 * @package SergeLiatko\WPImages
 */
class Tools {

	/**
	 * @param string $url
	 *
	 * @return string
	 */
	public static function getImageFileName( string $url ) {
		preg_match( '/[^\/]+\.(jpe?g|jpe|gif|png)\b/i', $url, $matches );

		return empty( $matches[0] ) ? '' : sanitize_file_name( $matches[0] );
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
