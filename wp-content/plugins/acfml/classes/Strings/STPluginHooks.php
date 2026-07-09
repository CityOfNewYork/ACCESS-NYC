<?php

namespace ACFML\Strings;

use ACFML\Options;
use WPML\LIB\WP\Hooks;

class STPluginHooks implements \IWPML_Backend_Action {

	const PLUGIN_STATUS_KEY = 'string-translation-status';

	const PLUGIN_STATUS_ACTIVATED   = 'activated';
	const PLUGIN_STATUS_DEACTIVATED = 'deactivated';

	/**
	 * @var Translator
	 */
	private $translator;

	/**
	 * @param Translator $translator
	 */
	public function __construct( Translator $translator ) {
		$this->translator = $translator;
	}

	/**
	 * @return void
	 */
	public function add_hooks() {
		if ( wp_doing_ajax() ) {
			return;
		}

		Hooks::onAction( 'wp_loaded' )
			->then( [ $this, 'maybeRegisterFieldGroupsStrings' ] );
	}

	/**
	 * @param array $fieldGroup
	 *
	 * @return bool
	 */
	public function hasPackage( $fieldGroup ) {
		return Package::STATUS_NOT_REGISTERED !== Package::create( $fieldGroup['ID'] )->getStatus();
	}

	/**
	 * @return void
	 */
	public function maybeRegisterFieldGroupsStrings() {
		$isStActivated           = HooksFactory::isStActivated();
		$isPluginStatusActivated = self::getPluginStatus() === self::PLUGIN_STATUS_ACTIVATED;

		if ( ! $isStActivated && $isPluginStatusActivated ) {
			self::setPluginStatus( self::PLUGIN_STATUS_DEACTIVATED );
		} elseif ( $isStActivated && ! $isPluginStatusActivated ) {
			$this->registerFieldGroupsStrings();
			self::setPluginStatus( self::PLUGIN_STATUS_ACTIVATED );
		}
	}

	/**
	 * @return void
	 */
	private function registerFieldGroupsStrings() {
		wpml_collect( acf_get_field_groups() )
			->reject( [ $this, 'hasPackage' ] )
			->map( [ $this->translator, 'registerGroupAndFieldsAndLayouts' ] );
	}

	/**
	 * @return string|null
	 */
	private static function getPluginStatus() {
		return Options::get( self::PLUGIN_STATUS_KEY );
	}

	/**
	 * @param string $status
	 *
	 * @return void
	 */
	private static function setPluginStatus( $status ) {
		Options::set( self::PLUGIN_STATUS_KEY, $status );
	}
}
