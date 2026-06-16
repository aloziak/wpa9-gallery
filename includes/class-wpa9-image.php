<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WPA9_Image {

    public static function table() {
        global $wpdb;
        return $wpdb->prefix . 'wpa9_images';
    }

    public static function get( $id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . self::table() . " WHERE id = %d", (int) $id ) );
    }

    public static function for_gallery( $gallery_id ) {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM " . self::table() . " WHERE gallery_id = %d ORDER BY sort_order ASC, id ASC",
            (int) $gallery_id
        ) );
    }

    public static function next_sort_order( $gallery_id ) {
        global $wpdb;
        $max = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT MAX(sort_order) FROM " . self::table() . " WHERE gallery_id = %d",
            (int) $gallery_id
        ) );
        return $max + 1;
    }

    public static function insert( $data ) {
        global $wpdb;
        $row = array(
            'gallery_id'  => (int) $data['gallery_id'],
            'filename'    => sanitize_file_name( $data['filename'] ),
            'caption'     => isset( $data['caption'] ) ? sanitize_text_field( $data['caption'] ) : '',
            'description' => isset( $data['description'] ) ? sanitize_textarea_field( $data['description'] ) : '',
            'alt_text'    => isset( $data['alt_text'] ) ? sanitize_text_field( $data['alt_text'] ) : '',
            'width'       => isset( $data['width'] ) ? (int) $data['width'] : 0,
            'height'      => isset( $data['height'] ) ? (int) $data['height'] : 0,
            'filesize'    => isset( $data['filesize'] ) ? (int) $data['filesize'] : 0,
            'sort_order'  => isset( $data['sort_order'] ) ? (int) $data['sort_order'] : 0,
            'created_at'  => current_time( 'mysql' ),
        );
        $ok = $wpdb->insert( self::table(), $row );
        if ( false === $ok ) {
            return new WP_Error( 'wpa9_db', $wpdb->last_error );
        }
        return (int) $wpdb->insert_id;
    }

    public static function update_meta( $id, $data ) {
        global $wpdb;
        $row = array();
        if ( isset( $data['caption'] ) ) {
            $row['caption'] = sanitize_text_field( $data['caption'] );
        }
        if ( isset( $data['description'] ) ) {
            $row['description'] = sanitize_textarea_field( $data['description'] );
        }
        if ( isset( $data['alt_text'] ) ) {
            $row['alt_text'] = sanitize_text_field( $data['alt_text'] );
        }
        if ( empty( $row ) ) {
            return true;
        }
        $wpdb->update( self::table(), $row, array( 'id' => (int) $id ) );
        return true;
    }

    public static function reorder( $gallery_id, array $ordered_ids ) {
        global $wpdb;
        $i = 1;
        foreach ( $ordered_ids as $img_id ) {
            $wpdb->update(
                self::table(),
                array( 'sort_order' => $i++ ),
                array( 'id' => (int) $img_id, 'gallery_id' => (int) $gallery_id )
            );
        }
        return true;
    }

    public static function delete( $id ) {
        global $wpdb;
        $img = self::get( $id );
        if ( ! $img ) {
            return false;
        }
        $gallery = WPA9_Gallery::get( $img->gallery_id );
        if ( $gallery ) {
            WPA9_Storage::delete_image_files( $gallery->slug, $img->filename );
        }
        $wpdb->delete( self::table(), array( 'id' => (int) $id ) );
        return true;
    }

    public static function delete_many( array $ids ) {
        $count = 0;
        foreach ( $ids as $id ) {
            if ( self::delete( (int) $id ) ) {
                $count++;
            }
        }
        return $count;
    }

    public static function url( $gallery, $image ) {
        return trailingslashit( WPA9_Storage::gallery_url( $gallery->slug ) ) . rawurlencode( $image->filename );
    }

    public static function thumb_url( $gallery, $image ) {
        return trailingslashit( WPA9_Storage::thumbs_url( $gallery->slug ) ) . rawurlencode( WPA9_Storage::thumb_filename( $image->filename ) );
    }
}
