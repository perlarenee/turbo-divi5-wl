<?php
/*
Plugin Name: Turbo Divi5 WebLocomotive Modules
Plugin URI:
Description: Extentions for Divi5 by WebLocomotive
Version:     1.0.0
Author:      WebLocomotive
Author URI:  https://weblocomotive.com
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: d5-extension-example-modules
Domain Path: /languages
*/

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

define( 'D5_EXTENSION_EXAMPLE_MODULES_PATH', plugin_dir_path( __FILE__ ) );
define( 'D5_EXTENSION_EXAMPLE_MODULES_JSON_PATH', D5_EXTENSION_EXAMPLE_MODULES_PATH . 'modules-json/' );

/**
 * Requires Autoloader.
 */
require D5_EXTENSION_EXAMPLE_MODULES_PATH . 'vendor/autoload.php';
require D5_EXTENSION_EXAMPLE_MODULES_PATH . 'modules/Modules.php';

/**
 * Enqueue style and scripts of Module Extension Example for Visual Builder.
 *
 * @since ??
 */
function d5_extension_example_module_enqueue_vb_scripts() {
	if ( et_builder_d5_enabled() && et_core_is_fb_enabled() ) {
		$plugin_dir_url = plugin_dir_url( __FILE__ );

		\ET\Builder\VisualBuilder\Assets\PackageBuildManager::register_package_build(
			[
				'name'   => 'd5-extension-example-modules-builder-bundle-script',
				'version' => '1.0.0',
				'script' => [
					'src' => "{$plugin_dir_url}scripts/bundle.js",
					'deps'               => [
						'divi-module-library',
						'divi-vendor-wp-hooks',
					],
					'enqueue_top_window' => false,
					'enqueue_app_window' => true,
				],
			]
		);

		\ET\Builder\VisualBuilder\Assets\PackageBuildManager::register_package_build(
			[
				'name'   => 'd5-extension-example-modules-builder-vb-bundle-style',
				'version' => '1.0.0',
				'style' => [
					'src' => "{$plugin_dir_url}styles/vb-bundle.css",
					'deps'               => [],
					'enqueue_top_window' => false,
					'enqueue_app_window' => true,
				],
			]
		);
	}
}
add_action( 'divi_visual_builder_assets_before_enqueue_scripts', 'd5_extension_example_module_enqueue_vb_scripts' );

/**
 * Enqueue style and scripts of Module Extension Example
 *
 * @since ??
 */
function d5_extension_example_module_enqueue_frontend_scripts() {
	$plugin_dir_url = plugin_dir_url( __FILE__ );
	wp_enqueue_style( 'd5-extension-example-modules-builder-bundle-style', "{$plugin_dir_url}styles/bundle.css", array(), '1.0.0' );
}
add_action( 'wp_enqueue_scripts', 'd5_extension_example_module_enqueue_frontend_scripts' );