<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WPA9_Install {

    const DB_VERSION = '1.4.0';

    /** Allowed ratio tokens. Empty string = inherit (on galleries); 'none' = original ratio. */
    public static function ratios() {
        return array(
            'none'  => __( 'No ratio (original)', 'wpa9-gallery' ),
            '3x2'   => '3:2',
            '4x3'   => '4:3',
            '16x9'  => '16:9',
            '16x10' => '16:10',
            '21x9'  => '21:9',
            '1x1'   => '1:1',
        );
    }
    const OPT_DB_VERSION = 'wpa9_db_version';
    const OPT_SETTINGS   = 'wpa9_settings';

    public static function activate() {
        self::create_tables();
        self::migrate();
        self::ensure_base_dir();
        self::seed_defaults();
        update_option( self::OPT_DB_VERSION, self::DB_VERSION );
        flush_rewrite_rules();
    }

    public static function deactivate() {
        flush_rewrite_rules();
    }

    public static function maybe_upgrade() {
        if ( get_option( self::OPT_DB_VERSION ) !== self::DB_VERSION ) {
            self::create_tables();
            self::migrate();
            self::ensure_base_dir();
            self::seed_defaults();
            update_option( self::OPT_DB_VERSION, self::DB_VERSION );
        }
    }

    /**
     * Idempotent one-shot migrations. Runs after dbDelta so new tables exist.
     */
    public static function migrate() {
        global $wpdb;

        // 1.3.0: galleries.album_id → wpa9_album_galleries (many-to-many)
        $galleries_t = $wpdb->prefix . 'wpa9_galleries';
        $junction_t  = $wpdb->prefix . 'wpa9_album_galleries';

        $has_album_id = $wpdb->get_var( $wpdb->prepare(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'album_id'",
            DB_NAME, $galleries_t
        ) );
        if ( $has_album_id === 'album_id' ) {
            $rows = $wpdb->get_results(
                "SELECT id, album_id FROM {$galleries_t} WHERE album_id IS NOT NULL AND album_id > 0"
            );
            foreach ( $rows as $r ) {
                $wpdb->query( $wpdb->prepare(
                    "INSERT IGNORE INTO {$junction_t} (album_id, gallery_id, sort_order) VALUES (%d, %d, %d)",
                    (int) $r->album_id, (int) $r->id, 0
                ) );
            }
            $wpdb->query( "ALTER TABLE {$galleries_t} DROP COLUMN album_id" );
        }

        // 1.4.0: rename existing thumb files to 'thumbs_' prefix (NextGEN-compatible).
        self::migrate_thumb_prefix();
    }

    private static function migrate_thumb_prefix() {
        $base = WPA9_Storage::base_dir();
        if ( ! is_dir( $base ) ) {
            return;
        }
        $gallery_dirs = @scandir( $base );
        if ( ! is_array( $gallery_dirs ) ) {
            return;
        }
        foreach ( $gallery_dirs as $gallery_dir ) {
            if ( $gallery_dir === '.' || $gallery_dir === '..' ) {
                continue;
            }
            $thumbs = trailingslashit( $base ) . $gallery_dir . '/thumbs';
            if ( ! is_dir( $thumbs ) ) {
                continue;
            }
            $files = @scandir( $thumbs );
            if ( ! is_array( $files ) ) {
                continue;
            }
            foreach ( $files as $f ) {
                if ( $f === '.' || $f === '..' || $f === 'index.php' ) {
                    continue;
                }
                if ( strpos( $f, 'thumbs_' ) === 0 ) {
                    continue;
                }
                $old = trailingslashit( $thumbs ) . $f;
                $new = trailingslashit( $thumbs ) . 'thumbs_' . $f;
                if ( is_file( $old ) && ! file_exists( $new ) ) {
                    @rename( $old, $new );
                }
            }
        }
    }

    public static function seed_defaults() {
        if ( false === get_option( self::OPT_SETTINGS ) ) {
            add_option( self::OPT_SETTINGS, self::default_settings() );
        }
    }

    public static function default_settings() {
        return array(
            'fullsize_width'  => 1600,
            'fullsize_height' => 1600,
            'thumb_width'     => 600,
            'thumb_height'    => 600,
            'ratio'           => 'none',
            'jpeg_quality'    => 85,
            'data_atts'       => '',
        );
    }

    public static function get_settings() {
        $opts = get_option( self::OPT_SETTINGS, array() );
        return wp_parse_args( is_array( $opts ) ? $opts : array(), self::default_settings() );
    }

    private static function create_tables() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();
        $albums          = $wpdb->prefix . 'wpa9_albums';
        $galleries       = $wpdb->prefix . 'wpa9_galleries';
        $images          = $wpdb->prefix . 'wpa9_images';

        $sql_albums = "CREATE TABLE $albums (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(190) NOT NULL,
            slug VARCHAR(190) NOT NULL,
            description TEXT NULL,
            sort_order INT(11) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY slug (slug)
        ) $charset_collate;";

        $sql_galleries = "CREATE TABLE $galleries (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(190) NOT NULL,
            slug VARCHAR(190) NOT NULL,
            description TEXT NULL,
            author_name VARCHAR(190) NULL,
            fullsize_width INT(11) NOT NULL DEFAULT 1600,
            fullsize_height INT(11) NOT NULL DEFAULT 1600,
            thumb_width INT(11) NOT NULL DEFAULT 600,
            thumb_height INT(11) NOT NULL DEFAULT 600,
            ratio VARCHAR(20) NOT NULL DEFAULT '',
            data_atts TEXT NULL,
            sort_order INT(11) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY slug (slug)
        ) $charset_collate;";

        $album_galleries = $wpdb->prefix . 'wpa9_album_galleries';
        $sql_album_galleries = "CREATE TABLE $album_galleries (
            album_id BIGINT(20) UNSIGNED NOT NULL,
            gallery_id BIGINT(20) UNSIGNED NOT NULL,
            sort_order INT(11) NOT NULL DEFAULT 0,
            PRIMARY KEY  (album_id, gallery_id),
            KEY gallery_id (gallery_id),
            KEY sort_order (sort_order)
        ) $charset_collate;";

        $sql_images = "CREATE TABLE $images (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            gallery_id BIGINT(20) UNSIGNED NOT NULL,
            filename VARCHAR(255) NOT NULL,
            caption VARCHAR(500) NULL,
            description TEXT NULL,
            alt_text VARCHAR(255) NULL,
            width INT(11) NOT NULL DEFAULT 0,
            height INT(11) NOT NULL DEFAULT 0,
            filesize BIGINT(20) NOT NULL DEFAULT 0,
            sort_order INT(11) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY gallery_id (gallery_id),
            KEY sort_order (sort_order)
        ) $charset_collate;";

        dbDelta( $sql_albums );
        dbDelta( $sql_galleries );
        dbDelta( $sql_album_galleries );
        dbDelta( $sql_images );
    }

    private static function ensure_base_dir() {
        $base = WPA9_Storage::base_dir();
        if ( ! file_exists( $base ) ) {
            wp_mkdir_p( $base );
        }
        $index = trailingslashit( $base ) . 'index.php';
        if ( ! file_exists( $index ) ) {
            @file_put_contents( $index, "<?php // Silence is golden.\n" );
        }
    }
}
