<?php
/**
 * Module: Masonry Gallery Item class.
 *
 * @package MEE\Modules\MasonryGalleryItem
 * @since ??
 */

namespace MEE\Modules\MasonryGalleryItem;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use ET\Builder\Framework\DependencyManagement\Interfaces\DependencyInterface;
use ET\Builder\Packages\ModuleLibrary\ModuleRegistration;
use MEE\Modules\MasonryGalleryItem\MasonryGalleryItemTrait;

/**
 * `MasonryGalleryItem` child module for individual gallery images.
 *
 * @since ??
 */
class MasonryGalleryItem implements DependencyInterface {
	use MasonryGalleryItemTrait\RenderCallbackTrait;

	/**
	 * Loads `MasonryGalleryItem` and registers render callback.
	 *
	 * @since ??
	 *
	 * @return void
	 */
	public function load() {
		$module_json_folder_path = D5_EXTENSION_EXAMPLE_MODULES_JSON_PATH . 'masonry-gallery-item/';

		add_action(
			'init',
			function() use ( $module_json_folder_path ) {
				ModuleRegistration::register_module(
					$module_json_folder_path,
					[
						'render_callback' => [ MasonryGalleryItem::class, 'render_callback' ],
					]
				);
			}
		);
	}
}
