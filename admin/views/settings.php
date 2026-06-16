<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$opts   = WPA9_Install::get_settings();
$ratios = WPA9_Install::ratios();
?>
<div class="wrap wpa9-wrap">
    <h1><?php esc_html_e( 'WPA9 Gallery — Settings', 'wpa9-gallery' ); ?></h1>

    <form method="post">
        <?php wp_nonce_field( 'wpa9_save_settings' ); ?>
        <input type="hidden" name="wpa9_action" value="save_settings">

        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><?php esc_html_e( 'Fullsize image (max W × H)', 'wpa9-gallery' ); ?></th>
                <td>
                    <label><?php esc_html_e( 'Width', 'wpa9-gallery' ); ?>
                        <input name="fullsize_width" type="number" min="0" step="1" value="<?php echo (int) $opts['fullsize_width']; ?>" class="small-text"></label>
                    <label><?php esc_html_e( 'Height', 'wpa9-gallery' ); ?>
                        <input name="fullsize_height" type="number" min="0" step="1" value="<?php echo (int) $opts['fullsize_height']; ?>" class="small-text"></label>
                    <p class="description"><?php esc_html_e( 'Default for new galleries. Leave one dimension at 0 to scale proportionally to the other; set both to hard-crop to that exact size.', 'wpa9-gallery' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Thumbnail (max W × H)', 'wpa9-gallery' ); ?></th>
                <td>
                    <label><?php esc_html_e( 'Width', 'wpa9-gallery' ); ?>
                        <input name="thumb_width" type="number" min="0" step="1" value="<?php echo (int) $opts['thumb_width']; ?>" class="small-text"></label>
                    <label><?php esc_html_e( 'Height', 'wpa9-gallery' ); ?>
                        <input name="thumb_height" type="number" min="0" step="1" value="<?php echo (int) $opts['thumb_height']; ?>" class="small-text"></label>
                    <p class="description"><?php esc_html_e( 'Default for new galleries. Leave one dimension at 0 for proportional thumbnails; set both to hard-crop to that exact size.', 'wpa9-gallery' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="wpa9-global-ratio"><?php esc_html_e( 'Default display ratio', 'wpa9-gallery' ); ?></label></th>
                <td>
                    <select id="wpa9-global-ratio" name="ratio">
                        <?php foreach ( $ratios as $key => $label ) : ?>
                            <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $opts['ratio'], $key ); ?>><?php echo esc_html( $label ); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?php esc_html_e( 'Applied via CSS aspect-ratio + object-fit:cover. Each gallery can override this.', 'wpa9-gallery' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="wpa9-jpeg"><?php esc_html_e( 'JPEG quality', 'wpa9-gallery' ); ?></label></th>
                <td><input id="wpa9-jpeg" name="jpeg_quality" type="number" min="10" max="100" value="<?php echo (int) $opts['jpeg_quality']; ?>" class="small-text"></td>
            </tr>
            <tr>
                <th scope="row"><label for="wpa9-data-atts"><?php esc_html_e( 'Link data-* attributes', 'wpa9-gallery' ); ?></label></th>
                <td>
                    <textarea id="wpa9-data-atts" name="data_atts" rows="4" class="large-text code" placeholder="fancybox={slug}&#10;caption=My gallery"><?php echo esc_textarea( $opts['data_atts'] ); ?></textarea>
                    <p class="description">
                        <?php esc_html_e( 'One key=value per line. Rendered as data-{key}="{value}" on each image link. Tokens: {slug}, {id}, {name}.', 'wpa9-gallery' ); ?><br>
                        <?php esc_html_e( 'Example (Fancybox): fancybox={slug} — each gallery becomes its own lightbox group automatically.', 'wpa9-gallery' ); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Storage location', 'wpa9-gallery' ); ?></th>
                <td><code><?php echo esc_html( WPA9_Storage::base_dir() ); ?></code></td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Template overrides', 'wpa9-gallery' ); ?></th>
                <td>
                    <p><?php esc_html_e( 'Copy a template from the plugin into your theme to customize it:', 'wpa9-gallery' ); ?></p>
                    <code><?php echo esc_html( WPA9_DIR . 'templates/gallery-masonry.php' ); ?>
                    &nbsp;→&nbsp;
                    <?php echo esc_html( get_stylesheet_directory() . '/wpa9-gallery/gallery-masonry.php' ); ?></code>
                </td>
            </tr>
        </table>

        <p><button type="submit" class="button button-primary"><?php esc_html_e( 'Save settings', 'wpa9-gallery' ); ?></button></p>
    </form>
</div>
