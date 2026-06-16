<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WPA9_Shortcode {

    public function __construct() {
        add_shortcode( 'wpa9_gallery', array( $this, 'render_gallery' ) );
        add_shortcode( 'wpa9_album', array( $this, 'render_album' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
    }

    public function register_assets() {
        wp_register_style(
            'wpa9-gallery',
            WPA9_URL . 'public/css/wpa9-gallery.css',
            array(),
            WPA9_VERSION
        );
    }

    public function render_gallery( $atts ) {
        $atts = shortcode_atts( array(
            'id'       => 0,
            'slug'     => '',
            'template' => 'gallery-masonry',
            'columns'  => 5,
            'gap'      => 12,
            'size'     => 'thumbnail', // used by gallery-native
            'link'     => 'file',      // used by gallery-native: file | none
            'ratio'    => '',          // '' = inherit gallery → settings; otherwise one of WPA9_Install::ratios()
        ), $atts, 'wpa9_gallery' );

        $gallery = $atts['slug'] ? WPA9_Gallery::get_by_slug( sanitize_title( $atts['slug'] ) ) : WPA9_Gallery::get( (int) $atts['id'] );
        if ( ! $gallery ) {
            return '';
        }

        wp_enqueue_style( 'wpa9-gallery' );

        $images    = WPA9_Image::for_gallery( $gallery->id );
        $album     = null; // Galleries can belong to multiple albums; standalone render isn't tied to one.
        $ratio     = $this->resolve_ratio_from_atts( $atts, $gallery );
        $data_html = WPA9_Gallery::render_data_atts_html( $gallery );

        return WPA9_Template_Loader::render( $atts['template'], array(
            'gallery'   => $gallery,
            'images'    => $images,
            'album'     => $album,
            'atts'      => $atts,
            'ratio'     => $ratio,
            'data_html' => $data_html,
        ) );
    }

    private function resolve_ratio_from_atts( $atts, $gallery ) {
        // Shortcode-level override takes priority if it's a valid key.
        if ( ! empty( $atts['ratio'] ) ) {
            $allowed = array_keys( WPA9_Install::ratios() );
            if ( in_array( $atts['ratio'], $allowed, true ) ) {
                return $atts['ratio'];
            }
        }
        return WPA9_Gallery::resolve_ratio( $gallery );
    }

    public function render_album( $atts ) {
        $atts = shortcode_atts( array(
            'id'               => 0,
            'slug'             => '',
            'template'         => 'album',
            'gallery_template' => 'gallery-masonry',
            'columns'          => 4,
            'gap'              => 12,
            'size'             => 'thumbnail',
            'link'             => 'file',
            'ratio'            => '',
        ), $atts, 'wpa9_album' );

        $album = $atts['slug'] ? WPA9_Album::get_by_slug( sanitize_title( $atts['slug'] ) ) : WPA9_Album::get( (int) $atts['id'] );
        if ( ! $album ) {
            return '';
        }

        wp_enqueue_style( 'wpa9-gallery' );

        // Drill-down: ?wpa9_gallery=slug renders that single gallery (only if it belongs to this album).
        $drill_slug = isset( $_GET['wpa9_gallery'] ) ? sanitize_title( wp_unslash( $_GET['wpa9_gallery'] ) ) : '';
        if ( $drill_slug !== '' ) {
            $gallery = WPA9_Gallery::get_by_slug( $drill_slug );
            if ( $gallery && in_array( (int) $album->id, WPA9_Gallery::album_ids( (int) $gallery->id ), true ) ) {
                $images    = WPA9_Image::for_gallery( $gallery->id );
                $ratio     = $this->resolve_ratio_from_atts( $atts, $gallery );
                $data_html = WPA9_Gallery::render_data_atts_html( $gallery );
                $back_url  = remove_query_arg( 'wpa9_gallery' );

                $out  = '<div class="wpa9-album-drill wpa9-album-drill--' . esc_attr( $album->slug ) . '">';
                $out .= '<p class="wpa9-album-drill__back"><a href="' . esc_url( $back_url ) . '">' . esc_html__( '← Back to album', 'wpa9-gallery' ) . '</a></p>';
                $out .= WPA9_Template_Loader::render( $atts['gallery_template'], array(
                    'gallery'   => $gallery,
                    'images'    => $images,
                    'album'     => $album,
                    'atts'      => $atts,
                    'ratio'     => $ratio,
                    'data_html' => $data_html,
                ) );
                $out .= '</div>';
                return $out;
            }
        }

        // Album overview: one cover thumbnail per gallery.
        $galleries = WPA9_Gallery::all( $album->id );
        $items     = array();
        foreach ( $galleries as $gallery ) {
            $images  = WPA9_Image::for_gallery( $gallery->id );
            $items[] = array(
                'gallery' => $gallery,
                'cover'   => ! empty( $images ) ? $images[0] : null,
                'count'   => count( $images ),
            );
        }

        return WPA9_Template_Loader::render( $atts['template'], array(
            'album' => $album,
            'items' => $items,
            'atts'  => $atts,
        ) );
    }
}
