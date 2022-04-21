<?php

class WPML_ST_Initialize {

	public function load() {
		add_action( 'plugins_loaded', array( $this, 'run' ), - PHP_INT_MAX );
	}

	public function run() {
		if ( ! $this->hasMinimalCoreRequirements() ) {
			return;
		}

		$this->includeAutoloader();
		$this->configureDIC();
		$this->loadEarlyHooks();
	}

	private function hasMinimalCoreRequirements() {
		if ( ! class_exists( 'WPML_Core_Version_Check' ) ) {
			require_once WPML_ST_PATH . '/vendor/wpml-shared/wpml-lib-dependencies/src/dependencies/class-wpml-core-version-check.php';
		}

		return WPML_Core_Version_Check::is_ok( WPML_ST_PATH . '/wpml-dependencies.json' );
	}

	private function includeAutoloader() {
		require_once WPML_ST_PATH . '/vendor/autoload.php';
	}

	private function configureDIC() {
		\WPML\Container\share( \WPML\ST\Container\Config::getSharedClasses() );
		\WPML\Container\alias( \WPML\ST\Container\Config::getAliases() );
		\WPML\Container\delegate( \WPML\ST\Container\Config::getDelegated() );
	}

	private function loadEarlyHooks() {
		/** @var \WPML\ST\TranslationFile\Hooks $hooks */
		$hooks = \WPML\Container\make( \WPML\ST\TranslationFile\Hooks::class );
		$hooks->install();
	}
}
