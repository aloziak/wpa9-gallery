<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WPA9_Importer {

    public static function validate_import_file( $json_string ) {
        $data = json_decode( $json_string, true );

        if ( is_null( $data ) ) {
            return new WP_Error( 'invalid_json', __( 'Invalid JSON format.', 'wpa9-gallery' ) );
        }

        if ( ! isset( $data['version'] ) ) {
            return new WP_Error( 'missing_version', __( 'Export file missing version information.', 'wpa9-gallery' ) );
        }

        if ( ! isset( $data['galleries'], $data['albums'], $data['album_galleries'], $data['images'] ) ) {
            return new WP_Error( 'invalid_structure', __( 'Export file has invalid structure.', 'wpa9-gallery' ) );
        }

        return true;
    }

    public static function preview( $json_string ) {
        $data = json_decode( $json_string, true );

        if ( ! is_array( $data ) ) {
            return null;
        }

        return array(
            'galleries' => count( $data['galleries'] ?? array() ),
            'albums'    => count( $data['albums'] ?? array() ),
            'images'    => count( $data['images'] ?? array() ),
        );
    }

    public static function import( $json_string ) {
        $data = json_decode( $json_string, true );

        if ( ! is_array( $data ) ) {
            return new WP_Error( 'decode_failed', __( 'Failed to decode import file.', 'wpa9-gallery' ) );
        }

        set_time_limit( 300 );
        wp_raise_memory_limit( 'import' );

        $result = array(
            'galleries' => 0,
            'albums'    => 0,
            'images'    => 0,
            'warnings'  => array(),
        );

        $gallery_id_map = array();

        foreach ( $data['galleries'] ?? array() as $gallery ) {
            $old_id = $gallery['id'];
            unset( $gallery['id'] );
            unset( $gallery['created_at'] );

            $new_id = WPA9_Gallery::insert( $gallery );
            if ( is_wp_error( $new_id ) ) {
                $result['warnings'][] = sprintf(
                    __( 'Gallery "%s" failed to import: %s', 'wpa9-gallery' ),
                    $gallery['name'] ?? 'Unknown',
                    $new_id->get_error_message()
                );
                continue;
            }

            $gallery_id_map[ $old_id ] = $new_id;
            $result['galleries']++;
        }

        $album_id_map = array();

        foreach ( $data['albums'] ?? array() as $album ) {
            $old_id = $album['id'];
            unset( $album['id'] );
            unset( $album['created_at'] );

            $new_id = WPA9_Album::insert( $album );
            if ( is_wp_error( $new_id ) ) {
                $result['warnings'][] = sprintf(
                    __( 'Album "%s" failed to import: %s', 'wpa9-gallery' ),
                    $album['name'] ?? 'Unknown',
                    $new_id->get_error_message()
                );
                continue;
            }

            $album_id_map[ $old_id ] = $new_id;
            $result['albums']++;
        }

        foreach ( $data['images'] ?? array() as $image ) {
            $old_gallery_id = $image['gallery_id'];
            unset( $image['id'] );
            unset( $image['created_at'] );

            if ( ! isset( $gallery_id_map[ $old_gallery_id ] ) ) {
                $result['warnings'][] = sprintf(
                    __( 'Image "%s" skipped: gallery not found in import.', 'wpa9-gallery' ),
                    $image['filename'] ?? 'Unknown'
                );
                continue;
            }

            $image['gallery_id'] = $gallery_id_map[ $old_gallery_id ];

            $image_id = WPA9_Image::insert( $image );
            if ( is_wp_error( $image_id ) ) {
                $result['warnings'][] = sprintf(
                    __( 'Image "%s" failed to import: %s', 'wpa9-gallery' ),
                    $image['filename'] ?? 'Unknown',
                    $image_id->get_error_message()
                );
                continue;
            }

            $result['images']++;
        }

        foreach ( $data['album_galleries'] ?? array() as $ag ) {
            $old_album_id = $ag['album_id'];
            $old_gallery_id = $ag['gallery_id'];

            if ( ! isset( $album_id_map[ $old_album_id ] ) || ! isset( $gallery_id_map[ $old_gallery_id ] ) ) {
                continue;
            }

            $new_album_id = $album_id_map[ $old_album_id ];
            $new_gallery_id = $gallery_id_map[ $old_gallery_id ];

            WPA9_Album::add_gallery( $new_album_id, $new_gallery_id, $ag['sort_order'] ?? 0 );
        }

        return $result;
    }
}
