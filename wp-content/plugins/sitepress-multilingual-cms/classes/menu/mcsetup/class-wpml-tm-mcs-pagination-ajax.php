<?php

use WPML\TM\Menu\McSetup\CfMetaBoxOption;

/**
 * Class WPML_TM_MCS_Pagination_Ajax
 */
class WPML_TM_MCS_Pagination_Ajax {

	/** @var WPML_TM_MCS_Custom_Field_Settings_Menu_Factory */
	private $menu_factory;

	public function __construct( WPML_TM_MCS_Custom_Field_Settings_Menu_Factory $menu_factory ) {
		$this->menu_factory = $menu_factory;
	}

	/**
	 * Define Ajax hooks.
	 */
	public function add_hooks() {
		add_action( 'wp_ajax_wpml_update_mcs_cf', array( $this, 'update_mcs_cf' ) );
	}

	/**
	 * Update custom fields form.
	 */
	public function update_mcs_cf() {
		if ( isset( $_POST['nonce'] ) && wp_verify_nonce( $_POST['nonce'], 'icl_' . $_POST['type'] . '_translation_nonce' ) ) {
			$page = intval( $_POST['paged'] );
			$args = array(
				'items_per_page'      => intval( $_POST['items_per_page'] ),
				'page'                => $page,
				'highest_page_loaded' => isset( $_POST['highest_page_loaded'] ) ? intval( $_POST['highest_page_loaded'] ) : $page,
				'search'              => isset( $_POST['search'] ) ? sanitize_text_field( $_POST['search'] ) : '',
				'hide_system_fields'  => ! isset( $_POST['show_system_fields'] ) || ! filter_var( $_POST['show_system_fields'], FILTER_VALIDATE_BOOLEAN ),
				'show_cf_meta_box'    => isset( $_POST['show_cf_meta_box'] ) && filter_var( $_POST['show_cf_meta_box'], FILTER_VALIDATE_BOOLEAN ),
			);

			$menu_item = null;

			if ( 'cf' === $_POST['type'] ) {
				$menu_item = $this->menu_factory->create_post();
				CfMetaBoxOption::update( $args['show_cf_meta_box'] );
			} elseif ( 'tcf' === $_POST['type'] ) {
				$menu_item = $this->menu_factory->create_term();
			}

			if ( $menu_item ) {
				$result = array();
				ob_start();
				$menu_item->init_data( $args );
				$menu_item->render_body();
				$result['body'] = ob_get_clean();

				ob_start();
				$menu_item->render_pagination( $args['items_per_page'], $args['page'] );
				$result['pagination'] = ob_get_clean();

				wp_send_json_success( $result );
			}
		}
		wp_send_json_error(
			array(
				'message' => __( 'Invalid Request.', 'wpml-translation-management' ),
			)
		);
	}
}
