<?php

namespace WPML\PB;

class App {

	public static function run() {
		global $sitepress, $wpdb;

		LegacyIntegration::load();

		if (
			$sitepress->is_setup_complete()
			&& has_action( 'wpml_before_init', 'load_wpml_st_basics' )
		) {
			if ( self::shouldLoadTMHooks() ) {
				$page_builder_hooks = new \WPML_TM_Page_Builders_Hooks(
					new \WPML_TM_Page_Builders( $sitepress ),
					$sitepress
				);

				$page_builder_hooks->init_hooks();
			}

			$app = new \WPML_Page_Builders_App( new \WPML_Page_Builders_Defined() );
			$app->add_hooks();

			new \WPML_PB_Loader( new \WPML_ST_Settings() );
		}
	}

	/**
	 * @return bool
	 */
	private static function shouldLoadTMHooks() {
		return 	defined( 'WPML_TM_VERSION' )
		          && (
			          is_admin()
			          || ( defined( 'XMLRPC_REQUEST' ) && constant( 'XMLRPC_REQUEST' ) )
			          || wpml_is_rest_request()
		          );
	}
}
