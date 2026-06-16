<?php
/**
 * WPA9 Gallery — Album template
 *
 * Available variables (extracted into scope by WPA9_Template_Loader):
 *   $album stdClass — album row from wpa9_albums
 *   $items array    — list of items, each: array{ gallery: stdClass, cover: stdClass|null, count: int }
 *   $atts  array    — shortcode atts incl. columns, gap, gallery_template
 *
 * Clicking a gallery cover loads ?wpa9_gallery={slug} on the same page; the shortcode
 * detects that and renders the single gallery using $atts['gallery_template'].
 *
 * To override in your theme, copy this file to:
 *   {your-theme}/wpa9-gallery/album.php
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$columns = isset( $atts['columns'] ) ? max( 1, (int) $atts['columns'] ) : 4;
$gap     = isset( $atts['gap'] )     ? max( 0, (int) $atts['gap'] )     : 12;
$style   = '--wpa9-cols: ' . (int) $columns . '; --wpa9-gap: ' . (int) $gap . 'px;';
?>
<section class="wpa9-album wpa9-album--<?php echo esc_attr( $album->slug ); ?>" style="<?php echo esc_attr( $style ); ?>">

    <header class="wpa9-album__header">
        <h2 class="wpa9-album__title"><?php echo esc_html( $album->name ); ?></h2>
        <?php if ( ! empty( $album->description ) ) : ?>
            <p class="wpa9-album__desc"><?php echo esc_html( $album->description ); ?></p>
        <?php endif; ?>
    </header>

    <?php if ( empty( $items ) ) : ?>
        <p class="wpa9-album__empty"><?php esc_html_e( 'No galleries in this album.', 'wpa9-gallery' ); ?></p>
    <?php else : ?>
        <div class="wpa9-album__grid">
            <?php foreach ( $items as $item ) :
                $g     = $item['gallery'];
                $cover = $item['cover'];
                $count = (int) $item['count'];
                $href  = add_query_arg( 'wpa9_gallery', $g->slug );
                $thumb = $cover ? WPA9_Image::thumb_url( $g, $cover ) : '';
                $alt   = $cover && $cover->alt_text !== '' ? $cover->alt_text : $g->name;
            ?>
                <figure class="wpa9-album__item">
                    <a class="wpa9-album__link" href="<?php echo esc_url( $href ); ?>">
                        <?php if ( $thumb ) : ?>
                            <img class="wpa9-album__cover"
                                 src="<?php echo esc_url( $thumb ); ?>"
                                 alt="<?php echo esc_attr( $alt ); ?>"
                                 loading="lazy">
                        <?php else : ?>
                            <span class="wpa9-album__cover wpa9-album__cover--empty" aria-hidden="true"></span>
                        <?php endif; ?>
                        <figcaption class="wpa9-album__caption">
                            <span class="wpa9-album__name"><?php echo esc_html( $g->name ); ?></span>
                            <span class="wpa9-album__count"><?php
                                /* translators: %d: number of photos in the gallery */
                                printf( esc_html( _n( '%d photo', '%d photos', $count, 'wpa9-gallery' ) ), $count );
                            ?></span>
                        </figcaption>
                    </a>
                </figure>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</section>
