<?php
/**
 * MasonryGallery::module_script_data().
 *
 * @package MEE\Modules\MasonryGallery
 * @since ??
 */

namespace MEE\Modules\MasonryGallery\MasonryGalleryTrait;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

trait ModuleScriptDataTrait {

	/**
	 * Module script data function for Masonry Gallery.
	 *
	 * @since ??
	 *
	 * @param array $args {
	 *     An array of arguments.
	 *
	 *     @type array $attrs Block attributes data that being rendered.
	 * }
	 *
	 * @return array Script data.
	 */
	public static function module_script_data( $args ) {
		return [];
	}

}
