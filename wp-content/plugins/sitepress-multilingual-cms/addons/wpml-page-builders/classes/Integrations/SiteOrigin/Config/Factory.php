<?php

namespace WPML\PB\SiteOrigin\Config;

class Factory extends \WPML\PB\Config\Factory {

	const DATA = [
		'configRoot'              => 'siteorigin-widgets',
		'defaultConditionKey'     => '_type',
		'pbKey'                   => 'siteorigin',
		'translatableWidgetsHook' => 'wpml_siteorigin_modules_to_translate',
	];

	/**
	 * @inheritDoc
	 */
	protected function getPbData( $key ) {
		return self::DATA[ $key ];
	}

}
