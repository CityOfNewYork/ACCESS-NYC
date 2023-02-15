<?php

namespace WPML\Setup;

use WPML\FP\Lst;
use WPML\FP\Obj;

class DisableNotices implements \IWPML_DIC_Action, \IWPML_Backend_Action {
	public function add_hooks() {

		add_action( 'wp_before_admin_bar_render', function () {
			$iSetupPage = Lst::includes( Obj::propOr( '', 'page', $_GET ), [
				'sitepress-multilingual-cms/menu/setup.php',
				'sitepress-multilingual-cms/menu/languages.php'
			] );

			if ( $iSetupPage ) {
				remove_all_actions( 'admin_notices' );
				remove_all_actions( 'all_admin_notices' );
			}

		} );
	}
}
