<?php
/**
 * Register all modules with dependency tree.
 *
 * @package MEE\Modules
 * @since ??
 */

namespace MEE\Modules;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use MEE\Modules\TurboBlogWl\TurboBlogWl;
use MEE\Modules\MasonryGallery\MasonryGallery; 
use MEE\Modules\MasonryGalleryItem\MasonryGalleryItem; 


add_action(
	'divi_module_library_modules_dependency_tree',
	function ( $dependency_tree ) {
		$dependency_tree->add_dependency( new TurboBlogWl() ); 
		$dependency_tree->add_dependency( new MasonryGallery() ); 
		$dependency_tree->add_dependency( new MasonryGalleryItem() );
	}
);
