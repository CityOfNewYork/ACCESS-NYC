<?php

namespace WPML\ICLToATEMigration\Endpoints;

use WPML\Ajax\IHandler;
use WPML\Collect\Support\Collection;
use WPML\FP\Either;
use WPML\ICLToATEMigration\Data;

class DeactivateICL implements IHandler {

	public function run( Collection $data ) {
		if ( \TranslationProxy::is_current_service_active_and_authenticated() ) {
			\TranslationProxy::deselect_active_service();

			global $sitepress_settings;
			$translationServiceData = current( $sitepress_settings['icl_translation_projects'] );

			Data::setICLDeactivated( true );
			Data::saveICLCredentials( $translationServiceData );
		}

		return Either::of( true );
	}
}