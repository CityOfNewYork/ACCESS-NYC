<?php

use \WPML\FP\Obj;
use \WPML\FP\Fns;
use  \WPML\ST\Main\Ajax\FetchTranslationMemory;

class WPML_ST_Translation_Memory implements IWPML_AJAX_Action, IWPML_Backend_Action, IWPML_DIC_Action {

	/** @var WPML_ST_Translation_Memory_Records $records */
	private $records;

	public function __construct( WPML_ST_Translation_Memory_Records $records ) {
		$this->records = $records;
	}

	public function add_hooks() {
		add_filter( 'wpml_st_get_translation_memory', [ $this, 'get_translation_memory' ], 10, 2 );
		add_filter( 'wpml_st_translation_memory_endpoint', Fns::always( FetchTranslationMemory::class ) );
	}

	/**
	 * @param array $empty_array
	 * @param array $args with keys
	 *                  - `strings` an array of strings
	 *                  - `source_lang`
	 *                  - `target_lang`
	 *
	 * @return stdClass[]
	 */
	public function get_translation_memory( $empty_array, $args ) {
		return $this->records->get(
			Obj::propOr( [], 'strings',  $args ),
			Obj::propOr( '', 'source_lang',  $args ),
			Obj::propOr( '', 'target_lang',  $args )
		);
	}
}
