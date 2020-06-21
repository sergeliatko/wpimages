<?php


namespace SergeLiatko\WPImages;


/**
 * Trait IsEmptyTrait
 *
 * @package SergeLiatko\WPImages
 */
trait IsEmptyTrait {

	/**
	 * @param mixed $data
	 *
	 * @return bool
	 */
	public static function isEmpty( $data = null ) {
		return empty( $data );
	}

}
