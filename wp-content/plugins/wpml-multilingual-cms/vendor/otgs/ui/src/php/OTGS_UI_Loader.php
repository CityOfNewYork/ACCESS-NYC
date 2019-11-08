<?php

/**
 * @author OnTheGo Systems
 */
class OTGS_UI_Loader {

	/** @var \OTGS_UI_Assets */
	private $assets;

	/** @var \OTGS_Assets_Store */
	private $store;

	/**
	 * OTGS_UI_Loader constructor.
	 *
	 * @param \OTGS_Assets_Store $locator
	 * @param \OTGS_UI_Assets    $assets
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( OTGS_Assets_Store $locator = null, OTGS_UI_Assets $assets = null ) {
		if ( ! $locator || ! $assets ) {
			throw new InvalidArgumentException( 'Missing assets and assets store' );
		}

		$this->store  = $locator;
		$this->assets = $assets;
	}

	/**
	 * Hooks to the registration of all assets to the `ìnit` action
	 */
	public function load() {
		add_action( 'init', array( $this, 'register' ), 1 );
	}

	/**
	 * Adds the assets and registers them
	 */
	public function register() {
		$this->store->add_assets_location( dirname( __FILE__ ) . '/../../dist/assets.json' );
		$this->assets->register();
	}
}
