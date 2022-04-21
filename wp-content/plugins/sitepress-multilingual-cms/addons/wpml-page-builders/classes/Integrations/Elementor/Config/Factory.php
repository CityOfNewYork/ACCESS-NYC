<?php

namespace WPML\PB\Elementor\Config;

class Factory extends \WPML\PB\Config\Factory {

	const DATA = [
		'configRoot'              => 'elementor-widgets',
		'defaultConditionKey'     => 'widgetType',
		'pbKey'                   => 'elementor',
		'translatableWidgetsHook' => 'wpml_elementor_widgets_to_translate',
	];

	/**
	 * @inheritDoc
	 */
	protected function getPbData( $key ) {
		return self::DATA[ $key ];
	}
}
