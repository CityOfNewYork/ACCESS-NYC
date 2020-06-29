<?php

class WPML_Media_Attachments_Duplication_Factory extends \WPML\Media\Duplication\AbstractFactory {

	public function create() {
		if ( self::shouldActivateHooks() ) {
			return \WPML\Container\make( WPML_Media_Attachments_Duplication::class );
		}

		return null;
	}
}
