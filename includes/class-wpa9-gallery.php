<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WPA9_Gallery {

    public static function table() {
        global $wpdb;
        return $wpdb->prefix . 'wpa9_galleries';
    }

    public static function all( $album_id = null ) {
        global $wpdb;
        $tbl = self::table();
        if ( $album_id !== null && $album_id !== '' ) {
            $j = $wpdb->prefix . 'wpa9_album_galleries';
            return $wpdb->get_results( $wpdb->prepare(
                "SELECT g.* FROM $tbl g
                 INNER JOIN $j j ON j.gallery_id = g.id
                 WHERE j.album_id = %d
                 ORDER BY j.sort_order ASC, g.name ASC",
                (int) $album_id
            ) );
        }
        return $wpdb->get_results( "SELECT * FROM $tbl ORDER BY sort_order ASC, name ASC" );
    }

    /** @return int[] album IDs this gallery belongs to. */
    public static function album_ids( $gallery_id ) {
        global $wpdb;
        $j = $wpdb->prefix . 'wpa9_album_galleries';
        $rows = $wpdb->get_col( $wpdb->prepare(
            "SELECT album_id FROM $j WHERE gallery_id = %d ORDER BY album_id ASC",
            (int) $gallery_id
        ) );
        return array_map( 'intval', (array) $rows );
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
        $settings = WPA9_Install::get_settings();
        $slug     = self::unique_slug( $data['name'] );

        $row = array(
            'name'            => sanitize_text_field( $data['name'] ),
            'slug'            => $slug,
            'description'     => isset( $data['description'] ) ? sanitize_textarea_field( $data['description'] ) : '',
            'author_name'     => isset( $data['author_name'] ) ? sanitize_text_field( $data['author_name'] ) : '',
            'fullsize_width'  => isset( $data['fullsize_width'] ) ? max( 0, (int) $data['fullsize_width'] ) : (int) $settings['fullsize_width'],
            'fullsize_height' => isset( $data['fullsize_height'] ) ? max( 0, (int) $data['fullsize_height'] ) : (int) $settings['fullsize_height'],
            'thumb_width'     => isset( $data['thumb_width'] ) ? max( 0, (int) $data['thumb_width'] ) : (int) $settings['thumb_width'],
            'thumb_height'    => isset( $data['thumb_height'] ) ? max( 0, (int) $data['thumb_height'] ) : (int) $settings['thumb_height'],
            'ratio'           => isset( $data['ratio'] ) ? self::sanitize_ratio( $data['ratio'] ) : '',
            'data_atts'       => isset( $data['data_atts'] ) ? sanitize_textarea_field( $data['data_atts'] ) : '',
            'sort_order'      => isset( $data['sort_order'] ) ? (int) $data['sort_order'] : 0,
            'created_at'      => current_time( 'mysql' ),
        );

        $ok = $wpdb->insert( self::table(), $row );
        if ( false === $ok ) {
            return new WP_Error( 'wpa9_db', $wpdb->last_error );
        }
        $id = (int) $wpdb->insert_id;
        WPA9_Storage::create_gallery_dir( $slug );
        return $id;
    }

    public static function update( $id, $data ) {
        global $wpdb;
        $current = self::get( $id );
        if ( ! $current ) {
            return new WP_Error( 'wpa9_missing', __( 'Gallery not found.', 'wpa9-gallery' ) );
        }

        $row = array(
            'name'            => sanitize_text_field( $data['name'] ),
            'description'     => isset( $data['description'] ) ? sanitize_textarea_field( $data['description'] ) : '',
            'author_name'     => isset( $data['author_name'] ) ? sanitize_text_field( $data['author_name'] ) : '',
            'fullsize_width'  => isset( $data['fullsize_width'] ) ? max( 0, (int) $data['fullsize_width'] ) : (int) $current->fullsize_width,
            'fullsize_height' => isset( $data['fullsize_height'] ) ? max( 0, (int) $data['fullsize_height'] ) : (int) $current->fullsize_height,
            'thumb_width'     => isset( $data['thumb_width'] ) ? max( 0, (int) $data['thumb_width'] ) : (int) $current->thumb_width,
            'thumb_height'    => isset( $data['thumb_height'] ) ? max( 0, (int) $data['thumb_height'] ) : (int) $current->thumb_height,
            'ratio'           => isset( $data['ratio'] ) ? self::sanitize_ratio( $data['ratio'] ) : $current->ratio,
            'data_atts'       => isset( $data['data_atts'] ) ? sanitize_textarea_field( $data['data_atts'] ) : (string) $current->data_atts,
            'sort_order'      => isset( $data['sort_order'] ) ? (int) $data['sort_order'] : (int) $current->sort_order,
        );

        if ( $row['name'] !== $current->name ) {
            $new_slug = self::unique_slug( $row['name'], (int) $id );
            if ( $new_slug !== $current->slug ) {
                WPA9_Storage::rename_gallery_dir( $current->slug, $new_slug );
                $row['slug'] = $new_slug;
            }
        }

        $wpdb->update( self::table(), $row, array( 'id' => (int) $id ) );
        return true;
    }

    public static function delete( $id ) {
        global $wpdb;
        $g = self::get( $id );
        if ( ! $g ) {
            return false;
        }
        // Remove rows for images, album memberships, then the gallery itself.
        $wpdb->delete( $wpdb->prefix . 'wpa9_images', array( 'gallery_id' => (int) $id ) );
        $wpdb->delete( $wpdb->prefix . 'wpa9_album_galleries', array( 'gallery_id' => (int) $id ) );
        $wpdb->delete( self::table(), array( 'id' => (int) $id ) );
        // Remove files.
        WPA9_Storage::delete_gallery_dir( $g->slug );
        return true;
    }

    public static function unique_slug( $name, $exclude_id = 0 ) {
        global $wpdb;
        $base = sanitize_title( $name );
        if ( $base === '' ) {
            $base = 'gallery';
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

    /** Accept '' (inherit) or one of WPA9_Install::ratios() keys; anything else returns ''. */
    public static function sanitize_ratio( $value ) {
        $value = is_string( $value ) ? trim( $value ) : '';
        if ( $value === '' ) {
            return '';
        }
        $allowed = array_keys( WPA9_Install::ratios() );
        return in_array( $value, $allowed, true ) ? $value : '';
    }

    /** Resolve effective ratio: gallery override → settings global. Returns one of the ratio keys, or 'none'. */
    public static function resolve_ratio( $gallery ) {
        $r = $gallery && ! empty( $gallery->ratio ) ? $gallery->ratio : '';
        if ( $r === '' ) {
            $s = WPA9_Install::get_settings();
            $r = ! empty( $s['ratio'] ) ? $s['ratio'] : 'none';
        }
        $allowed = array_keys( WPA9_Install::ratios() );
        return in_array( $r, $allowed, true ) ? $r : 'none';
    }

    /**
     * Resolve effective data_atts for a gallery (override → global), expand {slug}/{id}/{name}
     * tokens, and return an associative array of [data-key => value].
     *
     * Returns an empty array if nothing is configured or input is malformed.
     */
    public static function resolve_data_atts( $gallery ) {
        $raw = $gallery && isset( $gallery->data_atts ) && trim( (string) $gallery->data_atts ) !== ''
            ? (string) $gallery->data_atts
            : (string) ( WPA9_Install::get_settings()['data_atts'] ?? '' );

        if ( trim( $raw ) === '' ) {
            return array();
        }

        $tokens = array(
            '{slug}' => $gallery ? (string) $gallery->slug : '',
            '{id}'   => $gallery ? (string) (int) $gallery->id : '',
            '{name}' => $gallery ? (string) $gallery->name : '',
        );
        $raw = strtr( $raw, $tokens );

        $out = array();
        $lines = preg_split( '/\r\n|\r|\n/', $raw );
        foreach ( $lines as $line ) {
            $line = trim( $line );
            if ( $line === '' ) {
                continue;
            }
            $pos = strpos( $line, '=' );
            if ( $pos === false ) {
                $key   = $line;
                $value = '';
            } else {
                $key   = trim( substr( $line, 0, $pos ) );
                $value = trim( substr( $line, $pos + 1 ) );
                // Strip a single pair of surrounding quotes if present.
                if ( strlen( $value ) >= 2 && (
                    ( $value[0] === '"' && substr( $value, -1 ) === '"' ) ||
                    ( $value[0] === "'" && substr( $value, -1 ) === "'" )
                ) ) {
                    $value = substr( $value, 1, -1 );
                }
            }
            // data-* keys: lower-case ASCII letters, digits and hyphens only.
            $key = strtolower( preg_replace( '/[^a-z0-9\-]/i', '', $key ) );
            if ( $key === '' ) {
                continue;
            }
            $out[ $key ] = $value;
        }
        return $out;
    }

    /** Build " data-foo=\"bar\" data-baz=\"qux\"" ready to drop into a tag. Empty string when none. */
    public static function render_data_atts_html( $gallery ) {
        $atts = self::resolve_data_atts( $gallery );
        if ( empty( $atts ) ) {
            return '';
        }
        $parts = array();
        foreach ( $atts as $k => $v ) {
            $parts[] = sprintf( 'data-%s="%s"', esc_attr( $k ), esc_attr( $v ) );
        }
        return ' ' . implode( ' ', $parts );
    }

    public static function image_count( $gallery_id ) {
        global $wpdb;
        return (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM " . $wpdb->prefix . "wpa9_images WHERE gallery_id = %d",
            (int) $gallery_id
        ) );
    }
}
