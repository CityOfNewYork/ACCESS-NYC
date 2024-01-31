<?php

namespace WPML\Compatibility\FusionBuilder\Frontend;

use WPML\API\Sanitize;
use WPML\Compatibility\FusionBuilder\BaseHooks;
use WPML\FP\Obj;

class Hooks extends BaseHooks implements \IWPML_Frontend_Action, \IWPML_DIC_Action {

	/** @var \SitePress */
	private $sitepress;

	/** @var \WPML_Translation_Element_Factory */
	private $elementFactory;

	public function __construct(
		\SitePress $sitepress,
		\WPML_Translation_Element_Factory $elementFactory
	) {
		$this->sitepress      = $sitepress;
		$this->elementFactory = $elementFactory;
	}

	public function add_hooks() {
		if ( $this->isFusionBuilderRequest() ) {
			// Add action at high priority, as Avada cleans all scripts at priority 100.
			add_action( 'wp_enqueue_scripts', [ $this, 'frontendScripts' ], PHP_INT_MAX );
		}

		add_filter( 'nav_menu_link_attributes', [ $this, 'addMenuLinkCssClass' ], 10, 2 );
		add_filter( 'fusion_get_all_meta', [ $this, 'translateOffCanvasConditionId' ] );
	}

	public function frontendScripts() {
		$post_id = get_the_ID();

		if ( ! $post_id ) {
			return;
		}

		$post_element = $this->elementFactory->create( $post_id, 'post' );
		$is_original  = ! $post_element->get_source_language_code();

		if (
			$is_original
			|| ! (
				class_exists( '\WPML_TM_Post_Edit_TM_Editor_Mode' )
				&& \WPML_TM_Post_Edit_TM_Editor_Mode::is_using_tm_editor( $this->sitepress, $post_id )
			)
		) {
			return;
		}

		$message = sprintf(
			// translators: 1 and 2 are html <strong></strong> tags.
			__( '%1$sWarning:%2$s You are trying to add a translation using the Fusion Builder Live editor but your site is configured to use the WPML Translation Editor.', 'sitepress' ),
			'<strong>',
			'</strong>'
		);
		$message = '<p>' . $message . '</p>';
		$button  = '<button>' . __( 'OK', 'sitepress' ) . '</button>';
		$warning = '<div class="wpml-fusion-builder-live-edit-warning"><div class="wpml-message">' . $message . $button . '</div></div>';

		$this->enqueue_style();
		$this->enqueue_script();
		$this->localize_script( [ 'warning' => $warning ] );
	}

	private function isFusionBuilderRequest() {
		/* phpcs:ignore WordPress.Security.NonceVerification.Recommended */
		$builder_id = Sanitize::stringProp( 'builder_id', $_GET );
		$builder    = filter_input( INPUT_GET, 'builder', FILTER_VALIDATE_BOOLEAN );

		return $builder && $builder_id;
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
	 * @param array $data
	 * @return array
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
