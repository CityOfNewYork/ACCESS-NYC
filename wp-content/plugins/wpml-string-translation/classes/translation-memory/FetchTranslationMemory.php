<?php


namespace WPML\ST\Main\Ajax;

use WPML\Ajax\IHandler;
use WPML\Collect\Support\Collection;
use WPML\FP\Either;
use WPML\FP\Fns;
use WPML\FP\Lst;
use WPML\FP\Obj;

class FetchTranslationMemory implements IHandler {

	/** @var \WPML_ST_Translation_Memory_Records $records */
	private $records;

	public function __construct( \WPML_ST_Translation_Memory_Records $records ) {
		$this->records = $records;
	}

	public function run( Collection $data ) {
		$translations = Obj::prop( 'batch', $data )
			? $this->fetchBatchStrings( Obj::prop( 'strings', $data ) )
			: $this->fetchSingleString( $data );

		if ( $translations !== false ) {
			return Either::of( $translations );
		} else {
			return Either::left( 'invalid data' );
		}
	}

	private function fetchBatchStrings( $strings ) {
		$results = Fns::map( [ $this, 'fetchSingleString' ], $strings );

		return Lst::includes( false, $results ) ? false : $results;
	}

	public function fetchSingleString( $data ) {
		$string = Obj::prop( 'string', $data );
		$source = Obj::prop( 'source', $data );
		$target = Obj::prop( 'target', $data );

		if ( $string && $source && $target ) {
			return $this->records->get( [ $string ], $source, $target );
		} else {
			return false;
		}
	}
}
