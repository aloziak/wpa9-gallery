<?php
/**
 * NextGEN Gallery importer.
 *
 * Reads from NGG's tables and creates equivalent WPA9 rows. Files are NOT touched —
 * after running, the result view shows the source/destination directory pairs so the
 * site owner can copy files by hand and then regenerate thumbs.
 *
 * NGG schema reference (Imagely's NextGEN Gallery):
 *   {prefix}ngg_gallery   — gid, name, slug, path, title, galdesc, pageid, previewpic, author
 *   {prefix}ngg_pictures  — pid, galleryid, filename, description, alttext, sortorder, image_slug, exclude, meta_data
 *   {prefix}ngg_album     — id, name, slug, previewpic, albumdesc, sortorder, pageid, galleries (serialized array of gids)
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WPA9_Importer_NextGEN {

    public static function tables() {
        global $wpdb;
        return array(
            'gallery'  => $wpdb->prefix . 'ngg_gallery',
            'pictures' => $wpdb->prefix . 'ngg_pictures',
            'album'    => $wpdb->prefix . 'ngg_album',
        );
    }

    /** @return bool true when at least the galleries + pictures tables exist. */
    public static function is_available() {
        global $wpdb;
        $t = self::tables();
        $g = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $t['gallery'] ) );
        $p = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $t['pictures'] ) );
        return $g === $t['gallery'] && $p === $t['pictures'];
    }

    /** @return array{galleries:int, pictures:int, albums:int} */
    public static function preview() {
        global $wpdb;
        $t = self::tables();
        $out = array( 'galleries' => 0, 'pictures' => 0, 'albums' => 0 );
        if ( ! self::is_available() ) {
            return $out;
        }
        $out['galleries'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t['gallery']}" );
        $out['pictures']  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t['pictures']}" );
        $alb_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $t['album'] ) ) === $t['album'];
        if ( $alb_exists ) {
            $out['albums'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t['album']}" );
        }
        return $out;
    }

    /**
     * Run the import. Returns array with counts, warnings, and file_map.
     *
     * @return array|WP_Error
     */
    public static function import() {
        global $wpdb;
        if ( ! self::is_available() ) {
            return new WP_Error( 'wpa9_no_nextgen', __( 'NextGEN Gallery tables not found.', 'wpa9-gallery' ) );
        }

        @set_time_limit( 300 );
        if ( function_exists( 'wp_raise_memory_limit' ) ) {
            wp_raise_memory_limit( 'admin' );
        }

        $t = self::tables();
        $result = array(
            'galleries' => 0,
            'pictures'  => 0,
            'albums'    => 0,
            'file_map'  => array(),
            'warnings'  => array(),
        );

        // ---------- 1) Galleries ----------
        $gallery_id_map = array(); // ngg gid => wpa9 id
        $ngg_galleries  = $wpdb->get_results( "SELECT * FROM {$t['gallery']} ORDER BY gid ASC" );

        foreach ( $ngg_galleries as $ngg ) {
            $name = '';
            if ( ! empty( $ngg->title ) ) {
                $name = $ngg->title;
            } elseif ( ! empty( $ngg->name ) ) {
                $name = $ngg->name;
            } else {
                $name = sprintf( __( 'NextGEN Gallery #%d', 'wpa9-gallery' ), (int) $ngg->gid );
            }

            $author_name = '';
            if ( ! empty( $ngg->author ) ) {
                $user = get_userdata( (int) $ngg->author );
                if ( $user ) {
                    $author_name = $user->display_name;
                }
            }

            $new_id = WPA9_Gallery::insert( array(
                'name'        => $name,
                'description' => isset( $ngg->galdesc ) ? (string) $ngg->galdesc : '',
                'author_name' => $author_name,
            ) );

            if ( is_wp_error( $new_id ) ) {
                $result['warnings'][] = sprintf(
                    /* translators: 1: NGG gallery id, 2: NGG gallery name, 3: error message */
                    __( 'Gallery #%1$d (%2$s): %3$s', 'wpa9-gallery' ),
                    (int) $ngg->gid, $name, $new_id->get_error_message()
                );
                continue;
            }

            $gallery_id_map[ (int) $ngg->gid ] = (int) $new_id;
            $result['galleries']++;

            $new_gallery = WPA9_Gallery::get( $new_id );
            $src_rel     = isset( $ngg->path ) ? trim( (string) $ngg->path ) : '';
            $src_full    = $src_rel !== '' ? trailingslashit( ABSPATH ) . ltrim( $src_rel, '/' ) : '';
            $result['file_map'][] = array(
                'name'        => $name,
                'ngg_gid'     => (int) $ngg->gid,
                'source'      => $src_full,
                'destination' => WPA9_Storage::gallery_dir( $new_gallery->slug ),
            );
        }

        // ---------- 2) Pictures ----------
        if ( ! empty( $gallery_id_map ) ) {
            $gids   = array_keys( $gallery_id_map );
            $in     = implode( ',', array_map( 'intval', $gids ) );
            $rows   = $wpdb->get_results(
                "SELECT * FROM {$t['pictures']} WHERE galleryid IN ($in) ORDER BY galleryid ASC, sortorder ASC, pid ASC"
            );

            foreach ( $rows as $p ) {
                $gallery_id = isset( $gallery_id_map[ (int) $p->galleryid ] ) ? $gallery_id_map[ (int) $p->galleryid ] : 0;
                if ( ! $gallery_id ) {
                    continue;
                }

                $w = 0;
                $h = 0;
                if ( isset( $p->meta_data ) && $p->meta_data !== '' ) {
                    $meta = maybe_unserialize( $p->meta_data );
                    if ( is_array( $meta ) ) {
                        if ( isset( $meta['width'] ) )  { $w = (int) $meta['width']; }
                        if ( isset( $meta['height'] ) ) { $h = (int) $meta['height']; }
                    }
                }

                $ins = WPA9_Image::insert( array(
                    'gallery_id'  => (int) $gallery_id,
                    'filename'    => isset( $p->filename ) ? (string) $p->filename : '',
                    'caption'     => isset( $p->description ) ? (string) $p->description : '',
                    'description' => '', // NGG has no separate description field; caption maps to "description" column there.
                    'alt_text'    => isset( $p->alttext ) ? (string) $p->alttext : '',
                    'width'       => $w,
                    'height'      => $h,
                    'filesize'    => 0,
                    'sort_order'  => isset( $p->sortorder ) ? (int) $p->sortorder : 0,
                ) );

                if ( ! is_wp_error( $ins ) ) {
                    $result['pictures']++;
                }
            }
        }

        // ---------- 3) Albums ----------
        $alb_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $t['album'] ) ) === $t['album'];
        if ( $alb_exists ) {
            $ngg_albums = $wpdb->get_results( "SELECT * FROM {$t['album']} ORDER BY id ASC" );
            foreach ( $ngg_albums as $a ) {
                $name = ! empty( $a->name ) ? (string) $a->name : sprintf( __( 'NextGEN Album #%d', 'wpa9-gallery' ), (int) $a->id );

                $new_album_id = WPA9_Album::insert( array(
                    'name'        => $name,
                    'description' => isset( $a->albumdesc ) ? (string) $a->albumdesc : '',
                    'sort_order'  => isset( $a->sortorder ) ? (int) $a->sortorder : 0,
                ) );

                if ( is_wp_error( $new_album_id ) ) {
                    $result['warnings'][] = sprintf(
                        /* translators: 1: NGG album id, 2: NGG album name, 3: error message */
                        __( 'Album #%1$d (%2$s): %3$s', 'wpa9-gallery' ),
                        (int) $a->id, $name, $new_album_id->get_error_message()
                    );
                    continue;
                }
                $result['albums']++;

                // Assign galleries listed in this NGG album via the junction table.
                $gallery_list = isset( $a->galleries ) ? maybe_unserialize( $a->galleries ) : array();
                if ( is_array( $gallery_list ) ) {
                    foreach ( $gallery_list as $ngg_gid ) {
                        $ngg_gid = (int) $ngg_gid;
                        if ( ! isset( $gallery_id_map[ $ngg_gid ] ) ) {
                            continue;
                        }
                        WPA9_Album::add_gallery( (int) $new_album_id, (int) $gallery_id_map[ $ngg_gid ] );
                    }
                }
            }
        }

        return $result;
    }
}
