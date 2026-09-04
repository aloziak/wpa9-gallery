<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WPA9_Admin {

    const DASHBOARD_SLUG = 'wpa9-dashboard';
    const MENU_SLUG      = 'wpa9-galleries';
    const CAP            = 'manage_options';

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'register_menus' ) );
        add_action( 'admin_init', array( $this, 'handle_post' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'admin_notices', array( $this, 'show_notices' ) );
    }

    public function register_menus() {
        add_menu_page(
            __( 'WPA9 Gallery', 'wpa9-gallery' ),
            __( 'WPA9 Gallery', 'wpa9-gallery' ),
            self::CAP,
            self::DASHBOARD_SLUG,
            array( $this, 'page_dashboard' ),
            'dashicons-format-gallery',
            58
        );
        add_submenu_page( self::DASHBOARD_SLUG, __( 'Dashboard', 'wpa9-gallery' ), __( 'Dashboard', 'wpa9-gallery' ), self::CAP, self::DASHBOARD_SLUG, array( $this, 'page_dashboard' ) );
        add_submenu_page( self::DASHBOARD_SLUG, __( 'Galleries', 'wpa9-gallery' ),   __( 'Galleries', 'wpa9-gallery' ),   self::CAP, self::MENU_SLUG, array( $this, 'page_galleries' ) );
        add_submenu_page( self::DASHBOARD_SLUG, __( 'Add Gallery', 'wpa9-gallery' ), __( 'Add Gallery', 'wpa9-gallery' ), self::CAP, 'wpa9-gallery-edit',   array( $this, 'page_gallery_edit' ) );
        add_submenu_page( self::DASHBOARD_SLUG, __( 'Albums', 'wpa9-gallery' ),      __( 'Albums', 'wpa9-gallery' ),      self::CAP, 'wpa9-albums',         array( $this, 'page_albums' ) );
        add_submenu_page( self::DASHBOARD_SLUG, __( 'Add Album', 'wpa9-gallery' ),   __( 'Add Album', 'wpa9-gallery' ),   self::CAP, 'wpa9-album-edit',     array( $this, 'page_album_edit' ) );
        add_submenu_page( self::DASHBOARD_SLUG, __( 'Settings', 'wpa9-gallery' ),    __( 'Settings', 'wpa9-gallery' ),    self::CAP, 'wpa9-settings',       array( $this, 'page_settings' ) );
        add_submenu_page( self::DASHBOARD_SLUG, __( 'Import from NextGen', 'wpa9-gallery' ), __( 'Import from NextGen', 'wpa9-gallery' ), self::CAP, 'wpa9-import-imagely', array( $this, 'page_import' ) );
        add_submenu_page( self::DASHBOARD_SLUG, __( 'Export/Import', 'wpa9-gallery' ), __( 'Export/Import', 'wpa9-gallery' ), self::CAP, 'wpa9-export-import', array( $this, 'page_export_import' ) );
    }

    private function current_page() {
        return isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
    }

    private function is_plugin_page() {
        $page = $this->current_page();
        return in_array( $page, array( self::DASHBOARD_SLUG, self::MENU_SLUG, 'wpa9-gallery-edit', 'wpa9-albums', 'wpa9-album-edit', 'wpa9-settings', 'wpa9-import-imagely', 'wpa9-export-import' ), true );
    }

    public function enqueue_assets( $hook ) {
        if ( ! $this->is_plugin_page() ) {
            return;
        }
        wp_enqueue_media();
        wp_enqueue_script( 'plupload-all' );
        wp_enqueue_script( 'jquery-ui-sortable' );

        wp_enqueue_style( 'wpa9-admin', WPA9_URL . 'admin/css/admin.css', array(), WPA9_VERSION );
        wp_enqueue_script( 'wpa9-admin', WPA9_URL . 'admin/js/admin.js', array( 'jquery', 'plupload-all', 'jquery-ui-sortable' ), WPA9_VERSION, true );

        if ( 'wpa9-export-import' === $this->current_page() ) {
            wp_enqueue_script(
                'wpa9-export-import',
                WPA9_URL . 'admin/js/export-import.js',
                array(),
                WPA9_VERSION,
                true
            );
        }

        wp_localize_script( 'wpa9-admin', 'WPA9', array(
            'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
            'nonce'     => wp_create_nonce( 'wpa9_admin' ),
            'galleryId' => isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0,
            'maxUploadSize' => wp_max_upload_size(),
            'i18n'      => array(
                'confirmDeleteImage'    => __( 'Delete this image? This cannot be undone.', 'wpa9-gallery' ),
                'confirmBulkDelete'     => __( 'Delete the selected images? This cannot be undone.', 'wpa9-gallery' ),
                'confirmBulkRegen'      => __( 'Regenerate thumbnails for the selected images? Existing thumbnails will be deleted and rebuilt at the current Thumbnail (max W × H) size. Image metadata is preserved.', 'wpa9-gallery' ),
                /* translators: %1$d: number processed, %2$d: total. */
                'regenProgress'         => __( 'Regenerating thumbnails… %1$d of %2$d', 'wpa9-gallery' ),
                /* translators: %d: number of thumbnails regenerated. */
                'regenDone'             => __( 'Done. Regenerated %d thumbnails.', 'wpa9-gallery' ),
                /* translators: %1$d: number succeeded, %2$d: number failed. */
                'regenDoneErrors'       => __( 'Finished with errors: %1$d succeeded, %2$d failed.', 'wpa9-gallery' ),
                'noSelection'           => __( 'No images selected.', 'wpa9-gallery' ),
                'imageSize'             => __( 'Image size:', 'wpa9-gallery' ),
                'saved'                 => __( 'Saved.', 'wpa9-gallery' ),
                'saving'                => __( 'Saving…', 'wpa9-gallery' ),
                'saveError'             => __( 'Save failed.', 'wpa9-gallery' ),
                'uploadError'           => __( 'Upload error', 'wpa9-gallery' ),
                'dropFiles'             => __( 'Drag files here', 'wpa9-gallery' ),
            ),
        ) );
    }

    public function show_notices() {
        if ( ! $this->is_plugin_page() ) {
            return;
        }
        $stored = get_transient( 'wpa9_notice_' . get_current_user_id() );
        if ( ! is_array( $stored ) || empty( $stored['message'] ) ) {
            return;
        }
        delete_transient( 'wpa9_notice_' . get_current_user_id() );

        $allowed_types = array( 'success', 'error', 'warning' );
        $type          = isset( $stored['type'] ) ? sanitize_key( $stored['type'] ) : 'success';
        if ( ! in_array( $type, $allowed_types, true ) ) {
            $type = 'success';
        }
        $notice = sanitize_text_field( $stored['message'] );
        if ( $notice === '' ) {
            return;
        }
        printf(
            '<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
            esc_attr( $type ),
            esc_html( $notice )
        );
    }

    private function redirect_with_notice( $args, $message, $type = 'success' ) {
        set_transient(
            'wpa9_notice_' . get_current_user_id(),
            array(
                'message' => $message,
                'type'    => $type,
            ),
            30
        );
        wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
        exit;
    }

    public function handle_post() {
        if ( ! current_user_can( self::CAP ) ) {
            return;
        }
        if ( ! isset( $_POST['wpa9_action'] ) ) {
            return;
        }
        $action = sanitize_key( wp_unslash( $_POST['wpa9_action'] ) );
        check_admin_referer( 'wpa9_' . $action );

        switch ( $action ) {

            case 'save_gallery':
                $id   = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
                $data = array(
                    'name'            => isset( $_POST['name'] )            ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
                    'description'     => isset( $_POST['description'] )     ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '',
                    'author_name'     => isset( $_POST['author_name'] )     ? sanitize_text_field( wp_unslash( $_POST['author_name'] ) ) : '',
                    'fullsize_width'  => isset( $_POST['fullsize_width'] )  ? absint( $_POST['fullsize_width'] ) : 2560,
                    'fullsize_height' => isset( $_POST['fullsize_height'] ) ? absint( $_POST['fullsize_height'] ) : 2560,
                    'thumb_width'     => isset( $_POST['thumb_width'] )     ? absint( $_POST['thumb_width'] ) : 576,
                    'thumb_height'    => isset( $_POST['thumb_height'] )    ? absint( $_POST['thumb_height'] ) : 576,
                    'ratio'           => isset( $_POST['ratio'] )           ? WPA9_Gallery::sanitize_ratio( sanitize_key( wp_unslash( $_POST['ratio'] ) ) ) : '',
                    'data_atts'       => isset( $_POST['data_atts'] )       ? sanitize_textarea_field( wp_unslash( $_POST['data_atts'] ) ) : '',
                    'sort_order'      => isset( $_POST['sort_order'] )      ? intval( wp_unslash( $_POST['sort_order'] ) ) : 0,
                );
                if ( $id > 0 ) {
                    $res = WPA9_Gallery::update( $id, $data );
                    if ( is_wp_error( $res ) ) {
                        $this->redirect_with_notice( array( 'page' => 'wpa9-gallery-edit', 'id' => $id ), $res->get_error_message(), 'error' );
                    }
                    $this->redirect_with_notice( array( 'page' => 'wpa9-gallery-edit', 'id' => $id ), __( 'Gallery updated.', 'wpa9-gallery' ) );
                } else {
                    $res = WPA9_Gallery::insert( $data );
                    if ( is_wp_error( $res ) ) {
                        $this->redirect_with_notice( array( 'page' => 'wpa9-gallery-edit' ), $res->get_error_message(), 'error' );
                    }
                    $this->redirect_with_notice( array( 'page' => 'wpa9-gallery-edit', 'id' => $res ), __( 'Gallery created. You can now upload images.', 'wpa9-gallery' ) );
                }
                break;

            case 'delete_gallery':
                $id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
                if ( $id ) {
                    WPA9_Gallery::delete( $id );
                }
                $this->redirect_with_notice( array( 'page' => self::MENU_SLUG ), __( 'Gallery deleted.', 'wpa9-gallery' ) );
                break;

            case 'save_album':
                $id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
                $data = array(
                    'name'        => isset( $_POST['name'] )        ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
                    'description' => isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '',
                    'sort_order'  => isset( $_POST['sort_order'] )  ? intval( wp_unslash( $_POST['sort_order'] ) ) : 0,
                );
                if ( $id > 0 ) {
                    WPA9_Album::update( $id, $data );
                    $this->redirect_with_notice( array( 'page' => 'wpa9-album-edit', 'id' => $id ), __( 'Album updated.', 'wpa9-gallery' ) );
                } else {
                    $res = WPA9_Album::insert( $data );
                    if ( is_wp_error( $res ) ) {
                        $this->redirect_with_notice( array( 'page' => 'wpa9-album-edit' ), $res->get_error_message(), 'error' );
                    }
                    $this->redirect_with_notice( array( 'page' => 'wpa9-album-edit', 'id' => $res ), __( 'Album created.', 'wpa9-gallery' ) );
                }
                break;

            case 'delete_album':
                $id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
                if ( $id ) {
                    WPA9_Album::delete( $id );
                }
                $this->redirect_with_notice( array( 'page' => 'wpa9-albums' ), __( 'Album deleted. Its galleries were detached.', 'wpa9-gallery' ) );
                break;

            case 'save_settings':
                $ratio_in = isset( $_POST['ratio'] ) ? sanitize_text_field( wp_unslash( $_POST['ratio'] ) ) : 'none';
                $allowed_ratios = array_keys( WPA9_Install::ratios() );
                if ( ! in_array( $ratio_in, $allowed_ratios, true ) ) {
                    $ratio_in = 'none';
                }
                $opts = array(
                    'fullsize_width'  => isset( $_POST['fullsize_width'] )  ? absint( $_POST['fullsize_width'] ) : 0,
                    'fullsize_height' => isset( $_POST['fullsize_height'] ) ? absint( $_POST['fullsize_height'] ) : 0,
                    'thumb_width'     => isset( $_POST['thumb_width'] )     ? absint( $_POST['thumb_width'] ) : 0,
                    'thumb_height'    => isset( $_POST['thumb_height'] )    ? absint( $_POST['thumb_height'] ) : 0,
                    'ratio'           => $ratio_in,
                    'jpeg_quality'    => isset( $_POST['jpeg_quality'] )    ? min( 100, max( 10, absint( $_POST['jpeg_quality'] ) ) ) : 85,
                    'data_atts'       => isset( $_POST['data_atts'] )       ? sanitize_textarea_field( wp_unslash( $_POST['data_atts'] ) ) : '',
                );
                update_option( WPA9_Install::OPT_SETTINGS, $opts );
                $this->redirect_with_notice( array( 'page' => 'wpa9-settings' ), __( 'Settings saved.', 'wpa9-gallery' ) );
                break;

            case 'import_nextgen':
                if ( ! class_exists( 'WPA9_Importer_NextGEN' ) || ! WPA9_Importer_NextGEN::is_available() ) {
                    $this->redirect_with_notice( array( 'page' => 'wpa9-import-imagely' ), __( 'NextGEN Gallery tables not found.', 'wpa9-gallery' ), 'error' );
                }
                $result = WPA9_Importer_NextGEN::import();
                if ( is_wp_error( $result ) ) {
                    $this->redirect_with_notice( array( 'page' => 'wpa9-import-imagely' ), $result->get_error_message(), 'error' );
                }
                set_transient( 'wpa9_import_result_' . get_current_user_id(), $result, 5 * MINUTE_IN_SECONDS );
                $this->redirect_with_notice(
                    array( 'page' => 'wpa9-import-imagely', 'imported' => 1 ),
                    sprintf(
                        /* translators: 1: galleries count, 2: images count, 3: albums count */
                        __( 'Imported %1$d galleries, %2$d images, %3$d albums.', 'wpa9-gallery' ),
                        (int) $result['galleries'], (int) $result['pictures'], (int) $result['albums']
                    )
                );
                break;

            case 'export_wpa9':
                $export_data = WPA9_Exporter::export_all();
                $json = WPA9_Exporter::to_json( $export_data );
                nocache_headers();
                header( 'Content-Type: application/json; charset=utf-8' );
                header( 'Content-Disposition: attachment; filename="wpa9-gallery-export-' . current_time( 'Y-m-d-His' ) . '.json"' );
                header( 'Content-Length: ' . strlen( $json ) );
                echo $json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON download generated by wp_json_encode.
                exit;

            case 'import_wpa9':
                if ( ! isset( $_FILES['import_file'] ) ) {
                    $this->redirect_with_notice( array( 'page' => 'wpa9-export-import' ), __( 'No file provided.', 'wpa9-gallery' ), 'error' );
                }
                $file = $_FILES['import_file'];
                if ( ! isset( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
                    $this->redirect_with_notice( array( 'page' => 'wpa9-export-import' ), __( 'Invalid uploaded file.', 'wpa9-gallery' ), 'error' );
                }
                if ( isset( $file['error'] ) && (int) $file['error'] !== UPLOAD_ERR_OK ) {
                    $this->redirect_with_notice( array( 'page' => 'wpa9-export-import' ), __( 'File upload error.', 'wpa9-gallery' ), 'error' );
                }
                if ( isset( $file['size'] ) && (int) $file['size'] > 10 * MB_IN_BYTES ) {
                    $this->redirect_with_notice( array( 'page' => 'wpa9-export-import' ), __( 'File too large (max 10 MB).', 'wpa9-gallery' ), 'error' );
                }
                $name = isset( $file['name'] ) ? sanitize_file_name( wp_unslash( $file['name'] ) ) : '';
                $ext  = strtolower( pathinfo( $name, PATHINFO_EXTENSION ) );
                if ( 'json' !== $ext ) {
                    $this->redirect_with_notice( array( 'page' => 'wpa9-export-import' ), __( 'Please upload a JSON file.', 'wpa9-gallery' ), 'error' );
                }
                $json = file_get_contents( $file['tmp_name'] );
                $validation = WPA9_Importer::validate_import_file( $json );
                if ( is_wp_error( $validation ) ) {
                    $this->redirect_with_notice( array( 'page' => 'wpa9-export-import' ), $validation->get_error_message(), 'error' );
                }
                $transient_key = 'wpa9_pending_import_' . wp_hash( $json );
                set_transient( $transient_key, $json, 30 * MINUTE_IN_SECONDS );
                $this->redirect_with_notice(
                    array( 'page' => 'wpa9-export-import', 'pending_import' => wp_hash( $json ) ),
                    __( 'Import file validated. Review the preview below and confirm to proceed.', 'wpa9-gallery' )
                );
                break;

            case 'confirm_import_wpa9':
                if ( ! isset( $_POST['pending_import'] ) || ! isset( $_POST['confirm_import'] ) ) {
                    $this->redirect_with_notice( array( 'page' => 'wpa9-export-import' ), __( 'Invalid request.', 'wpa9-gallery' ), 'error' );
                }
                $pending_key   = sanitize_text_field( wp_unslash( $_POST['pending_import'] ) );
                $transient_key = 'wpa9_pending_import_' . sanitize_key( $pending_key );
                $json = get_transient( $transient_key );
                if ( ! $json ) {
                    $this->redirect_with_notice( array( 'page' => 'wpa9-export-import' ), __( 'Import session expired. Please upload the file again.', 'wpa9-gallery' ), 'error' );
                }
                delete_transient( $transient_key );
                $result = WPA9_Importer::import( $json );
                if ( is_wp_error( $result ) ) {
                    $this->redirect_with_notice( array( 'page' => 'wpa9-export-import' ), $result->get_error_message(), 'error' );
                }
                set_transient( 'wpa9_import_result_' . get_current_user_id(), $result, 5 * MINUTE_IN_SECONDS );
                $message = sprintf(
                    /* translators: 1: galleries count, 2: albums count, 3: images count */
                    __( 'Import complete. Created %1$d galleries, %2$d albums, %3$d images.', 'wpa9-gallery' ),
                    (int) $result['galleries'], (int) $result['albums'], (int) $result['images']
                );
                if ( ! empty( $result['warnings'] ) ) {
                    $message .= ' ' . sprintf(
                        /* translators: %d: count */
                        __( 'There were %d warnings.', 'wpa9-gallery' ),
                        count( $result['warnings'] )
                    );
                }
                $this->redirect_with_notice(
                    array( 'page' => 'wpa9-export-import', 'import_complete' => 1 ),
                    $message
                );
                break;
        }
    }

    public function page_dashboard() {
        include WPA9_DIR . 'admin/views/dashboard.php';
    }

    public function page_galleries() {
        include WPA9_DIR . 'admin/views/galleries-list.php';
    }

    public function page_gallery_edit() {
        include WPA9_DIR . 'admin/views/gallery-edit.php';
    }

    public function page_albums() {
        include WPA9_DIR . 'admin/views/albums-list.php';
    }

    public function page_album_edit() {
        include WPA9_DIR . 'admin/views/album-edit.php';
    }

    public function page_settings() {
        include WPA9_DIR . 'admin/views/settings.php';
    }

    public function page_import() {
        include WPA9_DIR . 'admin/views/import.php';
    }

    public function page_export_import() {
        include WPA9_DIR . 'admin/views/export-import.php';
    }
}
