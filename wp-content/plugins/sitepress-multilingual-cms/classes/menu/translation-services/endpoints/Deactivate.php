<?php


namespace WPML\TM\Menu\TranslationServices\Endpoints;

use WPML\Ajax\IHandler;
use WPML\Collect\Support\Collection;
use WPML\FP\Either;

class Deactivate implements IHandler {

	public function run( Collection $data ) {
		if ( \TranslationProxy::get_current_service_id() ) {
			\TranslationProxy::clear_preferred_translation_service();
			\TranslationProxy::deselect_active_service();
		}

		return Either::of( 'OK' );
	}

}