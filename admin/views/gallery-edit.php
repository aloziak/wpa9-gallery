<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$id        = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0;
$gallery   = $id ? WPA9_Gallery::get( $id ) : null;
$is_new    = ! $gallery;
$settings  = WPA9_Install::get_settings();
$images    = $gallery ? WPA9_Image::for_gallery( $gallery->id ) : array();

$name        = $gallery ? $gallery->name        : '';
$description = $gallery ? $gallery->description : '';
$author_name = $gallery ? $gallery->author_name : '';
$fw          = $gallery ? (int) $gallery->fullsize_width  : (int) $settings['fullsize_width'];
$fh          = $gallery ? (int) $gallery->fullsize_height : (int) $settings['fullsize_height'];
$tw          = $gallery ? (int) $gallery->thumb_width  : (int) $settings['thumb_width'];
$th          = $gallery ? (int) $gallery->thumb_height : (int) $settings['thumb_height'];
$ratio       = $gallery ? (string) $gallery->ratio : '';
$data_atts   = $gallery ? (string) $gallery->data_atts : '';
$sort_order  = $gallery ? (int) $gallery->sort_order : 0;
$ratios      = WPA9_Install::ratios();
?>
<div class="wrap wpa9-wrap">
    <h1 class="wp-heading-inline">
        <?php echo $is_new ? esc_html__( 'Add Gallery', 'wpa9-gallery' ) : esc_html__( 'Edit Gallery', 'wpa9-gallery' ); ?>
    </h1>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpa9-galleries' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Back to list', 'wpa9-gallery' ); ?></a>
    <hr class="wp-header-end">

    <form method="post" class="wpa9-gallery-form">
        <?php wp_nonce_field( 'wpa9_save_gallery' ); ?>
        <input type="hidden" name="wpa9_action" value="save_gallery">
        <input type="hidden" name="id" value="<?php echo (int) ( $gallery ? $gallery->id : 0 ); ?>">

        <div class="wpa9-grid">
            <div class="wpa9-col-main">
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="wpa9-name"><?php esc_html_e( 'Name', 'wpa9-gallery' ); ?></label></th>
                        <td><input id="wpa9-name" name="name" type="text" class="regular-text" required value="<?php echo esc_attr( $name ); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wpa9-description"><?php esc_html_e( 'Description', 'wpa9-gallery' ); ?></label></th>
                        <td><textarea id="wpa9-description" name="description" rows="4" class="large-text"><?php echo esc_textarea( $description ); ?></textarea></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wpa9-author"><?php esc_html_e( 'Photo gallery author', 'wpa9-gallery' ); ?></label></th>
                        <td><input id="wpa9-author" name="author_name" type="text" class="regular-text" value="<?php echo esc_attr( $author_name ); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Fullsize image (max W × H)', 'wpa9-gallery' ); ?></th>
                        <td>
                            <label><?php esc_html_e( 'Width', 'wpa9-gallery' ); ?>
                                <input name="fullsize_width" type="number" min="0" step="1" value="<?php echo (int) $fw; ?>" class="small-text"></label>
                            <label><?php esc_html_e( 'Height', 'wpa9-gallery' ); ?>
                                <input name="fullsize_height" type="number" min="0" step="1" value="<?php echo (int) $fh; ?>" class="small-text"></label>
                            <p class="description"><?php esc_html_e( 'Leave one dimension at 0 to scale proportionally to the other (e.g. 600 × 0 = max 600px wide, height auto). Set both to hard-crop to that exact size. Affects new uploads only.', 'wpa9-gallery' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Thumbnail (max W × H)', 'wpa9-gallery' ); ?></th>
                        <td>
                            <label><?php esc_html_e( 'Width', 'wpa9-gallery' ); ?>
                                <input name="thumb_width" type="number" min="0" step="1" value="<?php echo (int) $tw; ?>" class="small-text"></label>
                            <label><?php esc_html_e( 'Height', 'wpa9-gallery' ); ?>
                                <input name="thumb_height" type="number" min="0" step="1" value="<?php echo (int) $th; ?>" class="small-text"></label>
                            <p class="description"><?php esc_html_e( 'Leave one dimension at 0 to scale proportionally (e.g. 0 × 160 = 160px tall, width auto). Set both to hard-crop to that exact size (e.g. 240 × 160). After changing this, save the gallery, then select images and use "Regenerate thumbnails" (or "Regen thumb" per image) to rebuild at the new size.', 'wpa9-gallery' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wpa9-ratio"><?php esc_html_e( 'Display ratio', 'wpa9-gallery' ); ?></label></th>
                        <td>
                            <select id="wpa9-ratio" name="ratio">
                                <option value="" <?php selected( $ratio, '' ); ?>>
                                    <?php
                                    /* translators: %s: ratio name from global settings */
                                    printf(
                                        esc_html__( 'Inherit from global (%s)', 'wpa9-gallery' ),
                                        esc_html( isset( $ratios[ $settings['ratio'] ] ) ? $ratios[ $settings['ratio'] ] : $settings['ratio'] )
                                    );
                                    ?>
                                </option>
                                <?php foreach ( $ratios as $key => $label ) : ?>
                                    <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $ratio, $key ); ?>><?php echo esc_html( $label ); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php esc_html_e( 'Visual crop only — files stay at their proportional dimensions. CSS object-fit:cover handles the framing.', 'wpa9-gallery' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wpa9-data-atts"><?php esc_html_e( 'Link data-* attributes', 'wpa9-gallery' ); ?></label></th>
                        <td>
                            <textarea id="wpa9-data-atts" name="data_atts" rows="4" class="large-text code" placeholder="<?php echo esc_attr( $settings['data_atts'] !== '' ? sprintf( __( 'Inherits global: %s', 'wpa9-gallery' ), str_replace( "\n", ' | ', $settings['data_atts'] ) ) : __( 'fancybox={slug}', 'wpa9-gallery' ) ); ?>"><?php echo esc_textarea( $data_atts ); ?></textarea>
                            <p class="description">
                                <?php esc_html_e( 'One key=value per line, rendered as data-{key}="{value}" on each image link. Tokens: {slug}, {id}, {name}.', 'wpa9-gallery' ); ?>
                                <?php if ( $settings['data_atts'] !== '' ) : ?>
                                    <br><em><?php esc_html_e( 'Leave empty to inherit the global setting.', 'wpa9-gallery' ); ?></em>
                                <?php endif; ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wpa9-sort"><?php esc_html_e( 'Sort order', 'wpa9-gallery' ); ?></label></th>
                        <td><input id="wpa9-sort" name="sort_order" type="number" step="1" value="<?php echo (int) $sort_order; ?>" class="small-text"></td>
                    </tr>
                </table>

                <p>
                    <button type="submit" class="button button-primary"><?php echo $is_new ? esc_html__( 'Create gallery', 'wpa9-gallery' ) : esc_html__( 'Save changes', 'wpa9-gallery' ); ?></button>
                </p>
            </div>

            <div class="wpa9-col-side">
                <?php if ( $gallery ) : ?>
                    <div class="wpa9-card">
                        <h3><?php esc_html_e( 'Embed', 'wpa9-gallery' ); ?></h3>
                        <p><?php esc_html_e( 'Use this shortcode anywhere on your site:', 'wpa9-gallery' ); ?></p>
                        <p><input type="text" class="widefat wpa9-select-on-focus" readonly value="[wpa9_gallery id=&quot;<?php echo (int) $gallery->id; ?>&quot;]"></p>
                        <p class="description"><?php esc_html_e( 'Optional: template="gallery-masonry" columns="3" gap="12"', 'wpa9-gallery' ); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </form>

    <?php if ( $gallery ) : ?>
        <hr>
        <h2><?php esc_html_e( 'Images', 'wpa9-gallery' ); ?></h2>

        <div class="wpa9-uploader"
             data-gallery-id="<?php echo (int) $gallery->id; ?>"
             data-max-size="<?php echo (int) wp_max_upload_size(); ?>">
            <div class="wpa9-drop">
                <p class="wpa9-drop__hint"><?php esc_html_e( 'Drag image files here, or click to select', 'wpa9-gallery' ); ?></p>
                <button type="button" class="button button-primary wpa9-pick-files"><?php esc_html_e( 'Select images', 'wpa9-gallery' ); ?></button>
            </div>
            <ul class="wpa9-upload-queue"></ul>
        </div>

        <div class="wpa9-images-toolbar">
            <label>
                <input type="checkbox" class="wpa9-select-all">
                <?php esc_html_e( 'Select all', 'wpa9-gallery' ); ?>
            </label>
            <button type="button" class="button wpa9-bulk-delete"><?php esc_html_e( 'Delete selected', 'wpa9-gallery' ); ?></button>
            <button type="button" class="button wpa9-bulk-regen"><?php esc_html_e( 'Regenerate thumbnails', 'wpa9-gallery' ); ?></button>
            <span class="wpa9-toolbar-hint"><?php esc_html_e( 'Drag to reorder. Edit captions, alt text, description inline.', 'wpa9-gallery' ); ?></span>
            <span class="wpa9-view-toggle" role="group" aria-label="<?php esc_attr_e( 'View mode', 'wpa9-gallery' ); ?>">
                <button type="button" class="button wpa9-view-mode" data-mode="grid" title="<?php esc_attr_e( 'Grid view', 'wpa9-gallery' ); ?>" aria-pressed="true">
                    <span class="dashicons dashicons-grid-view" aria-hidden="true"></span>
                    <span class="screen-reader-text"><?php esc_html_e( 'Grid', 'wpa9-gallery' ); ?></span>
                </button>
                <button type="button" class="button wpa9-view-mode" data-mode="list" title="<?php esc_attr_e( 'List view', 'wpa9-gallery' ); ?>" aria-pressed="false">
                    <span class="dashicons dashicons-list-view" aria-hidden="true"></span>
                    <span class="screen-reader-text"><?php esc_html_e( 'List', 'wpa9-gallery' ); ?></span>
                </button>
            </span>
        </div>

        <div class="wpa9-regen-progress" hidden aria-live="polite" role="status">
            <span class="wpa9-regen-progress__icon dashicons dashicons-update" aria-hidden="true"></span>
            <div class="wpa9-regen-progress__body">
                <span class="wpa9-regen-progress__label"></span>
                <div class="wpa9-regen-progress__bar"><span></span></div>
            </div>
            <button type="button" class="wpa9-regen-progress__close" aria-label="<?php esc_attr_e( 'Dismiss', 'wpa9-gallery' ); ?>" hidden>
                <span class="dashicons dashicons-no-alt" aria-hidden="true"></span>
            </button>
        </div>

        <ul class="wpa9-images" data-gallery-id="<?php echo (int) $gallery->id; ?>">
            <?php foreach ( $images as $img ) :
                $thumb_url  = WPA9_Image::thumb_url( $gallery, $img );
                $full_url   = WPA9_Image::url( $gallery, $img );
                $thumb_path = trailingslashit( WPA9_Storage::thumbs_dir( $gallery->slug ) ) . WPA9_Storage::thumb_filename( $img->filename );
                list( $tpx_w, $tpx_h ) = WPA9_Image_Processor::dimensions( $thumb_path );
            ?>
                <li class="wpa9-image" data-id="<?php echo (int) $img->id; ?>">
                    <label class="wpa9-image__check">
                        <input type="checkbox" class="wpa9-image-select">
                    </label>
                    <div class="wpa9-image__thumb">
                        <img src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php echo esc_attr( $img->alt_text ); ?>" loading="lazy">
                    </div>
                    <div class="wpa9-image__meta">
                        <input type="text" class="widefat wpa9-field" data-field="caption"   placeholder="<?php esc_attr_e( 'Caption', 'wpa9-gallery' ); ?>" value="<?php echo esc_attr( $img->caption ); ?>">
                        <input type="text" class="widefat wpa9-field" data-field="alt_text"  placeholder="<?php esc_attr_e( 'Alt text', 'wpa9-gallery' ); ?>" value="<?php echo esc_attr( $img->alt_text ); ?>">
                        <textarea class="widefat wpa9-field" data-field="description" rows="2" placeholder="<?php esc_attr_e( 'Description', 'wpa9-gallery' ); ?>"><?php echo esc_textarea( $img->description ); ?></textarea>
                        <p class="wpa9-image__size">
                            <?php esc_html_e( 'Image size:', 'wpa9-gallery' ); ?>
                            <span class="wpa9-image__size-val">
                                <?php echo ( $tpx_w && $tpx_h ) ? ( (int) $tpx_w . ' × ' . (int) $tpx_h . ' px' ) : esc_html__( 'n/a', 'wpa9-gallery' ); ?>
                            </span>
                        </p>
                        <div class="wpa9-image__actions">
                            <a href="<?php echo esc_url( $full_url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View full', 'wpa9-gallery' ); ?></a>
                            <button type="button" class="button-link wpa9-regen"><?php esc_html_e( 'Regen thumb', 'wpa9-gallery' ); ?></button>
                            <button type="button" class="button-link delete wpa9-delete-image"><?php esc_html_e( 'Delete', 'wpa9-gallery' ); ?></button>
                            <span class="wpa9-status" aria-live="polite"></span>
                        </div>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>

        <?php if ( empty( $images ) ) : ?>
            <p class="wpa9-empty"><?php esc_html_e( 'No images yet. Upload some using the area above.', 'wpa9-gallery' ); ?></p>
        <?php endif; ?>
    <?php endif; ?>
</div>
