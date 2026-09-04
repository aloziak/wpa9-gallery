<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$galleries_count = count( WPA9_Gallery::all() );
$albums_count    = count( WPA9_Album::all() );
$images_count    = WPA9_Image::count_all();

$pending_import = null;
$pending_key    = isset( $_GET['pending_import'] ) ? sanitize_text_field( wp_unslash( $_GET['pending_import'] ) ) : '';
if ( $pending_key !== '' ) {
    $transient_key = 'wpa9_pending_import_' . sanitize_key( $pending_key );
    $pending_import = get_transient( $transient_key );
    if ( ! $pending_import ) {
        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Import session expired. Please upload the file again.', 'wpa9-gallery' ) . '</p></div>';
    }
}
?>
<div class="wrap wpa9-wrap">
    <h1><?php esc_html_e( 'WPA9 Gallery — Export/Import', 'wpa9-gallery' ); ?></h1>

    <div class="wpa9-grid">
        <!-- LEFT COLUMN: EXPORT -->
        <div class="wpa9-main">
            <div class="wpa9-card wpa9-export-section">
                <h2><?php esc_html_e( 'Export WPA9 Gallery Data', 'wpa9-gallery' ); ?></h2>

                <p><?php esc_html_e( 'Export all galleries, albums, and image metadata as JSON. Image files must be copied manually (see instructions below).', 'wpa9-gallery' ); ?></p>

                <div class="notice notice-info inline">
                    <p><strong><?php esc_html_e( 'What will be exported:', 'wpa9-gallery' ); ?></strong></p>
                    <ul style="margin-left: 1.5em; margin-top: 0.5em;">
                        <li><?php esc_html_e( 'Gallery names, descriptions, settings', 'wpa9-gallery' ); ?></li>
                        <li><?php esc_html_e( 'Album names and descriptions', 'wpa9-gallery' ); ?></li>
                        <li><?php esc_html_e( 'Image filenames, captions, alt text, descriptions', 'wpa9-gallery' ); ?></li>
                        <li><?php esc_html_e( 'Album-to-gallery relationships', 'wpa9-gallery' ); ?></li>
                    </ul>
                    <p><strong><?php esc_html_e( 'What will NOT be exported:', 'wpa9-gallery' ); ?></strong></p>
                    <ul style="margin-left: 1.5em; margin-top: 0.5em;">
                        <li><?php esc_html_e( 'Image files (must be copied manually via FTP or server)', 'wpa9-gallery' ); ?></li>
                        <li><?php esc_html_e( 'Generated thumbnails (will be regenerated after import)', 'wpa9-gallery' ); ?></li>
                    </ul>
                </div>

                <div class="wpa9-stat-box" style="margin: 1.5em 0; padding: 1em; background: #f5f5f5; border-left: 4px solid #0073aa;">
                    <strong><?php esc_html_e( 'Current Data:', 'wpa9-gallery' ); ?></strong><br>
                    <?php printf( esc_html__( '%d Galleries', 'wpa9-gallery' ), $galleries_count ); ?><br>
                    <?php printf( esc_html__( '%d Albums', 'wpa9-gallery' ), $albums_count ); ?><br>
                    <?php printf( esc_html__( '%d Images', 'wpa9-gallery' ), $images_count ); ?>
                </div>

                <form method="post" style="margin-top: 1.5em;">
                    <?php wp_nonce_field( 'wpa9_export_wpa9' ); ?>
                    <input type="hidden" name="wpa9_action" value="export_wpa9">
                    <button type="submit" class="button button-primary">
                        <?php esc_html_e( 'Download JSON Export', 'wpa9-gallery' ); ?>
                    </button>
                    <p class="description"><?php esc_html_e( 'File will be named: wpa9-gallery-export-YYYY-MM-DD-HHMMSS.json', 'wpa9-gallery' ); ?></p>
                </form>
            </div>
        </div>

        <!-- RIGHT COLUMN: IMPORT -->
        <div class="wpa9-sidebar">
            <div class="wpa9-card wpa9-import-section">
                <h2><?php esc_html_e( 'Import WPA9 Gallery Data', 'wpa9-gallery' ); ?></h2>

                <div class="notice notice-warning inline">
                    <p><strong><?php esc_html_e( 'Important:', 'wpa9-gallery' ); ?></strong></p>
                    <ul style="margin-left: 1.5em; margin-top: 0.5em;">
                        <li><?php esc_html_e( 'Importing creates NEW galleries and albums (does not merge with existing ones)', 'wpa9-gallery' ); ?></li>
                        <li><?php esc_html_e( 'If a gallery slug already exists, a numeric suffix (-2, -3, etc.) will be added', 'wpa9-gallery' ); ?></li>
                        <li><?php esc_html_e( 'Always test on a staging site first', 'wpa9-gallery' ); ?></li>
                    </ul>
                </div>

                <?php if ( $pending_import ) : ?>
                    <?php $preview = WPA9_Importer::preview( $pending_import ); ?>
                    <div class="notice notice-success inline">
                        <p><strong><?php esc_html_e( 'Preview — This import will create:', 'wpa9-gallery' ); ?></strong></p>
                        <ul style="margin-left: 1.5em; margin-top: 0.5em;">
                            <li><?php printf( esc_html__( '%d Galleries', 'wpa9-gallery' ), $preview['galleries'] ); ?></li>
                            <li><?php printf( esc_html__( '%d Albums', 'wpa9-gallery' ), $preview['albums'] ); ?></li>
                            <li><?php printf( esc_html__( '%d Images', 'wpa9-gallery' ), $preview['images'] ); ?></li>
                        </ul>
                    </div>

                    <form method="post" style="margin-top: 1.5em;">
                        <?php wp_nonce_field( 'wpa9_confirm_import_wpa9' ); ?>
                        <input type="hidden" name="wpa9_action" value="confirm_import_wpa9">
                        <input type="hidden" name="pending_import" value="<?php echo esc_attr( $pending_key ); ?>">
                        <label style="display: flex; align-items: center; margin-bottom: 1em;">
                            <input type="checkbox" name="confirm_import" required>
                            <span style="margin-left: 0.5em;"><?php esc_html_e( 'I understand this will create new galleries and albums', 'wpa9-gallery' ); ?></span>
                        </label>
                        <button type="submit" class="button button-primary">
                            <?php esc_html_e( 'Confirm & Start Import', 'wpa9-gallery' ); ?>
                        </button>
                        <a href="<?php echo esc_url( add_query_arg( 'page', 'wpa9-export-import', admin_url( 'admin.php' ) ) ); ?>" class="button">
                            <?php esc_html_e( 'Cancel', 'wpa9-gallery' ); ?>
                        </a>
                    </form>
                <?php else : ?>
                    <form method="post" enctype="multipart/form-data" id="wpa9-import-form" style="margin-top: 1.5em;">
                        <?php wp_nonce_field( 'wpa9_import_wpa9' ); ?>
                        <input type="hidden" name="wpa9_action" value="import_wpa9">

                        <div class="wpa9-upload-zone" id="wpa9-import-dropzone">
                            <p class="wpa9-upload-zone__hint">
                                <strong><?php esc_html_e( 'Drag JSON file here or click to browse', 'wpa9-gallery' ); ?></strong>
                            </p>
                            <input type="file" name="import_file" id="wpa9-import-file" accept=".json" style="display: none;">
                            <p class="description"><?php esc_html_e( 'Max file size: 10 MB', 'wpa9-gallery' ); ?></p>
                        </div>

                        <p id="wpa9-file-selected" class="wpa9-file-selected" hidden>
                            ✓ <span id="wpa9-filename"></span>
                        </p>

                        <button type="submit" class="button button-primary wpa9-preview-btn" id="wpa9-preview-btn" disabled>
                            <?php esc_html_e( 'Preview Import', 'wpa9-gallery' ); ?>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- INSTRUCTIONS SECTION -->
    <div style="margin-top: 2em;">
        <h2><?php esc_html_e( 'How to Migrate Image Files Between WordPress Installations', 'wpa9-gallery' ); ?></h2>

        <details style="border: 1px solid #ddd; padding: 1em; border-radius: 4px; margin-bottom: 1em;">
            <summary style="cursor: pointer; font-weight: bold; padding: 0.5em 0;">
                <?php esc_html_e( 'Detailed Migration Instructions', 'wpa9-gallery' ); ?>
            </summary>

            <div style="margin-top: 1em; line-height: 1.6;">
                <ol>
                    <li style="margin-bottom: 1em;">
                        <strong><?php esc_html_e( 'Export the database data:', 'wpa9-gallery' ); ?></strong><br>
                        <?php esc_html_e( 'Click "Download JSON Export" above and save the file.', 'wpa9-gallery' ); ?>
                    </li>

                    <li style="margin-bottom: 1em;">
                        <strong><?php esc_html_e( 'On your SOURCE installation, note the gallery directories:', 'wpa9-gallery' ); ?></strong><br>
                        <code style="background: #f5f5f5; padding: 0.25em 0.5em;"><?php esc_html_e( 'wp-content/uploads/wpa9-galleries/', 'wpa9-gallery' ); ?></code><br>
                        <?php esc_html_e( 'You will see one folder per gallery (named by gallery slug, e.g., "my-gallery", "summer-2023", etc.)', 'wpa9-gallery' ); ?>
                    </li>

                    <li style="margin-bottom: 1em;">
                        <strong><?php esc_html_e( 'Copy image files to your destination:', 'wpa9-gallery' ); ?></strong><br>
                        <p><?php esc_html_e( 'Choose one of these methods:', 'wpa9-gallery' ); ?></p>
                        <ul style="margin-left: 2em;">
                            <li><strong>FTP:</strong> <?php esc_html_e( 'Download all gallery folders from source, upload to destination at the same path.', 'wpa9-gallery' ); ?></li>
                            <li><strong>SSH/Shell:</strong> <?php esc_html_e( 'Use rsync, scp, or tar+gzip to copy the wpa9-galleries folder.', 'wpa9-gallery' ); ?></li>
                            <li><strong>WordPress Backup Plugin:</strong> <?php esc_html_e( 'Use All-in-One WP Migration, UpdraftPlus, or Duplicator to migrate the entire uploads folder.', 'wpa9-gallery' ); ?></li>
                        </ul>
                    </li>

                    <li style="margin-bottom: 1em;">
                        <strong><?php esc_html_e( 'On your DESTINATION installation:', 'wpa9-gallery' ); ?></strong><br>
                        <?php esc_html_e( 'Upload the JSON export file using the Import tool above. This creates the gallery and album structure with the correct slug names.', 'wpa9-gallery' ); ?>
                    </li>

                    <li style="margin-bottom: 1em;">
                        <strong><?php esc_html_e( 'Verify image files are in place:', 'wpa9-gallery' ); ?></strong><br>
                        <?php esc_html_e( 'Ensure full-size files are at:', 'wpa9-gallery' ); ?> <code style="background: #f5f5f5; padding: 0.25em 0.5em;"><?php esc_html_e( 'wp-content/uploads/wpa9-galleries/{gallery-slug}/', 'wpa9-gallery' ); ?></code><br>
                        <?php esc_html_e( 'Thumbnails (optional — can be regenerated) at:', 'wpa9-gallery' ); ?> <code style="background: #f5f5f5; padding: 0.25em 0.5em;"><?php esc_html_e( 'wp-content/uploads/wpa9-galleries/{gallery-slug}/thumbs/thumbs_{filename}', 'wpa9-gallery' ); ?></code><br>
                        <span class="description"><?php esc_html_e( 'The "thumbs_" filename prefix matches NextGEN Gallery, so thumbnails copied from a NextGEN install will be picked up as-is.', 'wpa9-gallery' ); ?></span>
                    </li>

                    <li style="margin-bottom: 1em;">
                        <strong><?php esc_html_e( 'Regenerate thumbnails:', 'wpa9-gallery' ); ?></strong><br>
                        <?php esc_html_e( 'In the destination installation, go to each gallery, click "Edit Gallery", and click the "Regen thumb" button. This regenerates thumbnails from the full-size images.', 'wpa9-gallery' ); ?>
                    </li>

                    <li style="margin-bottom: 1em;">
                        <strong><?php esc_html_e( 'Verify the migration:', 'wpa9-gallery' ); ?></strong>
                        <ul style="margin-left: 2em;">
                            <li><?php esc_html_e( 'All galleries display with images correctly', 'wpa9-gallery' ); ?></li>
                            <li><?php esc_html_e( 'Album memberships are preserved', 'wpa9-gallery' ); ?></li>
                            <li><?php esc_html_e( 'Image captions, alt text, and descriptions are intact', 'wpa9-gallery' ); ?></li>
                        </ul>
                    </li>
                </ol>
            </div>
        </details>

        <div class="notice notice-info inline">
            <p><strong><?php esc_html_e( 'Need Help?', 'wpa9-gallery' ); ?></strong></p>
            <ul style="margin-left: 1.5em;">
                <li><?php esc_html_e( 'Max file size for import: 10 MB (typical 1000 galleries = 1-2 MB)', 'wpa9-gallery' ); ?></li>
                <li><?php esc_html_e( 'Thumbnail regeneration may take time for large galleries — be patient', 'wpa9-gallery' ); ?></li>
                <li><?php esc_html_e( 'If image files already exist but thumbnails are missing, just click "Regen thumb" on each gallery', 'wpa9-gallery' ); ?></li>
            </ul>
        </div>
    </div>
</div>
