<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Loads templates from (in order):
 *   1. {child theme}/wpa9-gallery/{name}.php
 *   2. {parent theme}/wpa9-gallery/{name}.php
 *   3. plugin's own templates/{name}.php
 */
class WPA9_Template_Loader {

    const SUBDIR = 'wpa9-gallery';

    public static function locate( $name ) {
        $name = preg_replace( '/[^a-zA-Z0-9_\-]/', '', $name );
        if ( $name === '' ) {
            $name = 'gallery-masonry';
        }
        $file = $name . '.php';

        $located = locate_template( array( self::SUBDIR . '/' . $file ) );
        if ( $located ) {
            return $located;
        }

        $fallback = WPA9_DIR . 'templates/' . $file;
        if ( file_exists( $fallback ) ) {
            return $fallback;
        }

        return '';
    }

    public static function render( $name, array $context ) {
        $path = self::locate( $name );
        if ( ! $path ) {
            return '';
        }

        // Expose $gallery, $images, $album, $atts to the template.
        ob_start();
        extract( $context, EXTR_SKIP );
        include $path;
        return ob_get_clean();
    }
}
