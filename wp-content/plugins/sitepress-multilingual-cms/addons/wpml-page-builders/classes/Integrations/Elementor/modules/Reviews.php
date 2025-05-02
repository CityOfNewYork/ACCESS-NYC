<?php
/**
 * Reviews
 */
namespace WPML\PB\Elementor\Modules;

class Reviews extends \WPML_Elementor_Module_With_Items {

    protected function get_title( $field ) {
        switch ( $field ) {
            case 'content':
                return esc_html__( 'Reviews: Comment Contents', 'sitepress' );
            case 'name':
                return esc_html__( 'Reviews: Commenter Name', 'sitepress' );
            case 'title':
                return esc_html__( 'Reviews: Comment Title', 'sitepress' );
            case 'image':
                return esc_html__( 'Reviews: Comment Image', 'sitepress' );
            case 'url':
                return esc_html__( 'Reviews: Comment Link', 'sitepress' );
            default:
                return '';
        }
    }

    public function get_fields() {
        return [ 'content', 'name', 'title', 'link' => [ 'field' => 'url' ] ];
    }

    protected function get_editor_type( $field ) {
        if ( 'content' === $field ) {
            return 'LINE';
        }
        if ( 'name' === $field ) {
            return 'LINE';
        }
        if ( 'title' === $field ) {
            return 'LINE';
        }
        if ( 'url' === $field ) {
            return 'LINK';
        }

		return 'LINE';
    }

    public function get_items_field() {
        return 'slides';
    }
}