<?php
/**
 * MasonryGalleryItem::render_callback()
 *
 * @package MEE\Modules\MasonryGalleryItem
 * @since ??
 */

namespace MEE\Modules\MasonryGalleryItem\MasonryGalleryItemTrait;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use ET\Builder\Packages\Module\Module;
use ET\Builder\Framework\Utility\HTMLUtility;
use ET\Builder\FrontEnd\BlockParser\BlockParserStore;
use ET\Builder\Packages\Module\Options\Element\ElementComponents;
use MEE\Modules\MasonryGalleryItem\MasonryGalleryItem;

trait RenderCallbackTrait {
	use ModuleClassnamesTrait;
	use ModuleStylesTrait;

	/**
	 * Child module render callback which outputs server side rendered HTML on the Front-End.
	 *
	 * @since ??
	 *
	 * @param array          $attrs    Block attributes that were saved by VB.
	 * @param string         $content  Block content.
	 * @param \WP_Block      $block    Parsed block object that being rendered.
	 * @param ModuleElements $elements ModuleElements instance.
	 *
	 * @return string HTML rendered of Masonry Gallery Item module.
	 */
	public static function render_callback( $attrs, $content, $block, $elements ) {
		
		$parent = BlockParserStore::get_parent( $block->parsed_block['id'], $block->parsed_block['storeInstance'] );
		$parent_attrs = $parent->attrs ?? [];

		// Get image data - it comes as a URL string, not an ID
		$image_upload = $attrs['image']['innerContent']['desktop']['value'] ?? '';
		$caption_text = $attrs['caption']['innerContent']['desktop']['value'] ?? '';
		$show_caption = ( $attrs['showCaption']['innerContent']['desktop']['value'] ?? 'off' ) === 'on';

		// Get parent settings
		$image_fit = $parent_attrs['imageFit']['innerContent']['desktop']['value'] ?? 'cover';
		$enable_lightbox = ( $parent_attrs['enableLightbox']['innerContent']['desktop']['value'] ?? 'on' ) === 'on';

		// Image URL comes directly from upload field
		$image_url = '';
		$image_alt = '';
		$width = 0;
		$height = 0;

		if ( is_string( $image_upload ) && filter_var( $image_upload, FILTER_VALIDATE_URL ) ) {
			// We have a URL
			$image_url = $image_upload;
			// Try to get alt text and dimensions from attachment
			$attachment_id = attachment_url_to_postid( $image_url );
			if ( $attachment_id ) {
				$image_alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
				
				// Get image dimensions for orientation detection
				$image_data = wp_get_attachment_metadata( $attachment_id );
				$width = $image_data['width'] ?? 0;
				$height = $image_data['height'] ?? 0;
			}
		}

		// If no valid image, return empty
		if ( ! $image_url ) {
			return '';
		}

		// Detect orientation and determine size class
		$size_class = '';

		// Check for manual spans first - they override auto-detection
		$manual_row_span = absint( $attrs['rowSpan']['innerContent']['desktop']['value']['rowSpan'] ?? 1 );
		$manual_col_span = absint( $attrs['columnSpan']['innerContent']['desktop']['value']['columnSpan'] ?? 1 );
		$has_manual_spans = $manual_row_span > 1 || $manual_col_span > 1;

		if ( $has_manual_spans ) {
			// Manual spans override everything
			if ( $manual_row_span > 1 && $manual_col_span > 1 ) {
				$size_class = ' masonry_gallery__item--big';
			} elseif ( $manual_row_span > 1 ) {
				$size_class = ' masonry_gallery__item--tall';
			} elseif ( $manual_col_span > 1 ) {
				$size_class = ' masonry_gallery__item--wide';
			}
		} else {
			// Check auto-detection
			$auto_detect = ( $parent_attrs['autoDetectOrientation']['innerContent']['desktop']['value'] ?? 'on' ) === 'on';

			if ( $auto_detect && $width > 0 && $height > 0 ) {
				// Auto mode - detect from image dimensions
				$aspect_ratio = $width / $height;
				
				// Detect orientation
				if ( $aspect_ratio < 0.85 ) {
					// Portrait
					$size_class = ' masonry_gallery__item--tall';
				} elseif ( $aspect_ratio > 1.15 ) {
					// Landscape - always span 2 columns
					$size_class = ' masonry_gallery__item--wide';
				}
				
				// 5% chance for big items
				if ( ! empty( $size_class ) && rand( 1, 100 ) > 95 ) {
					$size_class = ' masonry_gallery__item--big';
				}
			}
			// If auto-detect is OFF and no manual spans, size_class stays empty (default 1x1)
		}

		// Build manual span inline styles (always apply if set)
		$manual_styles = [];
		if ( $manual_row_span > 1 ) {
			$manual_styles[] = 'grid-row: span ' . $manual_row_span;
		}
		if ( $manual_col_span > 1 ) {
			$manual_styles[] = 'grid-column: span ' . $manual_col_span;
		}

		// Image element
		$image_html = HTMLUtility::render([
			'tag' => 'img',
			'attributes' => [
				'src' => esc_url( $image_url ),
				'alt' => esc_attr( $image_alt ),
				'loading' => 'lazy',
				'style' => 'width: 100%; height: 100%; object-fit: ' . esc_attr( $image_fit ) . '; display: block;',
			],
		]);

		// Wrap image in link if lightbox enabled
		if ( $enable_lightbox ) {
			$image_html = HTMLUtility::render([
				'tag' => 'a',
				'attributes' => [
					'href' => esc_url( $image_url ),
					'class' => 'masonry_gallery__lightbox-link',
					'data-lightbox' => 'gallery',
					'data-src' => esc_url( $image_url ),
					'data-alt' => esc_attr( $image_alt ),
					'data-caption' => esc_attr( $caption_text ), 
				],
				'childrenSanitizer' => 'et_core_esc_previously',
				'children' => $image_html,
			]);
		}

		// Caption if enabled
		$caption_html = '';
		if ( $show_caption && ! empty( $caption_text ) ) {
			$caption_html = HTMLUtility::render([
				'tag' => 'div',
				'attributes' => [
					'class' => 'masonry_gallery__caption',
				],
				'childrenSanitizer' => 'wp_kses_post',
				'children' => $caption_text,
			]);
		}

		// Item content container
		$item_content = HTMLUtility::render([
			'tag' => 'div',
			'attributes' => [
				'class' => 'masonry_gallery__item-content',
			],
			'childrenSanitizer' => 'et_core_esc_previously',
			'children' => $image_html . $caption_html,
		]);

		$html_attrs = [
			'class' => 'masonry_gallery__item' . $size_class,
		];

		// Add inline styles for manual spans
		if ( ! $auto_detect && ( $manual_row_span > 1 || $manual_col_span > 1 ) ) {
			$manual_styles = [];
			if ( $manual_row_span > 1 ) {
				$manual_styles[] = 'grid-row: span ' . $manual_row_span;
			}
			if ( $manual_col_span > 1 ) {
				$manual_styles[] = 'grid-column: span ' . $manual_col_span;
			}
			if ( ! empty( $manual_styles ) ) {
				$html_attrs['style'] = implode( '; ', $manual_styles );
			}
		}

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
			'classnamesFunction' => [ MasonryGalleryItem::class, 'module_classnames' ],
			'stylesComponent' => [ MasonryGalleryItem::class, 'module_styles' ],
			'parentAttrs' => $parent_attrs,
			'parentId' => $parent->id ?? '',
			'parentName' => $parent->blockName ?? '',
			'htmlAttrs' => $html_attrs,
			'children' => ElementComponents::component([
				'attrs' => $attrs['module']['decoration'] ?? [],
				'id' => $block->parsed_block['id'],
				'orderIndex' => $block->parsed_block['orderIndex'],
				'storeInstance' => $block->parsed_block['storeInstance'],
			]) . $item_content,
		]);
	}
}