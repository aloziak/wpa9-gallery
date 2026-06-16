<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WPA9_Ajax {

    public function __construct() {
        add_action( 'wp_ajax_wpa9_upload',       array( $this, 'upload' ) );
        add_action( 'wp_ajax_wpa9_reorder',      array( $this, 'reorder' ) );
        add_action( 'wp_ajax_wpa9_delete_image', array( $this, 'delete_image' ) );
        add_action( 'wp_ajax_wpa9_bulk_delete',  array( $this, 'bulk_delete' ) );
        add_action( 'wp_ajax_wpa9_save_meta',    array( $this, 'save_meta' ) );
        add_action( 'wp_ajax_wpa9_regen_thumb',  array( $this, 'regen_thumb' ) );
        add_action( 'wp_ajax_wpa9_album_set_galleries', array( $this, 'album_set_galleries' ) );
    }

    private function require_caps_and_nonce() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'wpa9-gallery' ) ), 403 );
        }
        $nonce = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '';
        if ( ! wp_verify_nonce( $nonce, 'wpa9_admin' ) ) {
            wp_send_json_error( array( 'message' => __( 'Bad nonce.', 'wpa9-gallery' ) ), 403 );
        }
    }

    public function upload() {
        $this->require_caps_and_nonce();

        $gallery_id = isset( $_POST['gallery_id'] ) ? (int) $_POST['gallery_id'] : 0;
        $gallery    = WPA9_Gallery::get( $gallery_id );
        if ( ! $gallery ) {
            wp_send_json_error( array( 'message' => __( 'Gallery not found.', 'wpa9-gallery' ) ), 404 );
        }

        if ( empty( $_FILES['file'] ) ) {
            wp_send_json_error( array( 'message' => __( 'No file uploaded.', 'wpa9-gallery' ) ), 400 );
        }

        $file = $_FILES['file'];
        if ( ! empty( $file['error'] ) ) {
            wp_send_json_error( array( 'message' => sprintf( __( 'Upload error (%d).', 'wpa9-gallery' ), (int) $file['error'] ) ), 400 );
        }

        $filetype = wp_check_filetype( $file['name'] );
        if ( empty( $filetype['type'] ) || strpos( $filetype['type'], 'image/' ) !== 0 ) {
            wp_send_json_error( array( 'message' => __( 'Only image files are allowed.', 'wpa9-gallery' ) ), 400 );
        }

        WPA9_Storage::create_gallery_dir( $gallery->slug );
        $target_dir = WPA9_Storage::gallery_dir( $gallery->slug );
        $filename   = WPA9_Storage::unique_filename( $gallery->slug, $file['name'] );
        $target     = trailingslashit( $target_dir ) . $filename;

        if ( ! is_uploaded_file( $file['tmp_name'] ) ) {
            wp_send_json_error(
                array(
                    'message' => __( 'Invalid uploaded file.', 'wpa9-gallery' ),
                ),
                400
            );
        }

        // Some environments disallow move_uploaded_file; use copy() then unlink().
        if ( ! copy( $file['tmp_name'], $target ) ) {
            wp_send_json_error(
                array(
                    'message' => sprintf(
                        __( 'Failed to move uploaded file to %s.', 'wpa9-gallery' ),
                        basename( $target )
                    ),
                ),
                500
            );
        }
        // Remove the temporary uploaded file if it still exists.
        if ( file_exists( $file['tmp_name'] ) ) {
            @unlink( $file['tmp_name'] );
        }

        @chmod( $target, 0644 );

        $settings = WPA9_Install::get_settings();
        $quality  = (int) $settings['jpeg_quality'];

        // 1) Size the uploaded file in-place into the fullsize box.
        //    Both dims set → hard crop; one dim set → proportional; both 0 → untouched.
        $fs_w = (int) $gallery->fullsize_width;
        $fs_h = (int) $gallery->fullsize_height;
        WPA9_Image_Processor::resize_to_fit( $target, $target, $fs_w, $fs_h, $quality );

        // 2) Derive thumbnail in {slug}/thumbs/ from the (now sized) file.
        $thumb_w   = (int) $gallery->thumb_width;
        $thumb_h   = (int) $gallery->thumb_height;
        $thumb_dir = WPA9_Storage::thumbs_dir( $gallery->slug );
        if ( ! file_exists( $thumb_dir ) ) {
            wp_mkdir_p( $thumb_dir );
        }
        $thumb_path   = trailingslashit( $thumb_dir ) . WPA9_Storage::thumb_filename( $filename );
        $thumb_result = WPA9_Image_Processor::resize_to_fit( $target, $thumb_path, $thumb_w, $thumb_h, $quality );

        list( $w, $h ) = WPA9_Image_Processor::dimensions( $target );

        $image_id = WPA9_Image::insert( array(
            'gallery_id' => $gallery->id,
            'filename'   => $filename,
            'width'      => $w,
            'height'     => $h,
            'filesize'   => filesize( $target ),
            'sort_order' => WPA9_Image::next_sort_order( $gallery->id ),
        ) );

        if ( is_wp_error( $image_id ) ) {
            wp_send_json_error( array( 'message' => $image_id->get_error_message() ), 500 );
        }

        list( $thumb_px_w, $thumb_px_h ) = WPA9_Image_Processor::dimensions( $thumb_path );

        $image = WPA9_Image::get( $image_id );
        wp_send_json_success( array(
            'id'           => $image->id,
            'filename'     => $image->filename,
            'url'          => WPA9_Image::url( $gallery, $image ),
            'thumb_url'    => is_wp_error( $thumb_result ) ? WPA9_Image::url( $gallery, $image ) : WPA9_Image::thumb_url( $gallery, $image ),
            'width'        => (int) $image->width,
            'height'       => (int) $image->height,
            'thumb_width'  => (int) $thumb_px_w,
            'thumb_height' => (int) $thumb_px_h,
        ) );
    }

    public function reorder() {
        $this->require_caps_and_nonce();
        $gallery_id = isset( $_POST['gallery_id'] ) ? (int) $_POST['gallery_id'] : 0;
        $ids_raw    = isset( $_POST['ids'] ) ? (array) $_POST['ids'] : array();
        $ids        = array_map( 'intval', $ids_raw );
        if ( ! $gallery_id || empty( $ids ) ) {
            wp_send_json_error( array( 'message' => __( 'Nothing to reorder.', 'wpa9-gallery' ) ), 400 );
        }
        WPA9_Image::reorder( $gallery_id, $ids );
        wp_send_json_success();
    }

    public function delete_image() {
        $this->require_caps_and_nonce();
        $id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
        if ( ! $id ) {
            wp_send_json_error( array( 'message' => __( 'Bad request.', 'wpa9-gallery' ) ), 400 );
        }
        WPA9_Image::delete( $id );
        wp_send_json_success();
    }

    public function bulk_delete() {
        $this->require_caps_and_nonce();
        $ids_raw = isset( $_POST['ids'] ) ? (array) $_POST['ids'] : array();
        $ids     = array_map( 'intval', $ids_raw );
        if ( empty( $ids ) ) {
            wp_send_json_error( array( 'message' => __( 'No images selected.', 'wpa9-gallery' ) ), 400 );
        }
        $deleted = WPA9_Image::delete_many( $ids );
        wp_send_json_success( array( 'deleted' => $deleted ) );
    }

    public function save_meta() {
        $this->require_caps_and_nonce();
        $id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
        if ( ! $id ) {
            wp_send_json_error( array( 'message' => __( 'Bad request.', 'wpa9-gallery' ) ), 400 );
        }
        $data = array(
            'caption'     => isset( $_POST['caption'] ) ? wp_unslash( $_POST['caption'] ) : '',
            'description' => isset( $_POST['description'] ) ? wp_unslash( $_POST['description'] ) : '',
            'alt_text'    => isset( $_POST['alt_text'] ) ? wp_unslash( $_POST['alt_text'] ) : '',
        );
        WPA9_Image::update_meta( $id, $data );
        wp_send_json_success();
    }

    public function album_set_galleries() {
        $this->require_caps_and_nonce();
        $album_id = isset( $_POST['album_id'] ) ? (int) $_POST['album_id'] : 0;
        if ( ! $album_id || ! WPA9_Album::get( $album_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Album not found.', 'wpa9-gallery' ) ), 404 );
        }
        $ids_raw = isset( $_POST['gallery_ids'] ) ? (array) $_POST['gallery_ids'] : array();
        $ids     = array_values( array_unique( array_map( 'intval', $ids_raw ) ) );
        WPA9_Album::set_gallery_ids( $album_id, $ids );
        wp_send_json_success( array( 'count' => count( $ids ) ) );
    }

    public function regen_thumb() {
        $this->require_caps_and_nonce();
        $id    = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
        $image = WPA9_Image::get( $id );
        if ( ! $image ) {
            wp_send_json_error( array( 'message' => __( 'Image not found.', 'wpa9-gallery' ) ), 404 );
        }
        $gallery = WPA9_Gallery::get( $image->gallery_id );
        if ( ! $gallery ) {
            wp_send_json_error( array( 'message' => __( 'Gallery not found.', 'wpa9-gallery' ) ), 404 );
        }
        $settings = WPA9_Install::get_settings();
        $src      = trailingslashit( WPA9_Storage::gallery_dir( $gallery->slug ) ) . $image->filename;
        $dest_dir = WPA9_Storage::thumbs_dir( $gallery->slug );
        if ( ! file_exists( $dest_dir ) ) {
            wp_mkdir_p( $dest_dir );
        }
        $dest = trailingslashit( $dest_dir ) . WPA9_Storage::thumb_filename( $image->filename );

        // Delete the old thumbnail before rebuilding it at the gallery's current thumb size.
        if ( file_exists( $dest ) ) {
            @unlink( $dest );
        }

        $res = WPA9_Image_Processor::resize_to_fit(
            $src, $dest,
            (int) $gallery->thumb_width, (int) $gallery->thumb_height,
            (int) $settings['jpeg_quality']
        );
        if ( is_wp_error( $res ) ) {
            wp_send_json_error( array( 'message' => $res->get_error_message() ), 500 );
        }
        list( $thumb_w, $thumb_h ) = WPA9_Image_Processor::dimensions( $dest );
        wp_send_json_success( array(
            'thumb_url'    => WPA9_Image::thumb_url( $gallery, $image ) . '?v=' . time(),
            'thumb_width'  => (int) $thumb_w,
            'thumb_height' => (int) $thumb_h,
        ) );
    }
}
