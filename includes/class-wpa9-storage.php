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

    /**
     * Prevent directory listing with an empty index.html (no PHP in uploads).
     * Removes a legacy index.php written by earlier plugin versions.
     *
     * @param string $dir Absolute directory path.
     */
    public static function protect_dir( $dir ) {
        if ( ! is_dir( $dir ) ) {
            return;
        }
        $php_index = trailingslashit( $dir ) . 'index.php';
        if ( file_exists( $php_index ) ) {
            wp_delete_file( $php_index );
        }
        $index = trailingslashit( $dir ) . 'index.html';
        if ( file_exists( $index ) ) {
            return;
        }
        $stub = WPA9_DIR . 'assets/index.html';
        if ( file_exists( $stub ) ) {
            copy( $stub, $index ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_copy -- empty anti-listing stub
            return;
        }
        file_put_contents( $index, '' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- empty anti-listing stub
    }

    /**
     * Protect the base dir and every gallery / thumbs subdirectory.
     */
    public static function protect_all_dirs() {
        $base = self::base_dir();
        self::protect_dir( $base );
        if ( ! is_dir( $base ) ) {
            return;
        }
        $items = scandir( $base );
        if ( ! is_array( $items ) ) {
            return;
        }
        foreach ( $items as $item ) {
            if ( '.' === $item || '..' === $item ) {
                continue;
            }
            $gallery_dir = trailingslashit( $base ) . $item;
            if ( ! is_dir( $gallery_dir ) ) {
                continue;
            }
            self::protect_dir( $gallery_dir );
            self::protect_dir( trailingslashit( $gallery_dir ) . 'thumbs' );
        }
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
        self::protect_dir( $dir );
        self::protect_dir( $thumbs );
        return $dir;
    }

    public static function rename_gallery_dir( $old_slug, $new_slug ) {
        if ( $old_slug === $new_slug ) {
            return true;
        }
        $old = self::gallery_dir( $old_slug );
        $new = self::gallery_dir( $new_slug );
        if ( file_exists( $old ) && ! file_exists( $new ) ) {
            return rename( $old, $new );
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
            wp_delete_file( $orig );
        }
        if ( file_exists( $thumb ) ) {
            wp_delete_file( $thumb );
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
        if ( ! is_array( $items ) ) {
            return;
        }
        foreach ( $items as $item ) {
            if ( $item === '.' || $item === '..' ) {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if ( is_dir( $path ) ) {
                self::rrmdir( $path );
            } else {
                wp_delete_file( $path );
            }
        }
        rmdir( $dir );
    }
}
