<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WPA9_Exporter {

    public static function export_all() {
        global $wpdb;

        $export = array(
            'version'          => '1.0',
            'exported_at'      => current_time( 'mysql' ),
            'galleries'        => array(),
            'albums'           => array(),
            'album_galleries'  => array(),
            'images'           => array(),
        );

        $galleries = $wpdb->get_results(
            $wpdb->prepare( 'SELECT * FROM %i ORDER BY id', $wpdb->prefix . 'wpa9_galleries' )
        );
        foreach ( $galleries as $gallery ) {
            $export['galleries'][] = (array) $gallery;
        }

        $albums = $wpdb->get_results(
            $wpdb->prepare( 'SELECT * FROM %i ORDER BY id', $wpdb->prefix . 'wpa9_albums' )
        );
        foreach ( $albums as $album ) {
            $export['albums'][] = (array) $album;
        }

        $album_galleries = $wpdb->get_results(
            $wpdb->prepare( 'SELECT * FROM %i ORDER BY album_id, sort_order', $wpdb->prefix . 'wpa9_album_galleries' )
        );
        foreach ( $album_galleries as $ag ) {
            $export['album_galleries'][] = (array) $ag;
        }

        $images = $wpdb->get_results(
            $wpdb->prepare( 'SELECT * FROM %i ORDER BY gallery_id, sort_order', $wpdb->prefix . 'wpa9_images' )
        );
        foreach ( $images as $image ) {
            $export['images'][] = (array) $image;
        }

        return $export;
    }

    public static function to_json( $export ) {
        return wp_json_encode( $export, JSON_PRETTY_PRINT );
    }
}
