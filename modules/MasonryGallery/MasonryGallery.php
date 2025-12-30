<?php
/**
 * Module: Masonry Gallery class.
 *
 * @package MEE\Modules\MasonryGallery
 * @since ??
 */

namespace MEE\Modules\MasonryGallery;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use ET\Builder\Framework\DependencyManagement\Interfaces\DependencyInterface;
use ET\Builder\Packages\ModuleLibrary\ModuleRegistration;
use MEE\Modules\MasonryGallery\MasonryGalleryTrait;

/**
 * `MasonryGallery` is consisted of functions used for Masonry Gallery such as Front-End rendering, REST API Endpoints etc.
 *
 * This is a dependency class and can be used as a dependency for `DependencyTree`.
 *
 * @since ??
 */
class MasonryGallery implements DependencyInterface {
	use MasonryGalleryTrait\RenderCallbackTrait;

	/**
	 * Loads `MasonryGallery` and registers Front-End render callback and REST API Endpoints.
	 *
	 * @since ??
	 *
	 * @return void
	 */
	public function load() {
		$module_json_folder_path = D5_EXTENSION_EXAMPLE_MODULES_JSON_PATH . 'masonry-gallery/';

		add_action(
			'init',
			function() use ( $module_json_folder_path ) {
				ModuleRegistration::register_module(
					$module_json_folder_path,
					[
						'render_callback' => [ MasonryGallery::class, 'render_callback' ],
					]
				);
			}
		);
	}
}
