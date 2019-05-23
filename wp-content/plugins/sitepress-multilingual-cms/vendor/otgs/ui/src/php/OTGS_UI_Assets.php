<?php

/**
 * @author OnTheGo Systems
 */
class OTGS_UI_Assets {
	const ASSETS_TYPES_SCRIPT = 'script';
	const ASSETS_TYPES_STYLE  = 'style';

	/** @var string */
	private $assets_root_url;

	/** @var OTGS_Assets_Store */
	private $assets_store;

	/**
	 * OTGS_UI_Assets constructor.
	 *
	 * @param string             $assets_root_url Root URL for the dist directory on this vendor library.
	 * @param \OTGS_Assets_Store $assets_store
	 */
	public function __construct( $assets_root_url, OTGS_Assets_Store $assets_store ) {
		$this->assets_store    = $assets_store;
		$this->assets_root_url = $assets_root_url;
	}

	/**
	 * Registers both scripts and styles
	 */
	public function register() {
		$this->register_scripts();
		$this->register_styles();
	}

	/**
	 * Registers scripts
	 */
	private function register_scripts() {
		foreach ( $this->assets_store->get( 'js' ) as $handle => $path ) {
			$this->register_script( $handle, $path );
		}
	}

	/**
	 * @param string $handle
	 * @param string $path
	 */
	private function register_script( $handle, $path ) {
		$this->register_resource( self::ASSETS_TYPES_SCRIPT, $handle, $path );
	}

	/**
	 * @param string $type
	 * @param string $handle
	 * @param array  $path
	 */
	private function register_resource( $type, $handle, $path ) {
		$function = null;
		if ( self::ASSETS_TYPES_SCRIPT === $type ) {
			$function = 'wp_register_script';
		}
		if ( self::ASSETS_TYPES_STYLE === $type ) {
			$function = 'wp_register_style';
		}
		if ( ! $function ) {
			return;
		}

		if ( 1 === count( $path ) ) {
			$function( $handle, $this->get_assets_base_url() . '/' . $path[0] );
		} else {
			foreach ( $path as $index => $resource ) {
				$function( $handle . '-' . ( $index + 1 ), $this->get_assets_base_url() . '/' . $resource );
			}
		}
	}

	/**
	 * @return string
	 */
	private function get_assets_base_url() {
		return rtrim( $this->assets_root_url, '/\\' );
	}

	/**
	 * Registers styles
	 */
	private function register_styles() {
		foreach ( $this->assets_store->get( 'css' ) as $handle => $path ) {
			$this->register_style( $handle, $path );
		}
	}

	/**
	 * @param string $handle
	 * @param string $path
	 */
	private function register_style( $handle, $path ) {
		$this->register_resource( self::ASSETS_TYPES_STYLE, $handle, $path );
	}
}
