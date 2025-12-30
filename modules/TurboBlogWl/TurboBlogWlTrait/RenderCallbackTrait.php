<?php
/**
 * TurboBlogWl::render_callback()
 *
 * @package MEE\Modules\TurboBlogWl
 * @since ??
 */

namespace MEE\Modules\TurboBlogWl\TurboBlogWlTrait;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use ET\Builder\Packages\Module\Module;
use ET\Builder\FrontEnd\BlockParser\BlockParserStore;
use ET\Builder\Framework\Utility\HTMLUtility;
use ET\Builder\Packages\Module\Layout\Components\ModuleElements\ModuleElements;
use ET\Builder\Packages\Module\Options\Element\ElementComponents;


trait RenderCallbackTrait {
	use ModuleClassnamesTrait;
	use ModuleStylesTrait;
	use ModuleScriptDataTrait;
	use StylesHookTrait;
	
	/**
	 * Generate pagination range with ellipsis
	 *
	 * @since ??
	 *
	 * @param int $current_page Current page number.
	 * @param int $total_pages  Total number of pages.
	 *
	 * @return array Array of page numbers and ellipsis strings.
	 */
	private static function get_pagination_range( $current_page, $total_pages ) {
		$delta           = 2; // Number of pages to show around current page
		$range           = [];
		$range_with_dots = [];
		$l               = null;

		// If total pages is small, just return all pages
		if ( $total_pages <= 7 ) {
			for ( $i = 1; $i <= $total_pages; $i++ ) {
				$range[] = $i;
			}
			return $range;
		}

		// Always include first page
		$range[] = 1;

		// Add pages around current page
		for ( $i = $current_page - $delta; $i <= $current_page + $delta; $i++ ) {
			if ( $i > 1 && $i < $total_pages ) {
				$range[] = $i;
			}
		}

		// Always include last page
		if ( $total_pages > 1 ) {
			$range[] = $total_pages;
		}

		// Add dots where there are gaps
		foreach ( $range as $i ) {
			if ( $l !== null ) {
				if ( $i - $l === 2 ) {
					$range_with_dots[] = $l + 1;
				} elseif ( $i - $l !== 1 ) {
					$range_with_dots[] = '...';
				}
			}
			$range_with_dots[] = $i;
			$l                 = $i;
		}

		return $range_with_dots;
	}

	/**
	 * Clean and validate comma-separated IDs
	 * Removes duplicates, trailing commas, invalid IDs
	 *
	 * @since ??
	 *
	 * @param string $ids_string Comma-separated string of IDs.
	 *
	 * @return string Cleaned comma-separated string of valid IDs.
	 */
	private static function clean_and_validate_ids( $ids_string ) {
		if ( empty( $ids_string ) ) {
			return '';
		}
		
		// Cast to string in case it's an integer
		$ids_string = (string) $ids_string;
		
		// Split by comma, trim each part
		$ids = array_map( 'trim', explode( ',', $ids_string ) );
		
		// Filter out empty strings and non-numeric values
		$ids = array_filter( $ids, function( $id ) {
			return ! empty( $id ) && ctype_digit( $id );
		});
		
		// Remove duplicates
		$ids = array_unique( $ids );
		
		// Return comma-separated string or empty
		return ! empty( $ids ) ? implode( ',', $ids ) : '';
	}

	/**
	 * Generate custom excerpt from post content
	 *
	 * @since ??
	 *
	 * @param \WP_Post $post          Post object.
	 * @param int      $excerpt_length Length of excerpt in characters.
	 *
	 * @return string Custom excerpt.
	 */
	private static function get_custom_excerpt( $post, $excerpt_length = 270 ) {
		// Priority 1: Manual excerpt
		if ( ! empty( $post->post_excerpt ) ) {
			return wp_trim_words( $post->post_excerpt, $excerpt_length / 6, '...' );
		}

		$content = '';

		// Priority 2: Try post_content first
		if ( ! empty( $post->post_content ) ) {
			$content = $post->post_content;
		} else {
			// Priority 3: Fall back to _et_pb_old_content meta
			$old_content = get_post_meta( $post->ID, '_et_pb_old_content', true );
			if ( ! empty( $old_content ) ) {
				$content = $old_content;
			}
		}

		// If still empty, return empty string
		if ( empty( $content ) ) {
			return '';
		}

		// Check content type and process accordingly
		$has_divi_blocks = strpos( $content, '<!-- wp:divi/' ) !== false;
		$has_gutenberg_blocks = strpos( $content, '<!-- wp:' ) !== false;
		$has_divi_shortcodes = strpos( $content, '[et_pb_' ) !== false;

		if ( $has_divi_blocks || $has_gutenberg_blocks ) {
			// Parse Gutenberg/Divi blocks
			$content = self::extract_text_from_blocks( $content );
		} elseif ( $has_divi_shortcodes ) {
			// Strip Divi shortcodes
			$content = preg_replace( '/\[.*?\]/', ' ', $content );
			$content = preg_replace( '/\s+/', ' ', $content );
		}

		// Strip all HTML tags
		$content = wp_strip_all_tags( $content );
		
		// Remove shortcodes that might remain
		$content = strip_shortcodes( $content );
		
		// Trim whitespace
		$content = trim( $content );

		// Truncate to character length
		if ( mb_strlen( $content ) > $excerpt_length ) {
			$content = mb_substr( $content, 0, $excerpt_length );
			
			// Break at last word boundary
			$last_space = mb_strrpos( $content, ' ' );
			if ( $last_space !== false ) {
				$content = mb_substr( $content, 0, $last_space );
			}
			
			$content .= '...';
		}

		return $content;
	}

