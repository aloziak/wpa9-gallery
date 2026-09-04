<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$available = class_exists( 'WPA9_Importer_NextGEN' ) && WPA9_Importer_NextGEN::is_available();
$preview   = $available ? WPA9_Importer_NextGEN::preview() : null;

$result = null;
if ( isset( $_GET['imported'] ) && absint( wp_unslash( $_GET['imported'] ) ) ) {
    $transient_key = 'wpa9_import_result_' . get_current_user_id();
    $stored        = get_transient( $transient_key );
    if ( is_array( $stored ) ) {
        $result = $stored;
        delete_transient( $transient_key );
    }
}
?>
<div class="wrap wpa9-wrap">
    <h1><?php esc_html_e( 'WPA9 Gallery — Import', 'wpa9-gallery' ); ?></h1>

    <h2><?php esc_html_e( 'NextGEN Gallery', 'wpa9-gallery' ); ?></h2>

    <?php if ( ! $available ) : ?>
        <p><?php esc_html_e( 'NextGEN Gallery tables were not found in this database. Nothing to import.', 'wpa9-gallery' ); ?></p>
    <?php else : ?>
        <p><?php esc_html_e( 'NextGEN Gallery data detected:', 'wpa9-gallery' ); ?></p>
        <ul class="ul-disc" style="margin-left:1.5em;">
            <li><?php
                /* translators: %d: count */
                printf( esc_html( _n( '%d gallery', '%d galleries', $preview['galleries'], 'wpa9-gallery' ) ), (int) $preview['galleries'] );
            ?></li>
            <li><?php
                /* translators: %d: count */
                printf( esc_html( _n( '%d image', '%d images', $preview['pictures'], 'wpa9-gallery' ) ), (int) $preview['pictures'] );
            ?></li>
            <li><?php
                /* translators: %d: count */
                printf( esc_html( _n( '%d album', '%d albums', $preview['albums'], 'wpa9-gallery' ) ), (int) $preview['albums'] );
            ?></li>
        </ul>

        <div class="notice notice-warning inline">
            <p><strong><?php esc_html_e( 'What this importer does — and what it does not:', 'wpa9-gallery' ); ?></strong></p>
            <ul class="ul-disc" style="margin-left:1.5em;">
                <li><?php esc_html_e( 'Reads database records from NextGEN. NextGEN data is never modified.', 'wpa9-gallery' ); ?></li>
                <li><?php esc_html_e( 'Creates WPA9 galleries, image rows (filenames + captions/alt/sort), and albums.', 'wpa9-gallery' ); ?></li>
                <li><?php esc_html_e( 'Does NOT copy any image files. After import you will see the source / destination paths below — copy the files yourself, then regenerate thumbnails inside each gallery.', 'wpa9-gallery' ); ?></li>
                <li><?php esc_html_e( 'A gallery can belong to multiple albums — all NextGEN album memberships are preserved.', 'wpa9-gallery' ); ?></li>
                <li><?php esc_html_e( 'Re-running the import creates duplicate galleries (slug suffix -2, -3, …).', 'wpa9-gallery' ); ?></li>
            </ul>
        </div>

        <form method="post" data-wpa9-confirm="<?php echo esc_attr( __( 'Run NextGEN import now?', 'wpa9-gallery' ) ); ?>">
            <?php wp_nonce_field( 'wpa9_import_nextgen' ); ?>
            <input type="hidden" name="wpa9_action" value="import_nextgen">
            <p><button type="submit" class="button button-primary"><?php esc_html_e( 'Import from NextGEN', 'wpa9-gallery' ); ?></button></p>
        </form>
    <?php endif; ?>

    <?php if ( $result ) : ?>
        <hr>
        <h2><?php esc_html_e( 'Import result', 'wpa9-gallery' ); ?></h2>
        <p>
            <?php
            /* translators: 1: galleries, 2: images, 3: albums */
            printf(
                esc_html__( 'Imported %1$d galleries, %2$d images, %3$d albums.', 'wpa9-gallery' ),
                (int) $result['galleries'],
                (int) $result['pictures'],
                (int) $result['albums']
            );
            ?>
        </p>

        <?php if ( ! empty( $result['warnings'] ) ) : ?>
            <div class="notice notice-warning inline">
                <p><strong><?php esc_html_e( 'Warnings:', 'wpa9-gallery' ); ?></strong></p>
                <ul class="ul-disc" style="margin-left:1.5em;">
                    <?php foreach ( $result['warnings'] as $w ) : ?>
                        <li><?php echo esc_html( $w ); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ( ! empty( $result['file_map'] ) ) : ?>
            <h3><?php esc_html_e( 'Files to copy', 'wpa9-gallery' ); ?></h3>
            <p>
                <?php esc_html_e( 'Copy the contents of each "Source" folder into the matching "Destination" folder, then open each gallery and use "Regen thumb" on every image (NextGEN uses a different thumbnail naming convention, so regenerating is the cleanest path).', 'wpa9-gallery' ); ?>
            </p>

            <table class="wp-list-table widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Gallery', 'wpa9-gallery' ); ?></th>
                        <th><?php esc_html_e( 'Source (NextGEN)', 'wpa9-gallery' ); ?></th>
                        <th><?php esc_html_e( 'Destination (WPA9)', 'wpa9-gallery' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $result['file_map'] as $row ) : ?>
                        <tr>
                            <td><?php echo esc_html( $row['name'] ); ?></td>
                            <td><code><?php echo esc_html( $row['source'] !== '' ? $row['source'] : '—' ); ?></code></td>
                            <td><code><?php echo esc_html( $row['destination'] ); ?></code></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h3><?php esc_html_e( 'Suggested shell commands', 'wpa9-gallery' ); ?></h3>
            <p><?php esc_html_e( 'Review and adjust before running. These commands copy original files only — thumbnails will need regenerating from the WPA9 admin.', 'wpa9-gallery' ); ?></p>
            <pre style="background:#fff;border:1px solid #c3c4c7;padding:12px;overflow:auto;max-height:320px;"><?php
                foreach ( $result['file_map'] as $row ) {
                    if ( $row['source'] === '' ) {
                        continue;
                    }
                    $cmd  = 'mkdir -p ' . escapeshellarg( $row['destination'] ) . ' && ';
                    $cmd .= 'cp -R ' . escapeshellarg( rtrim( $row['source'], '/' ) ) . '/. ';
                    $cmd .= escapeshellarg( rtrim( $row['destination'], '/' ) ) . '/';
                    echo esc_html( $cmd ) . "\n";
                }
            ?></pre>
        <?php endif; ?>
    <?php endif; ?>
</div>
