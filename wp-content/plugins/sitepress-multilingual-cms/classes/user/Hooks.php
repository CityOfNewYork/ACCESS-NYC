<?php

namespace WPML\TM\User;

use WPML\LIB\WP\User;

class Hooks implements \IWPML_Backend_Action {

	public function add_hooks() {
		add_action( 'clean_user_cache', [ $this, 'cleanUserCacheAction' ] );
		add_action( 'updated_user_meta', [ $this, 'updatedUserMetaAction' ] );

		add_filter( 'wpml_show_hidden_languages_options', [ $this, 'filter_show_hidden_languages_options' ] );
	}

	public function cleanUserCacheAction() {
		$this->flushCache();
	}

	public function updatedUserMetaAction() {
		$this->flushCache();
	}

	public function filter_show_hidden_languages_options( $show_hidden_languages_options ) {
		if ( User::canManageTranslations() || User::hasCap(User::CAP_TRANSLATE) ) {
			return true;
		}

		return $show_hidden_languages_options;
	}

	private function flushCache() {
		wpml_get_cache( \WPML_Translation_Roles_Records::CACHE_GROUP )->flush_group_cache();
	}
}
