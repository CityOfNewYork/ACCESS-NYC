<?php

namespace WPML\Media\Translate;

use WPML\LIB\WP\Hooks;
use WPML\Media\Option;

class LanguagesUpdated implements \IWPML_AJAX_Action {

	public function add_hooks() {
		Hooks::onAction( 'wpml_update_active_languages' )
		     ->then( function () {
			     Option::setSetupFinished( false );
		     } );
	}
}
