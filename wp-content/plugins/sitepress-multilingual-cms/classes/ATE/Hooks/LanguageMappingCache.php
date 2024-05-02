<?php

namespace WPML\TM\ATE\Hooks;

use WPML\LIB\WP\Hooks;
use WPML\TM\API\ATE\CachedLanguageMappings;

class LanguageMappingCache implements \IWPML_Backend_Action, \IWPML_REST_Action {
	public function add_hooks() {
		$clearCache = function () {
			CachedLanguageMappings::clearCache();
		};

		Hooks::onAction( 'wpml_tm_ate_translation_engines_updated' )->then( $clearCache );
		Hooks::onAction( 'icl_after_set_default_language' )->then( $clearCache );
		Hooks::onAction( 'wpml_update_active_languages' )->then( $clearCache );
	}
}
