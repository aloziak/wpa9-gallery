<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$albums = WPA9_Album::all();
?>
<div class="wrap wpa9-wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e( 'Albums', 'wpa9-gallery' ); ?></h1>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpa9-album-edit' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New', 'wpa9-gallery' ); ?></a>
    <hr class="wp-header-end">

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th class="manage-column column-primary"><?php esc_html_e( 'Name', 'wpa9-gallery' ); ?></th>
                <th class="manage-column"><?php esc_html_e( 'Slug', 'wpa9-gallery' ); ?></th>
                <th class="manage-column"><?php esc_html_e( 'Galleries', 'wpa9-gallery' ); ?></th>
                <th class="manage-column"><?php esc_html_e( 'Shortcode', 'wpa9-gallery' ); ?></th>
                <th class="manage-column"><?php esc_html_e( 'Created', 'wpa9-gallery' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( empty( $albums ) ) : ?>
                <tr><td colspan="5"><?php esc_html_e( 'No albums yet.', 'wpa9-gallery' ); ?></td></tr>
            <?php else : foreach ( $albums as $a ) :
                $edit_url = admin_url( 'admin.php?page=wpa9-album-edit&id=' . (int) $a->id );
                $count    = WPA9_Album::gallery_count( (int) $a->id );
            ?>
                <tr>
                    <td class="column-primary">
                        <strong><a href="<?php echo esc_url( $edit_url ); ?>"><?php echo esc_html( $a->name ); ?></a></strong>
                        <div class="row-actions">
                            <span class="edit"><a href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( 'Edit', 'wpa9-gallery' ); ?></a> | </span>
                            <span class="trash">
                                <form method="post" style="display:inline;" onsubmit="return confirm('<?php echo esc_js( __( 'Delete this album? Galleries inside it will be detached, not deleted.', 'wpa9-gallery' ) ); ?>');">
                                    <?php wp_nonce_field( 'wpa9_delete_album' ); ?>
                                    <input type="hidden" name="wpa9_action" value="delete_album">
                                    <input type="hidden" name="id" value="<?php echo (int) $a->id; ?>">
                                    <button type="submit" class="button-link delete"><?php esc_html_e( 'Delete', 'wpa9-gallery' ); ?></button>
                                </form>
                            </span>
                        </div>
                    </td>
                    <td><code><?php echo esc_html( $a->slug ); ?></code></td>
                    <td><?php echo (int) $count; ?></td>
                    <td><code>[wpa9_album id="<?php echo (int) $a->id; ?>"]</code></td>
                    <td><?php echo esc_html( mysql2date( get_option( 'date_format' ), $a->created_at ) ); ?></td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
