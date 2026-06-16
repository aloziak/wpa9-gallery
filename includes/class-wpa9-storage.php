<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WPA9_Storage {

    const DIR = 'wpa9-galleries';

    public static function base_dir() {
        $u = wp_upload_dir();
        return trailingslashit( $u['basedir'] ) . self::DIR;
    }

    public static function base_url() {
        $u = wp_upload_dir();
        return trailingslashit( $u['baseurl'] ) . self::DIR;
    }

    public static function gallery_dir( $slug ) {
        return trailingslashit( self::base_dir() ) . $slug;
    }

    public static function gallery_url( $slug ) {
        return trailingslashit( self::base_url() ) . rawurlencode( $slug );
    }

    public static function thumbs_dir( $slug ) {
        return trailingslashit( self::gallery_dir( $slug ) ) . 'thumbs';
    }

    public static function thumbs_url( $slug ) {
        return trailingslashit( self::gallery_url( $slug ) ) . 'thumbs';
    }

    public static function thumb_filename( $filename ) {
        return 'thumbs_' . $filename;
    }

    public static function create_gallery_dir( $slug ) {
        $dir = self::gallery_dir( $slug );
        if ( ! file_exists( $dir ) ) {
            wp_mkdir_p( $dir );
        }
        $thumbs = self::thumbs_dir( $slug );
        if ( ! file_exists( $thumbs ) ) {
            wp_mkdir_p( $thumbs );
        }
        // Drop a tiny index.php into each so directory listing is closed.
        foreach ( array( $dir, $thumbs ) as $d ) {
            $idx = trailingslashit( $d ) . 'index.php';
            if ( ! file_exists( $idx ) ) {
                @file_put_contents( $idx, "<?php // Silence is golden.\n" );
            }
        }
        return $dir;
    }

    public static function rename_gallery_dir( $old_slug, $new_slug ) {
        if ( $old_slug === $new_slug ) {
            return true;
        }
        $old = self::gallery_dir( $old_slug );
        $new = self::gallery_dir( $new_slug );
        if ( file_exists( $old ) && ! file_exists( $new ) ) {
            return @rename( $old, $new );
        }
        if ( ! file_exists( $old ) ) {
            self::create_gallery_dir( $new_slug );
            return true;
        }
        return false;
    }

    public static function delete_gallery_dir( $slug ) {
        $dir = self::gallery_dir( $slug );
        if ( ! file_exists( $dir ) ) {
            return true;
        }
        self::rrmdir( $dir );
        return ! file_exists( $dir );
    }

    public static function delete_image_files( $slug, $filename ) {
        $orig  = trailingslashit( self::gallery_dir( $slug ) ) . $filename;
        $thumb = trailingslashit( self::thumbs_dir( $slug ) ) . self::thumb_filename( $filename );
        if ( file_exists( $orig ) ) {
            @unlink( $orig );
        }
        if ( file_exists( $thumb ) ) {
            @unlink( $thumb );
        }
    }

    public static function unique_filename( $slug, $filename ) {
        $dir = self::gallery_dir( $slug );
        if ( ! file_exists( $dir ) ) {
            self::create_gallery_dir( $slug );
        }
        return wp_unique_filename( $dir, $filename );
    }

    private static function rrmdir( $dir ) {
        if ( ! is_dir( $dir ) ) {
            return;
        }
        $items = scandir( $dir );
        foreach ( $items as $item ) {
            if ( $item === '.' || $item === '..' ) {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if ( is_dir( $path ) ) {
                self::rrmdir( $path );
            } else {
                @unlink( $path );
            }
        }
        @rmdir( $dir );
    }
}
