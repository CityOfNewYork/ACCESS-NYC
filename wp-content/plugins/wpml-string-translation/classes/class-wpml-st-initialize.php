<?php

class WPML_ST_Initialize {

	/** @var array */
	private $config;

	public function __construct( array $config = [] ) {
		$this->config = $config;
	}

	public function load() {
		add_action( 'plugins_loaded', array( $this, 'run' ), - PHP_INT_MAX );
	}

	public function run() {
		$this->includeAutoloader();
		$this->configureDIC();
		$this->loadEarlyHooks();

		$app = new \WPML\StringTranslation\Application( $this->config );
		$app->run();

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
