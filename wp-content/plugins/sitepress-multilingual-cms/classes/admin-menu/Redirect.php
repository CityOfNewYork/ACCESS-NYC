<?php

namespace WPML\AdminMenu;

use WPML\FP\Lst;
use WPML\FP\Obj;

class Redirect implements \IWPML_Backend_Action {
	public function add_hooks() {
		add_action( 'init', [ $this, 'redirectOldMenuUrls' ] );
	}

	public function redirectOldMenuUrls() {
		$query = $_GET;

		$redirections = [
			'page' => [
				'wpml-translation-management/menu/main.php' => 'tm/menu/main.php',
			],
			'sm'   => [
				'translation-services' => 'translators'
			]
		];

		foreach ( $query as $param => $value ) {
			$query[ $param ] = Obj::pathOr( $value, [ $param, $value ], $redirections );
		}

		if ( array_diff_assoc( Lst::flatten( $_GET ), Lst::flatten( $query ) ) ) {
			if ( wp_safe_redirect( add_query_arg( $query ), 301, 'WPML' ) ) {
				exit;
			}
		}
	}
}
