<?php

namespace WPML\ST\StringsScanning\JS;

class ScriptRegisterHooks implements \IWPML_Action {

	private $scriptMap = [];

	public function add_hooks() {
		add_filter( 'script_loader_tag', [ $this, 'mapHandleSrc' ], 10, 3 );
		add_action( 'shutdown', [ $this, 'register' ] );
	}

	/**
	 * @param string $tag
	 * @param string $handle
	 * @param string $src
	 *
	 * @return string
	 */
	public function mapHandleSrc( $tag, $handle, $src ) {
		$this->scriptMap[ $handle ] = $src;

		return $tag;
	}

	public function register() {
		ScriptRegistry::register( $this->scriptMap );
	}
}
