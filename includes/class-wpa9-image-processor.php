<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WPA9_Image_Processor {

    /**
     * Size an image into a target box. The behaviour depends on which of
     * $max_w / $max_h are provided (a value of 0 means "unset / auto"):
     *
     *   - both > 0  → hard crop to exactly $max_w × $max_h (centered, no distortion)
     *   - one  > 0  → scale proportionally to that single dimension (downscale only)
     *   - both = 0  → leave at original size
     *
     * Writes to $dest_path (may be the same as $source_path for in-place).
     */
    public static function resize_to_fit( $source_path, $dest_path, $max_w, $max_h, $quality = 85 ) {
        if ( ! file_exists( $source_path ) ) {
            return new WP_Error( 'wpa9_missing_source', __( 'Source file does not exist.', 'wpa9-gallery' ) );
        }

        $max_w = (int) $max_w;
        $max_h = (int) $max_h;
        $crop  = ( $max_w > 0 && $max_h > 0 );

        // No target box at all → just place the original at the destination.
        if ( $max_w <= 0 && $max_h <= 0 ) {
            return self::passthrough( $source_path, $dest_path );
        }

        $editor = wp_get_image_editor( $source_path );
        if ( is_wp_error( $editor ) ) {
            return $editor;
        }

        $editor->set_quality( (int) $quality );
        $resized = $editor->resize( $max_w, $max_h, $crop );
        if ( is_wp_error( $resized ) ) {
            // "error_getting_dimensions" means no resize was necessary (the image
            // already fits / is smaller than the proportional target). Keep the original.
            if ( $resized->get_error_code() === 'error_getting_dimensions' ) {
                return self::passthrough( $source_path, $dest_path );
            }
            return $resized;
        }

        $saved = $editor->save( $dest_path );
        if ( is_wp_error( $saved ) ) {
            return $saved;
        }
        return $saved;
    }

    /**
     * Place the source image at the destination unchanged (used when no resize
     * or crop is required). Returns a save-like array so callers can treat the
     * result the same as a real resize.
     */
    private static function passthrough( $source_path, $dest_path ) {
        if ( $source_path !== $dest_path ) {
            if ( ! copy( $source_path, $dest_path ) ) {
                return new WP_Error( 'wpa9_copy_failed', __( 'Could not copy image file.', 'wpa9-gallery' ) );
            }
            if ( file_exists( $dest_path ) ) {
                chmod( $dest_path, 0644 );
            }
        }
        list( $w, $h ) = self::dimensions( $dest_path );
        return array(
            'path'   => $dest_path,
            'file'   => basename( $dest_path ),
            'width'  => $w,
            'height' => $h,
        );
    }

    public static function dimensions( $path ) {
        if ( ! file_exists( $path ) ) {
            return array( 0, 0 );
        }
        $size = getimagesize( $path );
        if ( ! $size ) {
            return array( 0, 0 );
        }
        return array( (int) $size[0], (int) $size[1] );
    }

    public static function is_image( $path ) {
        $type = wp_check_filetype( basename( $path ) );
        return $type && strpos( (string) $type['type'], 'image/' ) === 0;
    }
}
