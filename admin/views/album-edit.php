<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$id     = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;
$album  = $id ? WPA9_Album::get( $id ) : null;
$is_new = ! $album;

$name        = $album ? $album->name        : '';
$description = $album ? $album->description : '';
$sort_order  = $album ? (int) $album->sort_order : 0;

// Membership lists (only when editing an existing album).
$all_galleries = $album ? WPA9_Gallery::all() : array();
$member_ids    = $album ? WPA9_Album::gallery_ids( (int) $album->id ) : array();
$member_set    = array_flip( $member_ids );

// Members in junction order.
$members = array();
if ( $album && $member_ids ) {
    $by_id = array();
    foreach ( $all_galleries as $g ) {
        $by_id[ (int) $g->id ] = $g;
    }
    foreach ( $member_ids as $mid ) {
        if ( isset( $by_id[ $mid ] ) ) {
            $members[] = $by_id[ $mid ];
        }
    }
}

// Pool: everything not currently a member, sorted by name.
$pool = array();
foreach ( $all_galleries as $g ) {
    if ( ! isset( $member_set[ (int) $g->id ] ) ) {
        $pool[] = $g;
    }
}
?>
<div class="wrap wpa9-wrap">
    <h1 class="wp-heading-inline">
        <?php echo $is_new ? esc_html__( 'Add Album', 'wpa9-gallery' ) : esc_html__( 'Edit Album', 'wpa9-gallery' ); ?>
    </h1>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpa9-albums' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Back to list', 'wpa9-gallery' ); ?></a>
    <hr class="wp-header-end">

    <form method="post">
        <?php wp_nonce_field( 'wpa9_save_album' ); ?>
        <input type="hidden" name="wpa9_action" value="save_album">
        <input type="hidden" name="id" value="<?php echo (int) ( $album ? $album->id : 0 ); ?>">

        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><label for="wpa9-album-name"><?php esc_html_e( 'Name', 'wpa9-gallery' ); ?></label></th>
                <td><input id="wpa9-album-name" name="name" type="text" class="regular-text" required value="<?php echo esc_attr( $name ); ?>"></td>
            </tr>
            <tr>
                <th scope="row"><label for="wpa9-album-desc"><?php esc_html_e( 'Short description', 'wpa9-gallery' ); ?></label></th>
                <td><textarea id="wpa9-album-desc" name="description" rows="4" class="large-text"><?php echo esc_textarea( $description ); ?></textarea></td>
            </tr>
            <tr>
                <th scope="row"><label for="wpa9-album-sort"><?php esc_html_e( 'Sort order', 'wpa9-gallery' ); ?></label></th>
                <td><input id="wpa9-album-sort" name="sort_order" type="number" step="1" value="<?php echo (int) $sort_order; ?>" class="small-text"></td>
            </tr>
        </table>

        <p><button type="submit" class="button button-primary"><?php echo $is_new ? esc_html__( 'Create album', 'wpa9-gallery' ) : esc_html__( 'Save changes', 'wpa9-gallery' ); ?></button></p>
    </form>

    <?php if ( $album ) : ?>
        <hr>
        <h2><?php esc_html_e( 'Galleries in this album', 'wpa9-gallery' ); ?></h2>
        <p class="description">
            <?php esc_html_e( 'Drag galleries between the two columns. The right column reflects the order they appear inside the album.', 'wpa9-gallery' ); ?>
        </p>

        <div class="wpa9-membership" data-album-id="<?php echo (int) $album->id; ?>">
            <div class="wpa9-membership__col">
                <h3>
                    <?php esc_html_e( 'Available galleries', 'wpa9-gallery' ); ?>
                    <span class="wpa9-membership__count">(<span class="wpa9-mc-pool"><?php echo count( $pool ); ?></span>)</span>
                </h3>
                <ul class="wpa9-membership__list wpa9-membership__pool"
                    data-role="pool"
                    data-empty="<?php esc_attr_e( 'All galleries are already in this album.', 'wpa9-gallery' ); ?>">
                    <?php foreach ( $pool as $g ) :
                        $count = WPA9_Gallery::image_count( (int) $g->id );
                    ?>
                        <li class="wpa9-membership__item" data-id="<?php echo (int) $g->id; ?>">
                            <span class="wpa9-membership__handle dashicons dashicons-menu-alt2" aria-hidden="true"></span>
                            <span class="wpa9-membership__name"><?php echo esc_html( $g->name ); ?></span>
                            <span class="wpa9-membership__meta"><?php
                                /* translators: %d: image count */
                                printf( esc_html( _n( '%d image', '%d images', $count, 'wpa9-gallery' ) ), (int) $count );
                            ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="wpa9-membership__col">
                <h3>
                    <?php esc_html_e( 'In this album', 'wpa9-gallery' ); ?>
                    <span class="wpa9-membership__count">(<span class="wpa9-mc-members"><?php echo count( $members ); ?></span>)</span>
                </h3>
                <ul class="wpa9-membership__list wpa9-membership__members"
                    data-role="members"
                    data-empty="<?php esc_attr_e( 'Drag galleries here to add them to this album.', 'wpa9-gallery' ); ?>">
                    <?php foreach ( $members as $g ) :
                        $count = WPA9_Gallery::image_count( (int) $g->id );
                    ?>
                        <li class="wpa9-membership__item" data-id="<?php echo (int) $g->id; ?>">
                            <span class="wpa9-membership__handle dashicons dashicons-menu-alt2" aria-hidden="true"></span>
                            <span class="wpa9-membership__name"><?php echo esc_html( $g->name ); ?></span>
                            <span class="wpa9-membership__meta"><?php
                                /* translators: %d: image count */
                                printf( esc_html( _n( '%d image', '%d images', $count, 'wpa9-gallery' ) ), (int) $count );
                            ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="wpa9-membership__status" aria-live="polite"></div>
        </div>
    <?php endif; ?>
</div>
