<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WPA9_Album {

    public static function table() {
        global $wpdb;
        return $wpdb->prefix . 'wpa9_albums';
    }

    public static function all() {
        global $wpdb;
        return $wpdb->get_results( "SELECT * FROM " . self::table() . " ORDER BY sort_order ASC, name ASC" );
    }

    public static function get( $id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . self::table() . " WHERE id = %d", (int) $id ) );
    }

    public static function get_by_slug( $slug ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . self::table() . " WHERE slug = %s", $slug ) );
    }

    public static function insert( $data ) {
        global $wpdb;
        $slug = self::unique_slug( $data['name'] );
        $row  = array(
            'name'        => sanitize_text_field( $data['name'] ),
            'slug'        => $slug,
            'description' => isset( $data['description'] ) ? sanitize_textarea_field( $data['description'] ) : '',
            'sort_order'  => isset( $data['sort_order'] ) ? (int) $data['sort_order'] : 0,
            'created_at'  => current_time( 'mysql' ),
        );
        $ok = $wpdb->insert( self::table(), $row );
        if ( false === $ok ) {
            return new WP_Error( 'wpa9_db', $wpdb->last_error );
        }
        return (int) $wpdb->insert_id;
    }

    public static function update( $id, $data ) {
        global $wpdb;
        $current = self::get( $id );
        if ( ! $current ) {
            return new WP_Error( 'wpa9_missing', __( 'Album not found.', 'wpa9-gallery' ) );
        }
        $row = array(
            'name'        => sanitize_text_field( $data['name'] ),
            'description' => isset( $data['description'] ) ? sanitize_textarea_field( $data['description'] ) : '',
            'sort_order'  => isset( $data['sort_order'] ) ? (int) $data['sort_order'] : (int) $current->sort_order,
        );
        if ( $row['name'] !== $current->name ) {
            $row['slug'] = self::unique_slug( $row['name'], (int) $id );
        }
        $wpdb->update( self::table(), $row, array( 'id' => (int) $id ) );
        return true;
    }

    public static function delete( $id ) {
        global $wpdb;
        // Detach galleries (do not delete them). Junction table only.
        $wpdb->delete( $wpdb->prefix . 'wpa9_album_galleries', array( 'album_id' => (int) $id ) );
        $wpdb->delete( self::table(), array( 'id' => (int) $id ) );
        return true;
    }

    /** @return int[] gallery IDs in this album, ordered by sort_order. */
    public static function gallery_ids( $album_id ) {
        global $wpdb;
        $j = $wpdb->prefix . 'wpa9_album_galleries';
        $rows = $wpdb->get_col( $wpdb->prepare(
            "SELECT gallery_id FROM $j WHERE album_id = %d ORDER BY sort_order ASC, gallery_id ASC",
            (int) $album_id
        ) );
        return array_map( 'intval', (array) $rows );
    }

    public static function gallery_count( $album_id ) {
        global $wpdb;
        $j = $wpdb->prefix . 'wpa9_album_galleries';
        return (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM $j WHERE album_id = %d", (int) $album_id
        ) );
    }

    /** Replace all member galleries with the given ordered list. */
    public static function set_gallery_ids( $album_id, array $gallery_ids ) {
        global $wpdb;
        $album_id = (int) $album_id;
        $j        = $wpdb->prefix . 'wpa9_album_galleries';
        $wpdb->delete( $j, array( 'album_id' => $album_id ) );
        $i = 1;
        foreach ( $gallery_ids as $gid ) {
            $gid = (int) $gid;
            if ( $gid <= 0 ) {
                continue;
            }
            $wpdb->query( $wpdb->prepare(
                "INSERT IGNORE INTO $j (album_id, gallery_id, sort_order) VALUES (%d, %d, %d)",
                $album_id, $gid, $i++
            ) );
        }
        return true;
    }

    public static function add_gallery( $album_id, $gallery_id ) {
        global $wpdb;
        $j = $wpdb->prefix . 'wpa9_album_galleries';
        $next = 1 + (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT MAX(sort_order) FROM $j WHERE album_id = %d", (int) $album_id
        ) );
        return false !== $wpdb->query( $wpdb->prepare(
            "INSERT IGNORE INTO $j (album_id, gallery_id, sort_order) VALUES (%d, %d, %d)",
            (int) $album_id, (int) $gallery_id, $next
        ) );
    }

    public static function remove_gallery( $album_id, $gallery_id ) {
        global $wpdb;
        $j = $wpdb->prefix . 'wpa9_album_galleries';
        return false !== $wpdb->delete( $j, array(
            'album_id'   => (int) $album_id,
            'gallery_id' => (int) $gallery_id,
        ) );
    }

    public static function unique_slug( $name, $exclude_id = 0 ) {
        global $wpdb;
        $base = sanitize_title( $name );
        if ( $base === '' ) {
            $base = 'album';
        }
        $slug = $base;
        $i    = 2;
        while ( true ) {
            $existing = $wpdb->get_var( $wpdb->prepare(
                "SELECT id FROM " . self::table() . " WHERE slug = %s AND id <> %d LIMIT 1",
                $slug, (int) $exclude_id
            ) );
            if ( ! $existing ) {
                return $slug;
            }
            $slug = $base . '-' . $i++;
        }
    }
}
