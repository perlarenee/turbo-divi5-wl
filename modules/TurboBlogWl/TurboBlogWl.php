<?php
/**
 * Module: Turbo Blog WL class.
 *
 * @package MEE\Modules\TurboBlogWl
 * @since ??
 */

namespace MEE\Modules\TurboBlogWl;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use ET\Builder\Framework\DependencyManagement\Interfaces\DependencyInterface;
use ET\Builder\Packages\ModuleLibrary\ModuleRegistration;
use MEE\Modules\TurboBlogWl\TurboBlogWlTrait;

/**
 * `TurboBlogWl` is consisted of functions used for Turbo Blog WL such as Front-End rendering, REST API Endpoints etc.
 *
 * This is a dependency class and can be used as a dependency for `DependencyTree`.
 *
 * @since ??
 */
class TurboBlogWl implements DependencyInterface {
	use TurboBlogWlTrait\RenderCallbackTrait;

	/**
	 * Loads `TurboBlogWl` and registers Front-End render callback and REST API Endpoints.
	 *
	 * @since ??
	 *
	 * @return void
	 */
	public function load() {
		$module_json_folder_path = D5_EXTENSION_EXAMPLE_MODULES_JSON_PATH . 'turbo-blog-wl/';

		add_action(
			'init',
			function() use ( $module_json_folder_path ) {
				ModuleRegistration::register_module(
					$module_json_folder_path,
					[
						'render_callback' => [ TurboBlogWl::class, 'render_callback' ],
					]
				);
			}
		);
	}
}
