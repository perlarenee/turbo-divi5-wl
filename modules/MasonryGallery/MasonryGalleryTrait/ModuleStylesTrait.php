<?php
/**
 * MasonryGallery::module_styles().
 *
 * @package MEE\Modules\MasonryGallery
 * @since ??
 */

namespace MEE\Modules\MasonryGallery\MasonryGalleryTrait;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

trait ModuleStylesTrait {

	/**
	 * Module styles function for Masonry Gallery.
	 *
	 * @since ??
	 *
	 * @param array $args {
	 *     An array of arguments.
	 *
	 *     @type array $attrs Block attributes data that being rendered.
	 * }
	 *
	 * @return array Styles array.
	 */
	public static function module_styles( $args ) {
		$attrs = $args['attrs'] ?? [];
		
		return [];
	}

}