	/**
	 * Extract text content from Gutenberg/Divi blocks
	 *
	 * @since ??
	 *
	 * @param string $block_content Block content.
	 *
	 * @return string Extracted text.
	 */
	private static function extract_text_from_blocks( $block_content ) {
		// Parse blocks
		$blocks = parse_blocks( $block_content );
		
		$text_content = [];
		
		// Recursively extract text from blocks
		$extract_text = function( $blocks ) use ( &$extract_text, &$text_content ) {
			foreach ( $blocks as $block ) {
				// Skip empty blocks
				if ( empty( $block['blockName'] ) ) {
					continue;
				}
				
				// For Divi blocks, check the attrs for content
				if ( strpos( $block['blockName'], 'divi/' ) !== false && ! empty( $block['attrs'] ) ) {
					// Look for common content paths in Divi blocks
					$content_found = false;
					
					// Check for content.innerContent.desktop.value (text modules)
					if ( isset( $block['attrs']['content']['innerContent']['desktop']['value'] ) ) {
						$text = wp_strip_all_tags( $block['attrs']['content']['innerContent']['desktop']['value'] );
						if ( ! empty( trim( $text ) ) ) {
							$text_content[] = $text;
							$content_found = true;
						}
					}
					
					// Check for innerContent attribute (some modules)
					if ( ! $content_found && isset( $block['attrs']['innerContent'] ) ) {
						if ( is_string( $block['attrs']['innerContent'] ) ) {
							$text = wp_strip_all_tags( $block['attrs']['innerContent'] );
							if ( ! empty( trim( $text ) ) ) {
								$text_content[] = $text;
								$content_found = true;
							}
						} elseif ( isset( $block['attrs']['innerContent']['desktop']['value'] ) ) {
							$text = wp_strip_all_tags( $block['attrs']['innerContent']['desktop']['value'] );
							if ( ! empty( trim( $text ) ) ) {
								$text_content[] = $text;
								$content_found = true;
							}
						}
					}
					
					// Recursively search all attrs for text content
					if ( ! $content_found ) {
						self::extract_text_from_array( $block['attrs'], $text_content );
					}
				}
				
				// For regular Gutenberg blocks, use innerHTML
				if ( ! empty( $block['innerHTML'] ) ) {
					$text = wp_strip_all_tags( $block['innerHTML'] );
					$text = trim( $text );
					if ( ! empty( $text ) ) {
						$text_content[] = $text;
					}
				}
				
				// Recursively process inner blocks
				if ( ! empty( $block['innerBlocks'] ) ) {
					$extract_text( $block['innerBlocks'] );
				}
			}
		};
		
		$extract_text( $blocks );
		
		// Combine all text with spaces
		$combined = implode( ' ', $text_content );
		
		// Remove excessive whitespace
		$combined = preg_replace( '/\s+/', ' ', $combined );
		
		return trim( $combined );
	}

	/**
	 * Recursively extract text from nested arrays (for Divi block attributes)
	 *
	 * @since ??
	 *
	 * @param array $data          Array to search.
	 * @param array &$text_content Reference to text content array.
	 *
	 * @return void
	 */
	private static function extract_text_from_array( $data, &$text_content ) {
		if ( ! is_array( $data ) ) {
			return;
		}
		
		foreach ( $data as $key => $value ) {
			if ( is_string( $value ) ) {
				// Check if this looks like content (not a setting)
				if ( strlen( $value ) > 10 && strip_tags( $value ) !== '' ) {
					// Skip if it's likely a URL, class name, or setting
					if ( strpos( $value, 'http' ) === 0 || 
						 strpos( $value, 'class' ) === 0 || 
						 strpos( $value, '#' ) === 0 ||
						 preg_match( '/^[\d\s\w-]+$/', $value ) ) {
						continue;
					}
					
					$text = wp_strip_all_tags( $value );
					$text = trim( $text );
					if ( ! empty( $text ) && strlen( $text ) > 10 ) {
						$text_content[] = $text;
					}
				}
			} elseif ( is_array( $value ) ) {
				// Recursively search nested arrays
				self::extract_text_from_array( $value, $text_content );
			}
		}
	}

	/**
	 * Initialize custom profile image functionality
	 * Call this in your module's init or constructor
	 *
	 * @since ??
	 */
	public static function init_custom_profile_image() {
		// Only run once
		static $initialized = false;
		if ( $initialized ) {
			return;
		}
		$initialized = true;
		
		// Enqueue media scripts on profile pages
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_profile_image_scripts' ] );
		
		// Add profile field
		add_action( 'show_user_profile', [ __CLASS__, 'add_custom_profile_image_field' ] );
		add_action( 'edit_user_profile', [ __CLASS__, 'add_custom_profile_image_field' ] );
		
		// Save profile field
		add_action( 'personal_options_update', [ __CLASS__, 'save_custom_profile_image' ] );
		add_action( 'edit_user_profile_update', [ __CLASS__, 'save_custom_profile_image' ] );
	}

	/**
	 * Enqueue media uploader scripts on user profile pages
	 *
	 * @since ??
	 *
	 * @param string $hook Current admin page hook.
	 */
	public static function enqueue_profile_image_scripts( $hook ) {
		// Only load on user profile pages
		if ( $hook !== 'profile.php' && $hook !== 'user-edit.php' ) {
			return;
		}
		
		// Enqueue WordPress media uploader
		wp_enqueue_media();
	}

	/**
	 * Add Custom Profile Image Field to User Profile
	 *
	 * @since ??
	 *
	 * @param \WP_User $user User object.
	 */
	public static function add_custom_profile_image_field( $user ) {
		$profile_image_id = get_user_meta( $user->ID, 'custom_profile_image', true );
		$profile_image_url = $profile_image_id ? wp_get_attachment_image_url( $profile_image_id, 'thumbnail' ) : '';
		?>
		<h3><?php _e( 'Custom Profile Image', 'divi' ); ?></h3>
		<table class="form-table">
			<tr>
				<th><label for="custom_profile_image"><?php _e( 'Profile Image', 'divi' ); ?></label></th>
				<td>
					<input type="hidden" id="custom_profile_image" name="custom_profile_image" value="<?php echo esc_attr( $profile_image_id ); ?>" />
					<div id="profile_image_preview" style="margin-bottom: 10px;">
						<?php if ( $profile_image_url ): ?>
							<img src="<?php echo esc_url( $profile_image_url ); ?>" style="max-width: 150px; height: auto; display: block;" />
						<?php endif; ?>
					</div>
					<button type="button" class="button" id="upload_profile_image_button">
						<?php echo $profile_image_url ? __( 'Change Image', 'divi' ) : __( 'Upload Image', 'divi' ); ?>
					</button>
					<?php if ( $profile_image_url ): ?>
						<button type="button" class="button" id="remove_profile_image_button"><?php _e( 'Remove Image', 'divi' ); ?></button>
					<?php endif; ?>
					<p class="description"><?php _e( 'Upload a custom profile image. If not set, your Gravatar will be used.', 'divi' ); ?></p>
				</td>
			</tr>
		</table>

		<script type="text/javascript">
		jQuery(document).ready(function($) {
			var mediaUploader;
			
			$('#upload_profile_image_button').on('click', function(e) {
				e.preventDefault();
				
				if (mediaUploader) {
					mediaUploader.open();
					return;
				}
				
				mediaUploader = wp.media({
					title: '<?php _e( 'Choose Profile Image', 'divi' ); ?>',
					button: {
						text: '<?php _e( 'Use this image', 'divi' ); ?>'
					},
					multiple: false
				});
				
				mediaUploader.on('select', function() {
					var attachment = mediaUploader.state().get('selection').first().toJSON();
					$('#custom_profile_image').val(attachment.id);
					$('#profile_image_preview').html('<img src="' + attachment.url + '" style="max-width: 150px; height: auto; display: block;" />');
					$('#upload_profile_image_button').text('<?php _e( 'Change Image', 'divi' ); ?>');
					
					if ($('#remove_profile_image_button').length === 0) {
						$('#upload_profile_image_button').after('<button type="button" class="button" id="remove_profile_image_button"><?php _e( 'Remove Image', 'divi' ); ?></button>');
					}
				});
				
				mediaUploader.open();
			});
			
			$(document).on('click', '#remove_profile_image_button', function(e) {
				e.preventDefault();
				$('#custom_profile_image').val('');
				$('#profile_image_preview').html('');
				$('#upload_profile_image_button').text('<?php _e( 'Upload Image', 'divi' ); ?>');
				$(this).remove();
			});
		});
		</script>
		<?php
	}

