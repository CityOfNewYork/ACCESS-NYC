<?php

namespace WPML\PB\Config;

use function WPML\FP\tap as tap;

class Hooks implements \IWPML_Action {

	const PRIORITY_AFTER_DEFAULT = 20;

	/** @var Parser $parser */
	private $parser;

	/** @var Storage $storage */
	private $storage;

	/** @var string $translatableWidgetsHook */
	private $translatableWidgetsHook;

	public function __construct(
		Parser $parser,
		Storage $storage,
		$translatableWidgetsHook
	) {
		$this->parser                  = $parser;
		$this->storage                 = $storage;
		$this->translatableWidgetsHook = $translatableWidgetsHook;
	}

	public function add_hooks() {
		add_filter( 'wpml_config_array', tap( [ $this, 'extractConfig' ] ) );
		add_filter( $this->translatableWidgetsHook , [ $this, 'extendTranslatableWidgets' ], self::PRIORITY_AFTER_DEFAULT );
	}

	public function extractConfig( array $allConfig ) {
		$this->storage->update( $this->parser->extract( $allConfig ) );
	}

	/**
	 * @param array $widgets
	 *
	 * @return array
	 */
	public function extendTranslatableWidgets( array $widgets ) {
		return array_merge( $widgets, $this->storage->get() );
	}
}
