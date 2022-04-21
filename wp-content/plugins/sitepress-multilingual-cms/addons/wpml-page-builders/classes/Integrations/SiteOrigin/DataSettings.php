<?php

namespace WPML\PB\SiteOrigin;

use WPML\FP\Obj;

class DataSettings implements \IWPML_Page_Builders_Data_Settings {

	/**
	 * @return string
	 */
	public function get_meta_field() {
		return 'panels_data';
	}

	/**
	 * @return string
	 */
	public function get_node_id_field() {
		return 'widget_id';
	}

	/**
	 * @return array
	 */
	public function get_fields_to_copy() {
		return [];
	}

	/**
	 * @param array $data
	 *
	 * @return array
	 */
	public function convert_data_to_array( $data ) {
		return $data;
	}

	/**
	 * @param array $data
	 *
	 * @return array
	 */
	public function prepare_data_for_saving( array $data ) {
		return $data;
	}

	/**
	 * @return string
	 */
	public function get_pb_name() {
		return 'SiteOrigin';
	}

	/**
	 * @return array
	 */
	public function get_fields_to_save() {
		return [ $this->get_meta_field() ];
	}

	public function add_hooks() {
		add_filter( 'pre_wpml_is_translated_taxonomy', [ $this, 'filterMenus' ], 10, 2 );
		add_filter( 'siteorigin_panels_widgets', [ $this, 'removeWidgets' ] );
		add_filter( 'siteorigin_panels_widget_form', [ $this, 'removeLanguageSelector' ] );
	}

	/**
	 * By letting WPML think `nav_menu` is translatable, it will filter out items in other languages.
	 * To avoid interfeering with anything else, we do this only for the SiteOrigin menu widget.
	 *
	 * @param bool|null $result
	 * @param string    $tax
	 *
	 * @return bool|null
	 */
	public function filterMenus( $result, $tax ) {
		/* phpcs:ignore WordPress.Security.NonceVerification.Recommended */
		if ( is_admin() && 'nav_menu' === $tax && Obj::prop( 'action', $_REQUEST ) === 'so_panels_widget_form' ) {
			$result = true;
		}

		return $result;
	}

	/**
	 * @param array $widgets
	 *
	 * @return array
	 */
	public function removeWidgets( $widgets ) {
		unset( $widgets['WPML_LS_Widget'], $widgets['WP_Widget_Text_Icl'] );

		return $widgets;
	}

	/**
	 * @param int $postId
	 *
	 * @return bool
	 */
	public function is_handling_post( $postId ) {
		return (bool) get_post_meta( $postId, $this->get_meta_field(), true );
	}

	/**
	 * @param string $form
	 *
	 * @return string
	 */
	public function removeLanguageSelector( $form ) {
		$form = preg_replace( '/<p>[\n\r\t]*<label for="wpml-language">.*<\/p>/s', '', $form, 1 );
		$form = preg_replace( '/<select name="icl_language">.*<\/label>/s', '', $form, 1 );

		return $form;
	}

}
