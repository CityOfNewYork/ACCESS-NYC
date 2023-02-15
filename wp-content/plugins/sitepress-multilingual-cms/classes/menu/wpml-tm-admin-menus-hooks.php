<?php

use WPML\UIPage;

class WPML_TM_Admin_Menus_Hooks implements IWPML_Action {

	public function add_hooks() {
		add_action( 'init', array( $this, 'init_action' ) );
	}

	public function init_action() {
		$this->redirect_settings_menu();
		$this->redirect_from_empty_basket_page();
	}

	public function redirect_settings_menu() {
		if ( isset( $_GET['page'], $_GET['sm'] )
			&& WPML_TM_FOLDER . WPML_Translation_Management::PAGE_SLUG_MANAGEMENT === $_GET['page']
			&& in_array( $_GET['sm'], array( 'mcsetup', 'notifications', 'custom-xml-config' ), true )
		) {
			$query         = $_GET;
			$query['page'] = WPML_TM_FOLDER . WPML_Translation_Management::PAGE_SLUG_SETTINGS;
			wp_safe_redirect( add_query_arg( $query ), 302, 'WPML' );
		}
	}

	public function redirect_from_empty_basket_page() {
		if ( $this->is_tm_basket_empty() ) {
			$query = $_GET;
			$url   = add_query_arg( $query );
			wp_safe_redirect( remove_query_arg( 'sm', $url ), 302, 'WPML' );
		}
	}

	public static function is_tm_basket_empty() {
		return UIPage::isTMBasket( $_GET ) && TranslationProxy_Basket::get_basket_items_count( true ) === 0;
	}

}
