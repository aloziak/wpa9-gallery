<?php
/**
 * WPA9 Gallery — Masonry template
 *
 * Available variables (extracted into scope by WPA9_Template_Loader):
 *   $gallery   stdClass  — gallery row from wpa9_galleries
 *   $images    array     — list of image rows from wpa9_images, already sorted
 *   $album     stdClass|null
 *   $atts      array     — shortcode atts incl. columns, gap
 *   $ratio     string    — resolved ratio key (none|3x2|4x3|16x9|16x10|21x9|1x1)
 *   $data_html string    — pre-escaped " data-foo=\"bar\"" attribute fragment (or '')
 *
 * To override in your theme, copy this file to:
 *   {your-theme}/wpa9-gallery/gallery-masonry.php
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$columns    = isset( $atts['columns'] ) ? max( 1, (int) $atts['columns'] ) : 5;
$gap        = isset( $atts['gap'] )     ? max( 0, (int) $atts['gap'] )     : 12;
$style      = '--wpa9-cols: ' . (int) $columns . '; --wpa9-gap: ' . (int) $gap . 'px;';
$ratio      = isset( $ratio ) ? $ratio : 'none';
$ratio_cls  = ( $ratio && $ratio !== 'none' ) ? ' r' . sanitize_html_class( $ratio ) : '';
$data_html  = isset( $data_html ) ? $data_html : '';
?>
<section class="wpa9-gallery wpa9-gallery--masonry wpa9-gallery--<?php echo esc_attr( $gallery->slug ); ?>" style="<?php echo esc_attr( $style ); ?>">

    <header class="wpa9-gallery__header">
        <h2 class="wpa9-gallery__title"><?php echo esc_html( $gallery->name ); ?></h2>
        <?php if ( ! empty( $gallery->description ) ) : ?>
            <p class="wpa9-gallery__desc"><?php echo esc_html( $gallery->description ); ?></p>
        <?php endif; ?>
        <?php if ( ! empty( $gallery->author_name ) ) : ?>
            <p class="wpa9-gallery__author">
                <?php
                /* translators: %s: author name */
                printf( esc_html__( 'Photos by %s', 'wpa9-gallery' ), '<span>' . esc_html( $gallery->author_name ) . '</span>' );
                ?>
            </p>
        <?php endif; ?>
    </header>

    <?php if ( empty( $images ) ) : ?>
        <p class="wpa9-gallery__empty"><?php esc_html_e( 'No images yet.', 'wpa9-gallery' ); ?></p>
    <?php else : ?>
        <div class="wpa9-masonry">
            <?php foreach ( $images as $img ) :
                $full  = WPA9_Image::url( $gallery, $img );
                $thumb = WPA9_Image::thumb_url( $gallery, $img );
                $alt   = $img->alt_text !== '' ? $img->alt_text : $img->caption;
            ?>
                <figure class="wpa9-masonry__item<?php echo esc_attr( $ratio_cls ); ?>">
                    <a class="wpa9-masonry__link" href="<?php echo esc_url( $full ); ?>"<?php echo $data_html; // already escaped by render_data_atts_html() ?>>
                        <img class="wpa9-masonry__img"
                             src="<?php echo esc_url( $thumb ); ?>"
                             alt="<?php echo esc_attr( $alt ); ?>"
                             loading="lazy"
                             <?php if ( $img->width && $img->height ) : ?>
                                 width="<?php echo (int) $img->width; ?>"
                                 height="<?php echo (int) $img->height; ?>"
                             <?php endif; ?>>
                    </a>
                    <?php if ( ! empty( $img->caption ) ) : ?>
                        <figcaption class="wpa9-masonry__caption"><?php echo esc_html( $img->caption ); ?></figcaption>
                    <?php endif; ?>
                </figure>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</section>
