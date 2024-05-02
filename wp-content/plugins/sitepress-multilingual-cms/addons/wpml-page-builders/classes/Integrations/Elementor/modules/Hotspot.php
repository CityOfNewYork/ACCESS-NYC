<?php
/**
 * Reviews
 */
namespace WPML\PB\Elementor\Modules;

class Hotspot extends \WPML_Elementor_Module_With_Items {

    protected function get_title( $field ) {
        switch ( $field ) {
            case 'hotspot_label':
                return esc_html__( 'Hotspot: Label', 'sitepress' );
            case 'hotspot_tooltip_content':
                return esc_html__( 'Hotspot: Content', 'sitepress' );
            case 'url':
                return esc_html__( 'Hotspot: URL', 'sitepress' );
            default:
                return '';
        }
    }

    public function get_fields() {
        return [ 'hotspot_label', 'hotspot_tooltip_content', 'hotspot_link' => [ 'field' => 'url' ] ];
    }

    protected function get_editor_type( $field ) {
       switch ( $field ) {
			case 'hotspot_label':
				return 'LINE';
			case 'hotspot_tooltip_content':
				return 'VISUAL';
			case 'url':
			    return 'LINK';
		    default:
			    return '';
	    }
    }

    public function get_items_field() {
        return 'hotspot';
    }
}
