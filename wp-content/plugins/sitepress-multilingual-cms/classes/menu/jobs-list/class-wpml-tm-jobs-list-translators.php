<?php

use \WPML\FP\Fns;
use \WPML\FP\Lst;
use \WPML\Element\API\Languages;
use function \WPML\FP\flip;
use function \WPML\FP\curryN;

class WPML_TM_Jobs_List_Translators {
	/** @var WPML_Translator_Records */
	private $translator_records;

	/**
	 * @param WPML_Translator_Records $translator_records
	 */
	public function __construct( WPML_Translator_Records $translator_records ) {
		$this->translator_records = $translator_records;
	}


	public function get() {
		$translators = $this->translator_records->get_users_with_capability();

		return array_map( [ $this, 'getTranslatorData' ], $translators );
	}

	private function getTranslatorData( $translator ) {
		return [
			'value'         => $translator->ID,
			'label'         => $translator->display_name,
			'languagePairs' => $this->getLanguagePairs( $translator ),
		];
	}

	private function getLanguagePairs( $translator ) {

		$isValidLanguage       = Lst::includes( Fns::__,  Lst::pluck( 'code', Languages::getAll() ) );
		$sourceIsValidLanguage = flip( $isValidLanguage );
		$getValidTargets       = Fns::filter( $isValidLanguage );

		$makePair = curryN(
			2,
			function ( $source, $target ) {
				return [
					'source' => $source,
					'target' => $target,
				];
			}
		);

		$getAsPair = curryN(
			3,
			function ( $makePair, $targets, $source ) {
				return Fns::map( $makePair( $source ), $targets );
			}
		);

		return \wpml_collect( $translator->language_pairs )
			->filter( $sourceIsValidLanguage )
			->map( $getValidTargets )
			->map( $getAsPair( $makePair ) )
			->flatten( 1 )
			->toArray();
	}
}
