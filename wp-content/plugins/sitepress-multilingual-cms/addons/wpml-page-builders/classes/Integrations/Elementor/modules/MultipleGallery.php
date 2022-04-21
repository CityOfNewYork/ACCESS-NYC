<?php
/**
 * MulitpleGallery
 */
namespace WPML\PB\Elementor\Modules;

class MulitpleGallery extends \WPML_Elementor_Module_With_Items {

    protected function get_title( $field ) {
        switch ( $field ) {
            case 'gallery_title':
                return esc_html__( 'Gallery Title:', 'sitepress' );
            default:
                return '';
        }
    }

    public function get_fields() {
        return [ 'gallery_title' ];
    }

    protected function get_editor_type( $field ) {
        if ( 'gallery_title' === $field ) {
            return 'LINE';
        }

		return 'LINE';
    }

    public function get_items_field() {
        return 'galleries';
    }
}