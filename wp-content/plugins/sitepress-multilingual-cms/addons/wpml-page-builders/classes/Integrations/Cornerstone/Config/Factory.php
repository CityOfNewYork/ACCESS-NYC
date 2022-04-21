<?php

namespace WPML\PB\Cornerstone\Config;

class Factory extends \WPML\PB\Config\Factory {

	const DATA = [
		'configRoot'              => 'cornerstone-widgets',
		'defaultConditionKey'     => '_type',
		'pbKey'                   => 'cornerstone',
		'translatableWidgetsHook' => 'wpml_cornerstone_modules_to_translate',
	];

	/**
	 * @inheritDoc
	 */
	protected function getPbData( $key ) {
		return self::DATA[ $key ];
	}
}
