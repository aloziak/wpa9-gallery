<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap wpa9-wrap">
    <h1><?php esc_html_e( 'WPA9 Gallery Dashboard', 'wpa9-gallery' ); ?></h1>
    <p><?php esc_html_e( 'Welcome to WPA9 Gallery. This page is a quick starting point for your gallery management and support options.', 'wpa9-gallery' ); ?></p>
    <p><?php esc_html_e( 'Use the menu on the left to manage galleries, albums, and plugin settings.', 'wpa9-gallery' ); ?></p>

    <h2><?php esc_html_e( 'Support WPA9 Gallery', 'wpa9-gallery' ); ?></h2>
    <p><?php esc_html_e( 'If you enjoy using WPA9 Gallery, you can support development with a small contribution.', 'wpa9-gallery' ); ?></p>

    <form action="https://www.paypal.com/donate/?business=HPRK67TQZ43MS&no_recurring=0&currency_code=EUR" method="post" target="_blank">
        <input type="hidden" name="business" value="aloziak@gmail.com">
        <input type="hidden" name="currency_code" value="EUR">
        <input type="hidden" name="item_name" value="Support WPA9 Gallery development">
        <button type="submit" class="button button-primary">
            <?php esc_html_e( 'Buy me a coffee', 'wpa9-gallery' ); ?>
        </button>
    </form>
</div>
