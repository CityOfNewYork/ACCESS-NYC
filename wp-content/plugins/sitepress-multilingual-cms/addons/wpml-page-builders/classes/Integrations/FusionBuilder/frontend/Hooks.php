<?php

namespace WPML\Compatibility\FusionBuilder\Frontend;

use WPML\API\Sanitize;
use WPML\Compatibility\FusionBuilder\BaseHooks;
use WPML\FP\Obj;

class Hooks extends BaseHooks implements \IWPML_Frontend_Action, \IWPML_DIC_Action {

	/** @var \SitePress */
	private $sitepress;

	public function __construct(
		\SitePress $sitepress
	) {
		$this->sitepress = $sitepress;
	}

	public function add_hooks() {
		add_filter( 'nav_menu_link_attributes', [ $this, 'addMenuLinkCssClass' ], 10, 2 );
		add_filter( 'fusion_get_all_meta', [ $this, 'translateOffCanvasConditionId' ] );
	}

	/**
	 * Adds required CSS class in menu links. This CSS class is used by
	 * WPML_Fix_Links_In_Display_As_Translated_Content::fix_fallback_links() to skip fixing language switcher links.
	 *
	 * Notes:
	 * - This is intended for themes that provide custom menu walkers.
	 * - For this to work, the custom menu walker must call `nav_menu_link_attributes` filter.
	 *
	 * @param array $atts
	 * @param mixed $item
	 *
	 * @return array
	 */
	public function addMenuLinkCssClass( $atts, $item ) {
		if ( 'wpml_ls_menu_item' === $item->type ) {
			$class         = Obj::prop( 'class', $atts );
			$atts['class'] = $class ? "$class wpml-ls-link" : 'wpml-ls-link';
		}

		return $atts;
	}

	/**
	 * @param array|mixed $data
	 *
	 * @return array|mixed
	 */
	public function translateOffCanvasConditionId( $data ) {
		if ( is_array( $data ) && Obj::prop( 'layout_conditions', $data ) ) {
			$conditions = json_decode( Obj::prop( 'layout_conditions', $data ), true );
			$result     = [];
			foreach ( $conditions as $key => $condition ) {
				if ( 'specific_' === substr( $key, 0, 9 ) ) {
					list( $pattern, $id ) = explode( '|', $key, 2 );
					$post_type            = substr( $pattern, 9 );
					$id                   = $this->sitepress->get_object_id( $id, $post_type, true );
					$key                  = $pattern . '|' . $id;
				}
				$result[ $key ] = $condition;
			}
			$data = Obj::assoc( 'layout_conditions', wp_json_encode( $result ), $data );
		}

		return $data;
	}

}
