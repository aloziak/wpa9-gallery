<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$galleries = WPA9_Gallery::all();
$albums    = WPA9_Album::all();
$album_map = array();
foreach ( $albums as $a ) {
    $album_map[ (int) $a->id ] = $a->name;
}
?>
<div class="wrap wpa9-wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e( 'Galleries', 'wpa9-gallery' ); ?></h1>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpa9-gallery-edit' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New', 'wpa9-gallery' ); ?></a>
    <hr class="wp-header-end">

    <table class="wp-list-table widefat fixed striped wpa9-galleries">
        <thead>
            <tr>
                <th class="manage-column column-primary"><?php esc_html_e( 'Name', 'wpa9-gallery' ); ?></th>
                <th class="manage-column"><?php esc_html_e( 'Slug', 'wpa9-gallery' ); ?></th>
                <th class="manage-column"><?php esc_html_e( 'Albums', 'wpa9-gallery' ); ?></th>
                <th class="manage-column"><?php esc_html_e( 'Author', 'wpa9-gallery' ); ?></th>
                <th class="manage-column"><?php esc_html_e( 'Images', 'wpa9-gallery' ); ?></th>
                <th class="manage-column"><?php esc_html_e( 'Shortcode', 'wpa9-gallery' ); ?></th>
                <th class="manage-column"><?php esc_html_e( 'Created', 'wpa9-gallery' ); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php if ( empty( $galleries ) ) : ?>
            <tr><td colspan="7"><?php esc_html_e( 'No galleries yet.', 'wpa9-gallery' ); ?> <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpa9-gallery-edit' ) ); ?>"><?php esc_html_e( 'Create the first one.', 'wpa9-gallery' ); ?></a></td></tr>
        <?php else : foreach ( $galleries as $g ) :
            $edit_url = admin_url( 'admin.php?page=wpa9-gallery-edit&id=' . (int) $g->id );
            $count    = WPA9_Gallery::image_count( (int) $g->id );
        ?>
            <tr>
                <td class="column-primary">
                    <strong><a href="<?php echo esc_url( $edit_url ); ?>"><?php echo esc_html( $g->name ); ?></a></strong>
                    <div class="row-actions">
                        <span class="edit"><a href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( 'Edit', 'wpa9-gallery' ); ?></a> | </span>
                        <span class="trash">
                            <form method="post" style="display:inline;" data-wpa9-confirm="<?php echo esc_attr( __( 'Delete this gallery and all its images? This cannot be undone.', 'wpa9-gallery' ) ); ?>">
                                <?php wp_nonce_field( 'wpa9_delete_gallery' ); ?>
                                <input type="hidden" name="wpa9_action" value="delete_gallery">
                                <input type="hidden" name="id" value="<?php echo (int) $g->id; ?>">
                                <button type="submit" class="button-link delete"><?php esc_html_e( 'Delete', 'wpa9-gallery' ); ?></button>
                            </form>
                        </span>
                    </div>
                </td>
                <td><code><?php echo esc_html( $g->slug ); ?></code></td>
                <td><?php
                    $names = array();
                    foreach ( WPA9_Gallery::album_ids( (int) $g->id ) as $aid ) {
                        if ( isset( $album_map[ $aid ] ) ) {
                            $names[] = $album_map[ $aid ];
                        }
                    }
                    echo $names ? esc_html( implode( ', ', $names ) ) : '—';
                ?></td>
                <td><?php echo $g->author_name ? esc_html( $g->author_name ) : '—'; ?></td>
                <td><?php echo (int) $count; ?></td>
                <td><code>[wpa9_gallery id="<?php echo (int) $g->id; ?>"]</code></td>
                <td><?php echo esc_html( mysql2date( get_option( 'date_format' ), $g->created_at ) ); ?></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
