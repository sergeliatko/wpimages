<?php


namespace SergeLiatko\WPImages;


/**
 * Trait IsEmptyTrait
 *
 * @package SergeLiatko\WPImages
 */
trait IsEmptyTrait {

	/**
	 * @param mixed|null $data
	 *
	 * @return bool
	 */
	public static function isEmpty( mixed $data = null ): bool {
		return empty( $data );
	}

}
