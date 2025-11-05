<?php
namespace MEE\Modules\TurboBlogWl\TurboBlogWlTrait;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

trait StylesHookTrait {

	private static function getResponsiveValue( $attrs, $attrName, $device, $key = null, $fallback = '' ) {
    // Safely extract the value from Divi's responsive structure
		$value = $attrs[$attrName]['innerContent'][$device]['value']
			?? $attrs[$attrName]['innerContent'][$device]
			?? $fallback;

		// If a sub-key is specified (like 'rowGap' or 'columnGap'), get that
		if ( is_array( $value ) && $key && isset( $value[$key] ) ) {
			$value = $value[$key];
		}

		// Handle arrays like ['1fr', '1fr', '1fr']
		if ( is_array( $value ) ) {
			return implode( ' ', array_map( 'trim', $value ) );
		}

		// Return clean string fallback
		return trim( (string) $value );
	}


	public static function enqueue_grid_styles( $attrs, $order_class ) {

		function implodeThis( $thisArray ) {
			if ( is_array( $thisArray ) ) {
				return implode( ' ', array_map( 'trim', $thisArray ) );
			}
			return trim( (string) $thisArray );
		}
		


		// Normalize order class (strip leading dot if any)
		$order_class = ltrim( (string) $order_class, '.' );

		// Bail early if empty
		if ( empty( $order_class ) || empty( $attrs ) ) {
			return;
		}

		// Layout flag
		$layout_type = $attrs['layoutType']['innerContent']['desktop']['value'] ?? 'off';
		if ( 'on' !== $layout_type ) {
			return;
		}

		// Gather grid values
		$gcd = self::getResponsiveValue( $attrs, 'gridColumns', 'desktop', 'gridColumns', '1fr 1fr 1fr' );
		$gct = self::getResponsiveValue( $attrs, 'gridColumns', 'tablet', 'gridColumns' );
		$gcp = self::getResponsiveValue( $attrs, 'gridColumns', 'phone', 'gridColumns' );

		$grgd = self::getResponsiveValue( $attrs, 'gridRowGap', 'desktop', 'rowGap', '30px' );
		$gcgd = self::getResponsiveValue( $attrs, 'gridColumnGap', 'desktop', 'columnGap', '30px' );

		$grgt = self::getResponsiveValue( $attrs, 'gridRowGap', 'tablet', 'rowGap' );
		$gcgt = self::getResponsiveValue( $attrs, 'gridColumnGap', 'tablet', 'columnGap' );

		$grgp = self::getResponsiveValue( $attrs, 'gridRowGap', 'phone', 'rowGap' );
		$gcgp = self::getResponsiveValue( $attrs, 'gridColumnGap', 'phone', 'columnGap' );


		

		// Build selector safely
		$selector = '.' . sanitize_html_class( $order_class ) . ' .turbo_blog_wl__post-items--grid';
		$wrapper  = '.' . sanitize_html_class( $order_class );

		// Build CSS string
		$css  = $selector . "/*hello css world*/ {\n";
		$css .= "  display: grid;\n";
		$css .= "  grid-template-columns: " . $gcd . ";\n";
		$css .= "  row-gap: " . $grgd . ";\n";
		$css .= "  column-gap: " . $gcgd . ";\n";
		$css .= "}\n\n";

		if ( $gct || $grgt || $gcgt ) {
			$css .= "@media only screen and (max-width: 980px) {\n";
			$css .= "  " . $selector . " {\n";
			if ( $gct )  { $css .= "    grid-template-columns: " . $gct . ";\n"; }
			if ( $grgt ) { $css .= "    row-gap: " . $grgt . ";\n"; }
			if ( $gcgt ) { $css .= "    column-gap: " . $gcgt . ";\n"; }
			$css .= "  }\n";
			$css .= "}\n\n";
		}

		if ( $gcp || $grgp || $gcgp ) {
			$css .= "@media only screen and (max-width: 767px) {\n";
			$css .= "  " . $selector . " {\n";
			if ( $gcp )  { $css .= "    grid-template-columns: " . $gcp . ";\n"; }
			if ( $grgp ) { $css .= "    row-gap: " . $grgp . ";\n"; }
			if ( $gcgp ) { $css .= "    column-gap: " . $gcgp . ";\n"; }
			$css .= "  }\n";
			$css .= "}\n\n";
		}


		// Optional: basic box sizing for consistency
		$css .= $wrapper . " .turbo_blog_wl__post-item {\n";
		$css .= "  box-sizing: border-box;\n";
		$css .= "}\n";

		// Register + enqueue inline style
		$handle = 'turbo-blog-wl-inline-' . md5( $selector );
		wp_register_style( $handle, false );
		wp_enqueue_style( $handle );
		wp_add_inline_style( $handle, $css );
	}
}