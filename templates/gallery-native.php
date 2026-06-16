<?php
/**
 * WPA9 Gallery — "Native WP" template
 *
 * Emits the exact HTML structure (and the same conditional inline <style>)
 * that core's gallery_shortcode() produces. Any theme that already styles
 * native WP galleries — including default themes — will style this one with
 * zero changes.
 *
 * Shortcode usage:
 *   [wpa9_gallery id="1" template="gallery-native" columns="3" size="thumbnail" link="file"]
 *
 * Reference: see gallery_shortcode() in wp-includes/media.php
 *
 * Theme override: copy this file to {your-theme}/wpa9-gallery/gallery-native.php
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$columns    = isset( $atts['columns'] ) ? max( 1, (int) $atts['columns'] ) : 5;
$size_class = isset( $atts['size'] )    ? sanitize_html_class( (string) $atts['size'] ) : 'thumbnail';
$link       = isset( $atts['link'] ) && $atts['link'] === 'none' ? 'none' : 'file';
$ratio      = isset( $ratio ) ? $ratio : 'none';
$ratio_cls  = ( $ratio && $ratio !== 'none' ) ? ' r' . sanitize_html_class( $ratio ) : '';
$data_html  = isset( $data_html ) ? $data_html : '';

$html5      = current_theme_supports( 'html5', 'gallery' );
$itemtag    = $html5 ? 'figure'     : 'dl';
$icontag    = $html5 ? 'div'        : 'dt';
$captiontag = $html5 ? 'figcaption' : 'dd';

$selector   = 'gallery-' . (int) $gallery->id;
$itemwidth  = $columns > 0 ? floor( 100 / $columns ) : 100;
$float      = is_rtl() ? 'right' : 'left';

/**
 * Same filter core uses; defaults to true on legacy themes and false on HTML5
 * themes (matching wp-includes/media.php behavior).
 */
$print_default_style = apply_filters( 'use_default_gallery_style', ! $html5 );

if ( $print_default_style ) {
    $type_attr = current_theme_supports( 'html5', 'style' ) ? '' : ' type="text/css"';
    echo "
    <style{$type_attr}>
        #{$selector} {
            margin: auto;
        }
        #{$selector} .gallery-item {
            float: {$float};
            margin-top: 10px;
            text-align: center;
            width: {$itemwidth}%;
        }
        #{$selector} img {
            border: 2px solid #cfcfcf;
        }
        #{$selector} .gallery-caption {
            margin-left: 0;
        }
        /* see gallery_shortcode() in wp-includes/media.php */
    </style>\n";
}

$classes = sprintf(
    'gallery galleryid-%d gallery-columns-%d gallery-size-%s',
    (int) $gallery->id,
    (int) $columns,
    $size_class
);
?>
<div id="<?php echo esc_attr( $selector ); ?>" class="<?php echo esc_attr( $classes ); ?>">

<?php
$i = 0;
foreach ( $images as $img ) :
    $i++;
    $full  = WPA9_Image::url( $gallery, $img );
    $thumb = WPA9_Image::thumb_url( $gallery, $img );
    $alt   = $img->alt_text !== '' ? $img->alt_text : $img->caption;

    // Core inserts a clearing <br> every $columns items on non-HTML5 themes.
    if ( ! $html5 && $columns > 0 && $i > 1 && ( $i - 1 ) % $columns === 0 ) {
        echo '<br style="clear: both" />';
    }
?>
    <<?php echo $itemtag; ?> class="gallery-item<?php echo esc_attr( $ratio_cls ); ?>">
        <<?php echo $icontag; ?> class="gallery-icon <?php echo ( $img->width >= $img->height ) ? 'landscape' : 'portrait'; ?>">
            <?php if ( $link === 'file' ) : ?>
                <a href="<?php echo esc_url( $full ); ?>"<?php echo $data_html; // already escaped ?>>
            <?php endif; ?>
            <img
                src="<?php echo esc_url( $thumb ); ?>"
                class="attachment-<?php echo esc_attr( $size_class ); ?> size-<?php echo esc_attr( $size_class ); ?>"
                alt="<?php echo esc_attr( $alt ); ?>"
                loading="lazy"
                <?php if ( $img->width && $img->height ) : ?>
                    width="<?php echo (int) $img->width; ?>"
                    height="<?php echo (int) $img->height; ?>"
                <?php endif; ?>
                aria-describedby="<?php echo esc_attr( $selector . '-' . (int) $img->id ); ?>" />
            <?php if ( $link === 'file' ) : ?>
                </a>
            <?php endif; ?>
        </<?php echo $icontag; ?>>

        <?php if ( ! empty( $img->caption ) ) : ?>
            <<?php echo $captiontag; ?> class="wp-caption-text gallery-caption" id="<?php echo esc_attr( $selector . '-' . (int) $img->id ); ?>">
                <?php echo wptexturize( esc_html( $img->caption ) ); ?>
            </<?php echo $captiontag; ?>>
        <?php endif; ?>
    </<?php echo $itemtag; ?>>
<?php
endforeach;
if ( ! $html5 ) {
    echo "<br style=\"clear: both\" />\n";
}
?>
</div>
