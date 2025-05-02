<?php

namespace WPML\TranslationRoles;

use WPML\Collect\Support\Collection;
use WPML\LIB\WP\User;

class RemoveManager extends Remove {

	/**
	 * @inheritDoc
	 */
	public function run( Collection $data ) {
		$result = parent::run( $data );
		do_action( 'wpml_tm_ate_synchronize_managers' );
		return $result;
	}

	protected static function getCap() {
		return User::CAP_MANAGE_TRANSLATIONS;
	}
}
