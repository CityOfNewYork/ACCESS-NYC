<?php

namespace WPML\PB\BeaverBuilder\Config;

class Factory extends \WPML\PB\Config\Factory {

	const DATA = [
		'configRoot'              => 'beaver-builder-widgets',
		'defaultConditionKey'     => 'type',
		'pbKey'                   => 'beaver-builder',
		'translatableWidgetsHook' => 'wpml_beaver_builder_modules_to_translate',
	];

	/**
	 * @inheritDoc
	 */
	protected function getPbData( $key ) {
		return self::DATA[ $key ];
	}
}
