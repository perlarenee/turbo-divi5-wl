<?php
/**
 * MasonryGallery::render_callback()
 *
 * @package MEE\Modules\MasonryGallery
 * @since ??
 */

namespace MEE\Modules\MasonryGallery\MasonryGalleryTrait;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use ET\Builder\Packages\Module\Module;
use ET\Builder\FrontEnd\BlockParser\BlockParserStore;
use ET\Builder\Framework\Utility\HTMLUtility;
use ET\Builder\Packages\Module\Options\Element\ElementComponents;
use MEE\Modules\MasonryGallery\MasonryGallery;

trait RenderCallbackTrait {
	use ModuleClassnamesTrait;
	use ModuleStylesTrait;
	use ModuleScriptDataTrait;

	/**
	 * Parent module render callback which outputs server side rendered HTML on the Front-End.
	 *
	 * @since ??
	 *
	 * @param array          $attrs    Block attributes that were saved by VB.
	 * @param string         $content  Block content (rendered children).
	 * @param \WP_Block      $block    Parsed block object that being rendered.
	 * @param ModuleElements $elements ModuleElements instance.
	 *
	 * @return string HTML rendered of Masonry Gallery module.
	 */
	public static function render_callback( $attrs, $content, $block, $elements ) {

		// Enqueue Divi's Magnific Popup for lightbox
		$enable_lightbox = ( $attrs['enableLightbox']['innerContent']['desktop']['value'] ?? 'on' ) === 'on';
		if ( $enable_lightbox ) {
			wp_enqueue_script( 'magnific-popup' );
			wp_enqueue_style( 'magnific-popup' );
		}

		// Enqueue Magnific Popup for lightbox
		$enable_lightbox = ( $attrs['enableLightbox']['innerContent']['desktop']['value'] ?? 'on' ) === 'on';
		if ( $enable_lightbox ) {
			// Register Magnific Popup from CDN if not already available
			if ( ! wp_script_is( 'magnific-popup', 'registered' ) ) {
				wp_register_script(
					'magnific-popup',
					'https://cdnjs.cloudflare.com/ajax/libs/magnific-popup.js/1.1.0/jquery.magnific-popup.min.js',
					array( 'jquery' ),
					'1.1.0',
					true
				);
				wp_register_style(
					'magnific-popup',
					'https://cdnjs.cloudflare.com/ajax/libs/magnific-popup.js/1.1.0/magnific-popup.min.css',
					array(),
					'1.1.0'
				);
			}
			
			wp_enqueue_script( 'magnific-popup' );
			wp_enqueue_style( 'magnific-popup' );
			
			// Enqueue our lightbox initialization
			$plugin_url = plugin_dir_url( dirname( dirname( dirname( __FILE__ ) ) ) );
			wp_enqueue_script(
				'd5-masonry-gallery-lightbox',
				$plugin_url . 'scripts/masonry-gallery-lightbox.js',
				array( 'jquery', 'magnific-popup' ),
				'1.0.0',
				true
			);
		}

		// Get settings
		$min_column_width = $attrs['minColumnWidth']['innerContent']['desktop']['value']['minColumnWidth'] ?? '250px';
		$row_height = $attrs['rowHeight']['innerContent']['desktop']['value']['rowHeight'] ?? '200px';
		$gap = $attrs['gap']['innerContent']['desktop']['value']['gap'] ?? '10px';

		// Children IDs for future use
		$children_ids = $block->parsed_block['innerBlocks'] ? array_map(
			function( $inner_block ) {
				return $inner_block['id'];
			},
			$block->parsed_block['innerBlocks']
		) : [];

		// Grid styles - only dynamic settings that users can control
		$grid_style = sprintf(
			'grid-gap: %s; grid-auto-rows: %s;',
			esc_attr( $gap ),
			esc_attr( $row_height )
		);

		// Gallery grid wrapper
		$gallery_grid = HTMLUtility::render([
			'tag' => 'div',
			'attributes' => [
				'class' => 'masonry_gallery__grid',
				'style' => $grid_style,
			],
			'childrenSanitizer' => 'et_core_esc_previously',
			'children' => $content, // WordPress already rendered the children for us!
		]);

		// Title
		$title = $elements->render([
			'attrName' => 'title',
		]);

		// Gallery container
		$gallery_container = HTMLUtility::render([
			'tag' => 'div',
			'attributes' => [
				'class' => 'masonry_gallery__inner',
			],
			'childrenSanitizer' => 'et_core_esc_previously',
			'children' => $title . $gallery_grid,
		]);

		$parent = BlockParserStore::get_parent( $block->parsed_block['id'], $block->parsed_block['storeInstance'] );
		$parent_attrs = $parent->attrs ?? [];

		return Module::render([
			// FE only
			'orderIndex' => $block->parsed_block['orderIndex'],
			'storeInstance' => $block->parsed_block['storeInstance'],

			// VB equivalent
			'id' => $block->parsed_block['id'],
			'name' => $block->block_type->name,
			'moduleCategory' => $block->block_type->category,
			'attrs' => $attrs,
			'elements' => $elements,
			'classnamesFunction' => [ MasonryGallery::class, 'module_classnames' ],
			'scriptDataComponent' => [ MasonryGallery::class, 'module_script_data' ],
			'stylesComponent' => [ MasonryGallery::class, 'module_styles' ],
			'parentAttrs' => $parent_attrs,
			'parentId' => $parent->id ?? '',
			'parentName' => $parent->blockName ?? '',
			'children' => ElementComponents::component([
				'attrs' => $attrs['module']['decoration'] ?? [],
				'id' => $block->parsed_block['id'],
				'orderIndex' => $block->parsed_block['orderIndex'],
				'storeInstance' => $block->parsed_block['storeInstance'],
			]) . $gallery_container,
			'childrenIds' => $children_ids,
		]);
	}
}
