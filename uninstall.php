<?php
/**
 * WPA9 Gallery — uninstall
 *
 * Drops custom tables, removes options. Files in wp-content/uploads/wpa9-galleries/
 * are NOT removed — that's a deliberate safety net; delete by hand if desired.
 */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

$tables = array(
    $wpdb->prefix . 'wpa9_images',
    $wpdb->prefix . 'wpa9_album_galleries',
    $wpdb->prefix . 'wpa9_galleries',
    $wpdb->prefix . 'wpa9_albums',
);

foreach ( $tables as $t ) {
    $wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $t ) );
}

delete_option( 'wpa9_db_version' );
delete_option( 'wpa9_settings' );
