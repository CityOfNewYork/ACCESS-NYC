<?php

namespace WPML\PB\Config;

use WPML\WP\OptionManager;

abstract class Factory implements \IWPML_Backend_Action_Loader, \IWPML_Frontend_Action_Loader {

	/**
	 * @return \IWPML_Action
	 * @throws \Auryn\InjectionException
	 */
	public function create() {
		return new Hooks(
			new Parser(
				$this->getPbData( 'configRoot' ),
				$this->getPbData( 'defaultConditionKey' )
			),
			new Storage(
				new OptionManager(),
				$this->getPbData( 'pbKey' )
			),
			$this->getPbData( 'translatableWidgetsHook' )
		);
	}

	/**
	 * @param string $key
	 *
	 * @return mixed
	 */
	abstract protected function getPbData( $key );
}