	/**
	 * Save Custom Profile Image
	 *
	 * @since ??
	 *
	 * @param int $user_id User ID.
	 */
	public static function save_custom_profile_image( $user_id ) {
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return false;
		}
		
		if ( isset( $_POST['custom_profile_image'] ) ) {
			update_user_meta( $user_id, 'custom_profile_image', intval( $_POST['custom_profile_image'] ) );
		}
	}

	/**
	 * Get Author Profile Image (Custom or Gravatar)
	 *
	 * @since ??
	 *
	 * @param int    $author_id Author user ID.
	 * @param string $size      Image size.
	 *
	 * @return string Image URL.
	 */
	private static function get_author_profile_image( $author_id, $size = 'thumbnail' ) {
		$custom_image_id = get_user_meta( $author_id, 'custom_profile_image', true );
		
		if ( $custom_image_id ) {
			$custom_image_url = wp_get_attachment_image_url( $custom_image_id, $size );
			if ( $custom_image_url ) {
				return $custom_image_url;
			}
		}
		
		// Fallback to Gravatar
		return get_avatar_url( $author_id, array( 'size' => 96 ) );
	}

	/**
	 * Dynamic module render callback which outputs server side rendered HTML on the Front-End.
	 *
	 * @since ??
	 *
	 * @param array          $attrs    Block attributes that were saved by VB.
	 * @param string         $content  Block content.
	 * @param \WP_Block      $block    Parsed block object that being rendered.
	 * @param ModuleElements $elements ModuleElements instance.
	 *
	 * @return string HTML rendered of Dynamic module.
	 */
	public static function render_callback( $attrs, $content, $block, $elements ) {

		// Initialize custom profile image functionality
		self::init_custom_profile_image();
		
		$post_heading_level  = $attrs['postTitle']['decoration']['font']['font']['desktop']['value']['headingLevel'];
		// Validate posts per page to prevent crashes
		$posts_per_page = $attrs['postItems']['innerContent']['desktop']['value']['postsNumber'] ?? '';
		if ( empty( $posts_per_page ) || ! is_numeric( $posts_per_page ) || $posts_per_page <= 0 ) {
			$posts_per_page = 6; // Default fallback
		}
		$posts_per_page = absint( $posts_per_page );
		$post_type           = $attrs['postType']['innerContent']['desktop']['value']['postType'] ?? 'post';
		$categories = $attrs['categories']['innerContent']['desktop']['value'] ?? '';
		$tags = $attrs['tags']['innerContent']['desktop']['value'] ?? '';
		
		// Show/Hide settings
		$show_featured_image = $attrs['showFeaturedImage']['innerContent']['desktop']['value'] ?? 'on';
		$meta_position       = $attrs['metaPosition']['innerContent']['desktop']['value'] ?? 'off';
		$layout_type         = $attrs['layoutType']['innerContent']['desktop']['value'] ?? 'off';
		$show_author         = $attrs['showAuthor']['innerContent']['desktop']['value'] ?? 'on';
		$show_date           = $attrs['showDate']['innerContent']['desktop']['value'] ?? 'on';
		$show_categories     = $attrs['showCategories']['innerContent']['desktop']['value'] ?? 'on';
		$show_tags           = $attrs['showTags']['innerContent']['desktop']['value'] ?? 'on';
		$image_position = $attrs['imagePosition']['innerContent']['desktop']['value'] ?? 'above';
		$image_position_first = $attrs['imagePositionFirst']['innerContent']['desktop']['value'] ?? $image_position;
		$image_position_first_tablet = $attrs['imagePositionFirst']['innerContent']['tablet']['value'] ?? $image_position_first;
		$image_position_first_phone = $attrs['imagePositionFirst']['innerContent']['phone']['value'] ?? $image_position_first_tablet;
		$show_pagination     = $attrs['showPagination']['innerContent']['desktop']['value'] ?? 'on';
		
		// Filter settings
		$show_filter    = ( $attrs['showFilter']['innerContent']['desktop']['value'] ?? 'off' ) === 'on';
		$filter_type    = $attrs['filterType']['innerContent']['desktop']['value'] ?? 'categories';
		$filter_position = $attrs['filterPosition']['innerContent']['desktop']['value'] ?? 'left';
		
		$alternate_image_position = ( $attrs['alternateImagePosition']['innerContent']['desktop']['value'] ?? 'off' ) === 'on';
		$first_post_full_width    = ( $attrs['firstPostFullWidth']['innerContent']['desktop']['value'] ?? 'off' ) === 'on';
		$first_post_show_image = ( $attrs['firstPostShowImage']['innerContent']['desktop']['value'] ?? 'on' ) === 'on';
		$post_offset              = absint( $attrs['postOffset']['innerContent']['desktop']['value']['postOffset'] ?? 0 );
		$sort_order               = $attrs['sortOrder']['innerContent']['desktop']['value']['sortOrder'] ?? 'desc';
		$excerpt_length = absint( $attrs['excerptLength']['innerContent']['desktop']['value']['excerptLength'] ?? 270 );

		$excerpt_length_first = absint( $attrs['excerptLengthFirst']['innerContent']['desktop']['value']['excerptLengthFirst'] ?? 270 );

		$show_read_more  = ( $attrs['showReadMore']['innerContent']['desktop']['value'] ?? 'on' ) === 'on';
		$read_more_style = $attrs['readMoreStyle']['innerContent']['desktop']['value'] ?? 'arrow';
		$read_more_text  = $attrs['readMoreText']['innerContent']['desktop']['value'] ?? 'Read More';

		// Get current page, filter, and meta filter from URL parameters
		$current_page     = max( 1, absint( $_GET['turbo_page'] ?? 1 ) );
		$selected_filter  = sanitize_text_field( $_GET['turbo_filter'] ?? 'all' );
		$meta_filter_id   = sanitize_text_field( $_GET['turbo_meta_filter'] ?? '' );
		$meta_filter_type = sanitize_text_field( $_GET['turbo_meta_type'] ?? '' );
		$meta_filter_name = sanitize_text_field( $_GET['turbo_meta_name'] ?? '' );

		// Clean and validate category/tag IDs
		$clean_categories = self::clean_and_validate_ids( $categories );
		$clean_tags = self::clean_and_validate_ids( $tags );

		
		$background_component = ElementComponents::component(
			[
				'attrs'         => $attrs['module']['decoration'] ?? [],
				'id'            => $block->parsed_block['id'],

				// FE only.
				'orderIndex'    => $block->parsed_block['orderIndex'],
				'storeInstance' => $block->parsed_block['storeInstance'],
			]
		);

		
		// Build query args for counting total posts
		$count_args = [
			'post_type'      => $post_type,
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'orderby'        => 'date',
		];

		// Handle meta filter (clicked from post meta)
		if ( ! empty( $meta_filter_id ) && ! empty( $meta_filter_type ) ) {
			if ( $meta_filter_type === 'category' ) {
				$count_args['category__in'] = [ absint( $meta_filter_id ) ];
			} elseif ( $meta_filter_type === 'tag' ) {
				$count_args['tag__in'] = [ absint( $meta_filter_id ) ];
			}
			
			// Still apply the original restrictions as additional filters
			if ( $meta_filter_type === 'tag' && ! empty( $clean_categories ) ) {
				$count_args['category__in'] = array_map( 'intval', explode( ',', $clean_categories ) );
			} elseif ( $meta_filter_type === 'category' && ! empty( $clean_tags ) ) {
				$count_args['tag__in'] = array_map( 'intval', explode( ',', $clean_tags ) );
			}
		} elseif ( $show_filter && $selected_filter !== 'all' ) {
			// A specific category filter is selected from nav
			$count_args['category__in'] = [ absint( $selected_filter ) ];
			// Still apply tag restrictions in background
			if ( ! empty( $clean_tags ) ) {
				$count_args['tag__in'] = array_map( 'intval', explode( ',', $clean_tags ) );
			}
		} else {
			// "View All" is selected - apply field category restrictions if any
			if ( ! empty( $clean_categories ) ) {
				$count_args['category__in'] = array_map( 'intval', explode( ',', $clean_categories ) );
			}
			if ( ! empty( $clean_tags ) ) {
				$count_args['tag__in'] = array_map( 'intval', explode( ',', $clean_tags ) );
			}
		}

		// Get total post count
		$total_posts = count( get_posts( $count_args ) );
		$total_pages = ceil( $total_posts / $posts_per_page );

		// Build query args for current page (same logic as count)
		$query_args = [
			'post_type'      => $post_type,
			'posts_per_page' => $posts_per_page,
			'offset'         => $post_offset + ( ( $current_page - 1 ) * $posts_per_page ),
			'order'          => strtoupper( $sort_order === 'asc' ? 'ASC' : 'DESC' ),
			'orderby'        => 'date',
		];

		// Apply same filter logic
		if ( ! empty( $meta_filter_id ) && ! empty( $meta_filter_type ) ) {
			if ( $meta_filter_type === 'category' ) {
				$query_args['category__in'] = [ absint( $meta_filter_id ) ];
			} elseif ( $meta_filter_type === 'tag' ) {
				$query_args['tag__in'] = [ absint( $meta_filter_id ) ];
			}
			
			if ( $meta_filter_type === 'tag' && ! empty( $clean_categories ) ) {
				$query_args['category__in'] = array_map( 'intval', explode( ',', $clean_categories ) );
			} elseif ( $meta_filter_type === 'category' && ! empty( $clean_tags ) ) {
				$query_args['tag__in'] = array_map( 'intval', explode( ',', $clean_tags ) );
			}
		} elseif ( $show_filter && $selected_filter !== 'all' ) {
			$query_args['category__in'] = [ absint( $selected_filter ) ];
			if ( ! empty( $clean_tags ) ) {
				$query_args['tag__in'] = array_map( 'intval', explode( ',', $clean_tags ) );
			}
		} else {
			if ( ! empty( $clean_categories ) ) {
				$query_args['category__in'] = array_map( 'intval', explode( ',', $clean_categories ) );
			}
			if ( ! empty( $clean_tags ) ) {
				$query_args['tag__in'] = array_map( 'intval', explode( ',', $clean_tags ) );
			}
		}

		// Generate grid CSS for frontend
		$order_class = '.turbo_blog_wl_' . $block->parsed_block['orderIndex'];
		self::enqueue_grid_styles( $attrs, $order_class );

		// Generate color CSS for frontend
		self::enqueue_color_styles( $attrs, $order_class );

		$posts      = get_posts( $query_args );
		$post_items = '';

		if ( is_array( $posts ) && count( $posts ) ) {
			$post_index = 0;
			
			foreach ( $posts as $post ) {
				setup_postdata( $post );
				
				// Determine if this is the first post and should be full width
				$is_first_post        = $post_index === 0;
				$should_be_full_width = $is_first_post && $first_post_full_width && $layout_type === 'on';
				
				// Determine image position for this post
				$current_image_position = $image_position;

				// Special handling for first post when full-width
				if ( $is_first_post && $should_be_full_width ) {
					$current_image_position = $image_position_first;
				} elseif ( $alternate_image_position && ( $image_position === 'left' || $image_position === 'right' ) ) {
					// Regular alternating logic for other posts
					if ( $post_index % 2 === 0 ) {
						$current_image_position = $image_position;
					} else {
						$current_image_position = $image_position === 'left' ? 'right' : 'left';
					}
				}

				// Determine if this post should show image
				$should_show_image = $is_first_post 
					? ( $should_be_full_width ? $first_post_show_image : $show_featured_image === 'on' )
					: $show_featured_image === 'on';
				
				$post_content_parts = [];
				
				// Featured Image - Top or Left
				if ( ( $current_image_position === 'above' || $current_image_position === 'left' ) && $should_show_image && has_post_thumbnail( $post->ID ) ) {

					$featured_image       = get_the_post_thumbnail( $post->ID, 'large', [ 'class' => 'turbo_blog_wl__post-featured-image-img' ] );
					$post_content_parts[] = HTMLUtility::render(
						[
							'tag'               => 'div',
							'attributes'        => [
								'class' => 'turbo_blog_wl__post-featured-image',
							],
							'childrenSanitizer' => 'et_core_esc_previously',
							'children'          => $featured_image,
						]
					);
				}

				// Start Content Wrapper
				$content_wrapper_parts = [];

				// Post title with link and optional arrow
				$title_class = 'turbo_blog_wl__post-item-title';
				if ( $show_read_more && $read_more_style === 'arrow' ) {
					$title_class .= ' turbo_blog_wl__post-item-title--with-arrow';
				}

				// Arrow SVG
				$arrow_html = '';
				if ( $show_read_more && $read_more_style === 'arrow' ) {
					$arrow_class = 'turbo_blog_wl__read-more-arrow turbo_blog_wl__read-more-arrow--' . $current_image_position;
					$arrow_html = '<span class="' . $arrow_class . '"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M7 17L17 7M17 7H7M17 7V17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>';
				}

				$post_title = HTMLUtility::render(
					[
						'tag'               => 'a',
						'attributes'        => [
							'href' => get_permalink( $post ),
						],
						'childrenSanitizer' => 'et_core_esc_previously',
						'children'          => get_the_title( $post ) . $arrow_html,
					]
				);

				// Post title container.
				$post_title_container = HTMLUtility::render(
					[
						'tag'               => $post_heading_level,
						'attributes'        => [
							'class' => $title_class,
						],
						'childrenSanitizer' => 'et_core_esc_previously',
						'children'          => $post_title,
					]
				);
				
				$content_wrapper_parts[] = $post_title_container;

				// Build post meta HTML based on position
				if ( $meta_position === 'off' ) {
					$post_meta_parts = [];
					
					if ( $show_author === 'on' ) {
						$author_name       = get_the_author_meta( 'display_name', $post->post_author );
						$post_meta_parts[] = HTMLUtility::render(
							[
								'tag'               => 'span',
								'attributes'        => [
									'class' => 'turbo_blog_wl__post-author',
								],
								'childrenSanitizer' => 'esc_html',
								'children'          => __( 'By', 'd5-extension-example-modules' ) . ' ' . $author_name,
							]
						);
					}
					
					if ( $show_date === 'on' ) {
						$post_meta_parts[] = HTMLUtility::render(
							[
								'tag'               => 'span',
								'attributes'        => [
									'class' => 'turbo_blog_wl__post-date',
								],
								'childrenSanitizer' => 'esc_html',
								'children'          => get_the_date( '', $post ),
							]
						);
					}
					
					if ( $show_categories === 'on' && $post_type === 'post' ) {
						// Get all categories for this post
						$post_categories = get_the_category( $post->ID );
						
						// Filter to only specified categories if any
						if ( ! empty( $clean_categories ) ) {
							$specified_cat_ids = array_map( 'intval', explode( ',', $clean_categories ) );
							$post_categories = array_filter( $post_categories, function( $cat ) use ( $specified_cat_ids ) {
								return in_array( $cat->term_id, $specified_cat_ids );
							});
						}
						
						if ( ! empty( $post_categories ) ) {
							$cat_links = [];
							$base_url = remove_query_arg( [ 'turbo_filter', 'turbo_page', 'turbo_meta_filter', 'turbo_meta_type', 'turbo_meta_name' ] );
							
							foreach ( $post_categories as $cat ) {
								$cat_url = add_query_arg( [
									'turbo_meta_filter' => $cat->term_id,
									'turbo_meta_type'   => 'category',
									'turbo_meta_name'   => urlencode( $cat->name ),
								], $base_url );
								
								$cat_links[] = '<a href="' . esc_url( $cat_url ) . '">' . esc_html( $cat->name ) . '</a>';
							}
							
							$post_meta_parts[] = HTMLUtility::render(
								[
									'tag'               => 'span',
									'attributes'        => [
										'class' => 'turbo_blog_wl__post-categories',
									],
									'childrenSanitizer' => 'et_core_esc_previously',
									'children'          => __( 'Categories:', 'd5-extension-example-modules' ) . ' ' . implode( ', ', $cat_links ),
								]
							);
						}
					}
					
					if ( $show_tags === 'on' && $post_type === 'post' ) {
						// Get all tags for this post - tags are always shown in full
						$post_tags = get_the_tags( $post->ID );
						
						if ( ! empty( $post_tags ) ) {
							$tag_links = [];
							$base_url = remove_query_arg( [ 'turbo_filter', 'turbo_page', 'turbo_meta_filter', 'turbo_meta_type', 'turbo_meta_name' ] );
							
							foreach ( $post_tags as $tag ) {
								$tag_url = add_query_arg( [
									'turbo_meta_filter' => $tag->term_id,
									'turbo_meta_type'   => 'tag',
									'turbo_meta_name'   => urlencode( $tag->name ),
								], $base_url );
								
								$tag_links[] = '<a href="' . esc_url( $tag_url ) . '">' . esc_html( $tag->name ) . '</a>';
							}
							
							$post_meta_parts[] = HTMLUtility::render(
								[
									'tag'               => 'span',
									'attributes'        => [
										'class' => 'turbo_blog_wl__post-tags',
									],
									'childrenSanitizer' => 'et_core_esc_previously',
									'children'          => __( 'Tags:', 'd5-extension-example-modules' ) . ' ' . implode( ', ', $tag_links ),
								]
							);
						}
					}
					
					if ( ! empty( $post_meta_parts ) ) {
						$content_wrapper_parts[] = HTMLUtility::render(
							[
								'tag'               => 'div',
								'attributes'        => [
									'class' => 'turbo_blog_wl__post-meta turbo_blog_wl__post-meta--above',
								],
								'childrenSanitizer' => 'et_core_esc_previously',
								'children'          => implode( ' ', $post_meta_parts ),
							]
						);
					}
				}

				// Determine which excerpt length to use
				$current_excerpt_length = ( $is_first_post && $should_be_full_width ) ? $excerpt_length_first : $excerpt_length;

				// Post content/excerpt
				$post_content = HTMLUtility::render(
					[
						'tag'               => 'div',
						'attributes'        => [
							'class' => 'turbo_blog_wl__post-item-content',
						],
						'childrenSanitizer' => 'et_core_esc_previously',
						'children'          => '<p>'.self::get_custom_excerpt( $post, $current_excerpt_length ).'</p>',
					]
				);
				$content_wrapper_parts[] = $post_content;

				// Read More Link
				if ( $show_read_more && $read_more_style === 'link' ) {
					$read_more_link = HTMLUtility::render(
						[
							'tag'               => 'a',
							'attributes'        => [
								'href'  => get_permalink( $post ),
								'class' => 'turbo_blog_wl__read-more-link',
							],
							'childrenSanitizer' => 'esc_html',
							'children'          => $read_more_text,
						]
					);
					$content_wrapper_parts[] = $read_more_link;
				}

				// Enhanced format for below content
				if ( $meta_position === 'on' ) {
					$below_meta_parts = [];
					
					$taxonomy_parts = [];
					
					if ( $show_categories === 'on' && $post_type === 'post' ) {
						$post_categories = get_the_category( $post->ID );
						
						// Filter to only specified categories if any
						if ( ! empty( $clean_categories ) ) {
							$specified_cat_ids = array_map( 'intval', explode( ',', $clean_categories ) );
							$post_categories = array_filter( $post_categories, function( $cat ) use ( $specified_cat_ids ) {
								return in_array( $cat->term_id, $specified_cat_ids );
							});
						}
						
						if ( ! empty( $post_categories ) ) {
							$cat_links = [];
							$base_url = remove_query_arg( [ 'turbo_filter', 'turbo_page', 'turbo_meta_filter', 'turbo_meta_type', 'turbo_meta_name' ] );
							
							foreach ( $post_categories as $cat ) {
								$cat_url = add_query_arg( [
									'turbo_meta_filter' => $cat->term_id,
									'turbo_meta_type'   => 'category',
									'turbo_meta_name'   => urlencode( $cat->name ),
								], $base_url );
								
								$cat_links[] = '<a href="' . esc_url( $cat_url ) . '">' . esc_html( $cat->name ) . '</a>';
							}
							
							$taxonomy_parts[] = implode( ', ', $cat_links );
						}
					}

					if ( $show_tags === 'on' && $post_type === 'post' ) {
						$post_tags = get_the_tags( $post->ID );
						
						// Tags are always shown in full (not filtered) since they work in background
						if ( ! empty( $post_tags ) ) {
							$tag_links = [];
							$base_url = remove_query_arg( [ 'turbo_filter', 'turbo_page', 'turbo_meta_filter', 'turbo_meta_type', 'turbo_meta_name' ] );
							
							foreach ( $post_tags as $tag ) {
								$tag_url = add_query_arg( [
									'turbo_meta_filter' => $tag->term_id,
									'turbo_meta_type'   => 'tag',
									'turbo_meta_name'   => urlencode( $tag->name ),
								], $base_url );
								
								$tag_links[] = '<a href="' . esc_url( $tag_url ) . '">' . esc_html( $tag->name ) . '</a>';
							}
							
							if ( ! empty( $taxonomy_parts ) ) {
								$taxonomy_parts[] = ' | ';
							}
							$taxonomy_parts[] = implode( ', ', $tag_links );
						}
					}
					
					if ( ! empty( $taxonomy_parts ) ) {
						$below_meta_parts[] = HTMLUtility::render(
							[
								'tag'               => 'div',
								'attributes'        => [
									'class' => 'turbo_blog_wl__post-taxonomy',
								],
								'childrenSanitizer' => 'et_core_esc_previously',
								'children'          => implode( '', $taxonomy_parts ),
							]
						);
					}
					
					if ( $show_author === 'on' || $show_date === 'on' ) {
						$author_info_parts = [];
						
						if ( $show_author === 'on' ) {
							$author_name   = get_the_author_meta( 'display_name', $post->post_author );
							
							// Get custom profile image or fallback to Gravatar (standalone method)
							$author_image_url = self::get_author_profile_image( $post->post_author, 'thumbnail' );
							$author_avatar = '<img src="' . esc_url( $author_image_url ) . '" alt="' . esc_attr( $author_name ) . '" class="turbo_blog_wl__post-author-avatar" width="96" height="96" />';
						
							
							$author_details_parts = [];
							
							$author_details_parts[] = HTMLUtility::render(
								[
									'tag'               => 'div',
									'attributes'        => [
										'class' => 'turbo_blog_wl__post-author-name',
									],
									'childrenSanitizer' => 'esc_html',
									'children'          => $author_name,
								]
							);
							
							if ( $show_date === 'on' ) {
								$author_details_parts[] = HTMLUtility::render(
									[
										'tag'               => 'div',
										'attributes'        => [
											'class' => 'turbo_blog_wl__post-date',
										],
									'childrenSanitizer' => 'esc_html',
										'children'          => get_the_date( 'F j, Y', $post ),
									]
								);
							}
							
							$author_details = HTMLUtility::render(
								[
									'tag'               => 'div',
									'attributes'        => [
										'class' => 'turbo_blog_wl__post-author-details',
									],
									'childrenSanitizer' => 'et_core_esc_previously',
									'children'          => implode( '', $author_details_parts ),
								]
							);
							
							$author_info_parts[] = $author_avatar . $author_details;
						} elseif ( $show_date === 'on' ) {
							$author_info_parts[] = HTMLUtility::render(
								[
									'tag'               => 'div',
									'attributes'        => [
										'class' => 'turbo_blog_wl__post-date',
									],
									'childrenSanitizer' => 'esc_html',
									'children'          => get_the_date( 'F j, Y', $post ),
								]
							);
						}
						
						if ( ! empty( $author_info_parts ) ) {
							$below_meta_parts[] = HTMLUtility::render(
								[
									'tag'               => 'div',
									'attributes'        => [
										'class' => 'turbo_blog_wl__post-author-info',
									],
									'childrenSanitizer' => 'et_core_esc_previously',
									'children'          => implode( '', $author_info_parts ),
								]
							);
						}
					}
					
					if ( ! empty( $below_meta_parts ) ) {
						$content_wrapper_parts[] = HTMLUtility::render(
							[
								'tag'               => 'div',
								'attributes'        => [
									'class' => 'turbo_blog_wl__post-meta turbo_blog_wl__post-meta--below',
								],
								'childrenSanitizer' => 'et_core_esc_previously',
								'children'          => implode( '', $below_meta_parts ),
							]
						);
					}
				}

				// Wrap all content in content wrapper
				$post_content_parts[] = HTMLUtility::render(
					[
						'tag'               => 'div',
						'attributes'        => [
							'class' => 'turbo_blog_wl__post-content-wrapper',
						],
						'childrenSanitizer' => 'et_core_esc_previously',
						'children'          => implode( '', $content_wrapper_parts ),
					]
				);

				// Featured Image - Bottom or Right
				if ( ( $current_image_position === 'below' || $current_image_position === 'right' ) && $should_show_image && has_post_thumbnail( $post->ID ) ) {
					$featured_image       = get_the_post_thumbnail( $post->ID, 'large', [ 'class' => 'turbo_blog_wl__post-featured-image-img' ] );
					$post_content_parts[] = HTMLUtility::render(
						[
							'tag'               => 'div',
							'attributes'        => [
								'class' => 'turbo_blog_wl__post-featured-image',
							],
							'childrenSanitizer' => 'et_core_esc_previously',
							'children'          => $featured_image,
						]
					);
				}

				// Build data attributes for responsive image positioning
				$data_attributes = [];
				if ( $is_first_post && $should_be_full_width ) {
					$data_attributes['data-tablet-position'] = $image_position_first_tablet;
					$data_attributes['data-phone-position'] = $image_position_first_phone;
				}

				// Post inner wrapper
				$post_inner = HTMLUtility::render(
					[
						'tag'               => 'div',
						'attributes'        => array_merge(
							[
								'class' => 'turbo_blog_wl__post-inner turbo_blog_wl__post-inner--' . $current_image_position,
							],
							$data_attributes
						),
						'childrenSanitizer' => 'et_core_esc_previously',
						'children'          => implode( '', $post_content_parts ),
					]
				);

				// Post item with conditional full-width class
				$post_item_class = 'turbo_blog_wl__post-item';
				if ( $should_be_full_width ) {
					$post_item_class .= ' turbo_blog_wl__post-item--full-width';
				}

				$post_items .= HTMLUtility::render(
					[
						'tag'               => 'div',
						'attributes'        => [
							'class' => $post_item_class,
						],
						'childrenSanitizer' => 'et_core_esc_previously',
						'children'          => $post_inner,
					]
				);
				
				wp_reset_postdata();
				$post_index++;
			}
		}

		// Build filter HTML
		$filter_html = '';
		if ( $show_filter && $post_type === 'post' ) {
			// Get current URL without filter parameters
			$base_url = remove_query_arg( [ 'turbo_filter', 'turbo_page', 'turbo_meta_filter', 'turbo_meta_type', 'turbo_meta_name' ] );
			
			$terms = [];
			
			// If categories are specified in field, use only those
			if ( ! empty( $clean_categories ) ) {
				$category_ids = array_map( 'intval', explode( ',', $clean_categories ) );
				
				$terms = get_terms([
					'taxonomy'   => 'category',
					'include'    => $category_ids,
					'hide_empty' => false, // Show even if no posts match (with tag filter)
					'orderby'    => 'name',
					'order'      => 'ASC',
				]);
			} else {
				// No categories specified - get all categories from posts matching tag filter
				$filter_post_args = [
					'post_type'      => $post_type,
					'posts_per_page' => -1,
					'fields'         => 'ids',
				];
				
				// Apply tag filter if specified
				if ( ! empty( $clean_tags ) ) {
					$filter_post_args['tag__in'] = array_map( 'intval', explode( ',', $clean_tags ) );
				}
				
				// Get post IDs that match the tag filter
				$filtered_post_ids = get_posts( $filter_post_args );
				
				// Get categories from those posts
				$category_ids = [];
				if ( ! empty( $filtered_post_ids ) ) {
					foreach ( $filtered_post_ids as $post_id ) {
						$post_categories = wp_get_post_categories( $post_id );
						$category_ids = array_merge( $category_ids, $post_categories );
					}
					$category_ids = array_unique( $category_ids );
				}
				
				// Get the actual category objects
				if ( ! empty( $category_ids ) ) {
					$terms = get_terms([
						'taxonomy'   => 'category',
						'include'    => $category_ids,
						'hide_empty' => true,
						'orderby'    => 'name',
						'order'      => 'ASC',
					]);
				}
			}
			
			if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
				$filter_items = [];
				
				// View All button
				$view_all_class = 'turbo_blog_wl__filter-item';
				if ( $selected_filter === 'all' && empty( $meta_filter_id ) ) {
					$view_all_class .= ' turbo_blog_wl__filter-item--active';
				}
				
				$filter_items[] = HTMLUtility::render(
					[
						'tag'               => 'a',
						'attributes'        => [
							'href'  => esc_url( $base_url ),
							'class' => $view_all_class,
						],
						'childrenSanitizer' => 'esc_html',
						'children'          => __( 'View All', 'd5-extension-example-modules' ),
					]
				);
				
				// Term buttons
				foreach ( $terms as $term ) {
					$term_class = 'turbo_blog_wl__filter-item';
					if ( $selected_filter === (string) $term->term_id && empty( $meta_filter_id ) ) {
						$term_class .= ' turbo_blog_wl__filter-item--active';
					}
					
					$term_url     = add_query_arg( 'turbo_filter', $term->term_id, $base_url );
					$filter_items[] = HTMLUtility::render(
						[
							'tag'               => 'a',
							'attributes'        => [
								'href'  => esc_url( $term_url ),
								'class' => $term_class,
							],
							'childrenSanitizer' => 'esc_html',
							'children'          => $term->name,
						]
					);
				}
				
				$filter_inner = HTMLUtility::render(
					[
						'tag'               => 'div',
						'attributes'        => [
							'class' => 'turbo_blog_wl__filter-inner',
						],
						'childrenSanitizer' => 'et_core_esc_previously',
						'children'          => implode( '', $filter_items ),
					]
				);
				
				$filter_html = HTMLUtility::render(
					[
						'tag'               => 'nav',
						'attributes'        => [
							'class'      => 'turbo_blog_wl__filter turbo_blog_wl__filter--' . $filter_position,
							'role'       => 'navigation',
							'aria-label' => __( 'Filter posts by category', 'd5-extension-example-modules' ),
						],
						'childrenSanitizer' => 'et_core_esc_previously',
						'children'          => $filter_inner,
					]
				);
			}
		}

		// Build pagination HTML with ellipsis
		$pagination_html = '';
		if ( $show_pagination === 'on' && $total_pages > 1 ) {
			$pagination_items = [];
			
			// Get current URL - preserve filter parameters
			$current_url = remove_query_arg( 'turbo_page' );
			
			// Previous button
			if ( $current_page > 1 ) {
				$prev_url           = add_query_arg( 'turbo_page', $current_page - 1, $current_url );
				$pagination_items[] = HTMLUtility::render(
					[
						'tag'               => 'a',
						'attributes'        => [
							'href'  => esc_url( $prev_url ),
							'class' => 'turbo_blog_wl__pagination-prev',
						],
						'childrenSanitizer' => 'esc_html',
						'children'          => '← PREVIOUS',
					]
				);
			} else {
				$pagination_items[] = HTMLUtility::render(
					[
						'tag'               => 'span',
						'attributes'        => [
							'class' => 'turbo_blog_wl__pagination-prev turbo_blog_wl__pagination-disabled',
						],
						'childrenSanitizer' => 'esc_html',
						'children'          => '← PREVIOUS',
					]
				);
			}
			
			// Page numbers with ellipsis
			$page_numbers     = [];
			$pagination_range = self::get_pagination_range( $current_page, $total_pages );
			
			foreach ( $pagination_range as $page ) {
				if ( $page === '...' ) {
					$page_numbers[] = HTMLUtility::render(
						[
							'tag'               => 'span',
							'attributes'        => [
								'class' => 'turbo_blog_wl__pagination-ellipsis',
							],
							'childrenSanitizer' => 'esc_html',
							'children'          => '...',
						]
					);
				} elseif ( $page === $current_page ) {
					$page_numbers[] = HTMLUtility::render(
						[
							'tag'               => 'span',
							'attributes'        => [
								'class' => 'turbo_blog_wl__pagination-number turbo_blog_wl__pagination-current',
							],
							'childrenSanitizer' => 'esc_html',
							'children'          => (string) $page,
						]
					);
				} else {
					$page_url       = add_query_arg( 'turbo_page', $page, $current_url );
					$page_numbers[] = HTMLUtility::render(
						[
							'tag'               => 'a',
							'attributes'        => [
								'href'  => esc_url( $page_url ),
								'class' => 'turbo_blog_wl__pagination-number',
							],
							'childrenSanitizer' => 'esc_html',
							'children'          => (string) $page,
						]
					);
				}
			}
			
			$pagination_items[] = HTMLUtility::render(
				[
					'tag'               => 'div',
					'attributes'        => [
						'class' => 'turbo_blog_wl__pagination-numbers',
					],
					'childrenSanitizer' => 'et_core_esc_previously',
					'children'          => implode( '', $page_numbers ),
				]
			);
			
			// Next button
			if ( $current_page < $total_pages ) {
				$next_url           = add_query_arg( 'turbo_page', $current_page + 1, $current_url );
				$pagination_items[] = HTMLUtility::render(
					[
						'tag'               => 'a',
						'attributes'        => [
							'href'  => esc_url( $next_url ),
							'class' => 'turbo_blog_wl__pagination-next',
						],
						'childrenSanitizer' => 'esc_html',
						'children'          => 'NEXT →',
					]
				);
			} else {
				$pagination_items[] = HTMLUtility::render(
					[
						'tag'               => 'span',
						'attributes'        => [
							'class' => 'turbo_blog_wl__pagination-next turbo_blog_wl__pagination-disabled',
						],
						'childrenSanitizer' => 'esc_html',
						'children'          => 'NEXT →',
					]
				);
			}
			
			$pagination_html = HTMLUtility::render(
				[
					'tag'               => 'nav',
					'attributes'        => [
						'class'      => 'turbo_blog_wl__pagination',
						'role'       => 'navigation',
						'aria-label' => __( 'Pagination', 'd5-extension-example-modules' ),
					],
					'childrenSanitizer' => 'et_core_esc_previously',
					'children'          => implode( '', $pagination_items ),
				]
			);
		}

		// Meta filter indicator banner
		$meta_filter_banner = '';
		if ( ! empty( $meta_filter_id ) && ! empty( $meta_filter_type ) && ! empty( $meta_filter_name ) ) {
			$clear_url = remove_query_arg( [ 'turbo_meta_filter', 'turbo_meta_type', 'turbo_meta_name', 'turbo_page' ] );
			
			$filter_type_text = $meta_filter_type === 'category' 
				? __( 'category', 'd5-extension-example-modules' )
				: __( 'tag', 'd5-extension-example-modules' );
			
			$meta_filter_banner = HTMLUtility::render(
				[
					'tag'               => 'div',
					'attributes'        => [
						'style' => 'padding: 10px 15px; background: #f0f0f0; border: 1px solid #ddd; border-radius: 4px; margin: 10px 0; display: flex; justify-content: space-between; align-items: center;',
					],
					'childrenSanitizer' => 'et_core_esc_previously',
					'children'          => [
						HTMLUtility::render([
							'tag'               => 'span',
							'childrenSanitizer' => 'et_core_esc_previously',
							'children'          => sprintf(
								'%s %s: <strong>%s</strong>',
								__( 'Filtering by', 'd5-extension-example-modules' ),
								$filter_type_text,
								esc_html( urldecode( $meta_filter_name ) )
							),
						]),
						HTMLUtility::render([
							'tag'               => 'a',
							'attributes'        => [
								'href'  => esc_url( $clear_url ),
								'style' => 'background: #333; color: #fff; border: none; padding: 5px 10px; border-radius: 3px; text-decoration: none;',
							],
							'childrenSanitizer' => 'esc_html',
							'children'          => __( 'Clear Filter', 'd5-extension-example-modules' ),
						]),
					],
				]
			);
		}

		// Title
		$title = $elements->render(
			[
				'attrName' => 'title',
			]
		);

		// Posts container
		if ( ! empty( $post_items ) ) {
			$container_class = $layout_type === 'on' 
				? 'turbo_blog_wl__post-items turbo_blog_wl__post-items--grid' 
				: 'turbo_blog_wl__post-items turbo_blog_wl__post-items--fullwidth';
			
			$posts_container = HTMLUtility::render(
				[
					'tag'               => 'div',
					'attributes'        => [
						'class' => $container_class,
					],
					'childrenSanitizer' => 'et_core_esc_previously',
					'children'          => $post_items,
				]
			);
		} else {
			// No posts message with optional clear button
			$no_posts_content = __( 'No posts found.', 'd5-extension-example-modules' );
			
			if ( ! empty( $meta_filter_id ) ) {
				$clear_url = remove_query_arg( [ 'turbo_meta_filter', 'turbo_meta_type', 'turbo_meta_name', 'turbo_page' ] );
				
				$no_posts_content .= '<div style="margin-top: 10px;">' . 
					HTMLUtility::render([
						'tag'               => 'a',
						'attributes'        => [
							'href'  => esc_url( $clear_url ),
							'style' => 'background: #0073aa; color: #fff; border: none; padding: 8px 16px; border-radius: 3px; cursor: pointer; font-size: 14px; text-decoration: none; display: inline-block;',
						],
						'childrenSanitizer' => 'esc_html',
						'children'          => __( 'View All Posts', 'd5-extension-example-modules' ),
					]) .
				'</div>';
			}
			
			$posts_container = HTMLUtility::render(
				[
					'tag'               => 'div',
					'attributes'        => [
						'style' => 'padding: 20px; text-align: center;',
					],
					'childrenSanitizer' => 'et_core_esc_previously',
					'children'          => $no_posts_content,
				]
			);
		}

		// Posts wrapper with pagination
		$posts_wrapper = HTMLUtility::render(
			[
				'tag'               => 'section',
				'attributes'        => [
					'class' => 'turbo_blog_wl__posts-wrapper',
				],
				'childrenSanitizer' => 'et_core_esc_previously',
				'children'          => [
					$posts_container,
					$pagination_html,
				],
			]
		);

		// Content wrapper with filter
		$content_wrapper_class = 'turbo_blog_wl__content-wrapper';
		if ( $show_filter && ! empty( $filter_html ) ) {
			$content_wrapper_class .= ' turbo_blog_wl__content-wrapper--with-filter turbo_blog_wl__content-wrapper--filter-' . $filter_position;
		}

		// Always put filter first, then posts - CSS will handle visual order
		$content_wrapper_children = [];
		if ( $show_filter && ! empty( $filter_html ) ) {
			$content_wrapper_children[] = $filter_html;
		}
		$content_wrapper_children[] = $posts_wrapper;

		$content_wrapper = HTMLUtility::render(
			[
				'tag'               => 'div',
				'attributes'        => [
					'class' => $content_wrapper_class,
				],
				'childrenSanitizer' => 'et_core_esc_previously',
				'children'          => $content_wrapper_children,
			]
		);

		$parent       = BlockParserStore::get_parent( $block->parsed_block['id'], $block->parsed_block['storeInstance'] );
		$parent_attrs = $parent->attrs ?? [];

		return Module::render(
			[
				// FE only.
				'orderIndex'          => $block->parsed_block['orderIndex'],
				'storeInstance'       => $block->parsed_block['storeInstance'],

				// VB equivalent.
				'id'                  => $block->parsed_block['id'],
				'name'                => $block->block_type->name,
				'moduleCategory'      => $block->block_type->category,
				'attrs'               => $attrs,
				'elements'            => $elements,
				'classnamesFunction'  => [ self::class, 'module_classnames' ],
				'stylesComponent'     => [ self::class, 'module_styles' ],
				'scriptDataComponent' => [ self::class, 'module_script_data' ],
				'parentAttrs'         => $parent_attrs,
				'parentId'            => $parent->id ?? '',
				'parentName'          => $parent->blockName ?? '',
				'children'            => [
					$background_component,
					HTMLUtility::render(
						[
							'tag'               => 'div',
							'attributes'        => [
								'class' => 'turbo_blog_wl__inner',
							],
							'childrenSanitizer' => 'et_core_esc_previously',
							'children'          => [
								$title,
								$meta_filter_banner,
								$content_wrapper,
							],
						]
					),
				],
			]
		);
	}
}