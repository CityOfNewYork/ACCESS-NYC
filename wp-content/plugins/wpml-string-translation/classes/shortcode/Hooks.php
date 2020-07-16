<?php

namespace WPML\ST\Shortcode;

use function WPML\FP\partial;

class Hooks implements \IWPML_DIC_Action, \IWPML_Backend_Action, \IWPML_AJAX_Action, \IWPML_REST_Action {
	/** @var \WPML_ST_DB_Mappers_Strings  */
	private $stringMapper;

	public function __construct( \WPML_ST_DB_Mappers_Strings $stringMapper ) {
		$this->stringMapper = $stringMapper;
	}


	public function add_hooks() {
		$appendId = partial( [ Xliff::class, 'appendId' ], [ $this->stringMapper, 'getByDomainAndValue' ] );
		/**
		 * @see \WPML\ST\Shortcode\Xliff::appendId
		 */
		add_filter( 'wpml_tm_xliff_unit_field_data', $appendId );


		/**
		 * @see \WPML\ST\Shortcode\Xliff::restoreOriginalShortcodes
		 */
		add_filter( 'wpml_tm_ate_job_data_from_xliff', [ Xliff::class, 'registerStringTranslation' ], 9, 2 );

		$restoreOriginalShortcodes = partial(
			[ Xliff::class, 'restoreOriginalShortcodes' ],
			[ $this->stringMapper, 'getById' ]
		);
		/**
		 * @see \WPML\ST\Shortcode\Xliff::restoreOriginalShortcodes
		 */
		add_filter( 'wpml_tm_ate_job_data_from_xliff', $restoreOriginalShortcodes, 10, 1 );
	}
}