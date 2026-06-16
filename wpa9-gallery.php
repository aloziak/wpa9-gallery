<?php
/**
 * Plugin Name: WPA9 Gallery
 * Plugin URI:  https://apollo1.cz/
 * Description: Lightweight gallery & album manager. Stores files outside the Media Library at wp-content/uploads/wpa9-galleries/. Ships with a theme-overridable masonry template.
 * Version:     1.3.0
 * Author:      Aleš Loziak
 * License:     GPL-2.0-or-later
 * Text Domain: wpa9-gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'WPA9_VERSION', '1.0.0' );
define( 'WPA9_FILE', __FILE__ );
define( 'WPA9_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPA9_URL', plugin_dir_url( __FILE__ ) );
define( 'WPA9_BASENAME', plugin_basename( __FILE__ ) );
define( 'WPA9_NAME', 'wpa9-gallery' );

require_once WPA9_DIR . 'includes/class-wpa9-install.php';
require_once WPA9_DIR . 'includes/class-wpa9-storage.php';
require_once WPA9_DIR . 'includes/class-wpa9-image-processor.php';
require_once WPA9_DIR . 'includes/class-wpa9-gallery.php';
require_once WPA9_DIR . 'includes/class-wpa9-album.php';
require_once WPA9_DIR . 'includes/class-wpa9-image.php';
require_once WPA9_DIR . 'includes/class-wpa9-template-loader.php';
require_once WPA9_DIR . 'includes/class-wpa9-shortcode.php';
require_once WPA9_DIR . 'includes/class-wpa9-ajax.php';

if ( is_admin() ) {
    require_once WPA9_DIR . 'includes/class-wpa9-importer-nextgen.php';
    require_once WPA9_DIR . 'includes/class-wpa9-exporter.php';
    require_once WPA9_DIR . 'includes/class-wpa9-importer.php';
    require_once WPA9_DIR . 'includes/class-wpa9-admin.php';
}

register_activation_hook( __FILE__, array( 'WPA9_Install', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'WPA9_Install', 'deactivate' ) );

add_action( 'plugins_loaded', 'wpa9_init' );
function wpa9_init() {
    load_plugin_textdomain( 'wpa9-gallery', false, dirname( WPA9_BASENAME ) . '/languages' );

    WPA9_Install::maybe_upgrade();
    new WPA9_Shortcode();
    new WPA9_Ajax();

    if ( is_admin() ) {
        new WPA9_Admin();
    }
}
