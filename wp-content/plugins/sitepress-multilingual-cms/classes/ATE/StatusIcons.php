<?php

namespace WPML\TM\ATE;

use WPML\FP\Lst;
use WPML\FP\Obj;
use WPML\FP\Relation;
use WPML\LIB\WP\Hooks;
use WPML\TM\API\Jobs;
use function WPML\FP\spreadArgs;

class StatusIcons implements \IWPML_Backend_Action {
	/** @var bool */
	private $alreadyFound = false;

	public function add_hooks() {
		if (
			(int) Obj::prop( 'ate_job_id', $_GET )
			&& self::hasTranslatedStatusInAte()
		) {
			Hooks::onFilter( 'wpml_css_class_to_translation', PHP_INT_MAX, 5 )
			     ->then( spreadArgs( [ $this, 'setSpinningIconOnPageList' ] ) );

			Hooks::onFilter( 'wpml_tm_translation_queue_job_icon', 10, 2 )
			     ->then( spreadArgs( [ $this, 'setSpinningIconInTranslationQueue' ] ) );
		}
	}

	private static function hasTranslatedStatusInAte() {
		return Lst::includes(
			(int) Obj::prop( 'ate_status', $_GET ),
			[ \WPML_TM_ATE_API::TRANSLATED, \WPML_TM_ATE_API::DELIVERING ]
		);
	}

	public function setSpinningIconOnPageList( $default, $postId, $languageCode, $trid, $status ) {
		if ( ICL_TM_COMPLETE === $status ) {
			return $default;
		}
		if ( $this->alreadyFound ) {
			return $default;
		} else {
			return $this->getIcon( $default, Jobs::getTridJob( $trid, $languageCode ) );
		}
	}

	public function setSpinningIconInTranslationQueue( $default, $job ) {
		return $this->getIcon( $default, $job );
	}

	public function getIcon( $default, $job ) {
		if ( $job &&
			(int) Obj::prop( 'editor_job_id', $job ) === (int) Obj::prop( 'ate_job_id', $_GET )
			&& Relation::propEq( 'editor', \WPML_TM_Editors::ATE, $job )
			&& Lst::includes( (int) Obj::prop( 'status', $job ), [ ICL_TM_WAITING_FOR_TRANSLATOR, ICL_TM_IN_PROGRESS ] )
		) {
			$this->alreadyFound = true;

			return 'otgs-ico-refresh-spin';
		} else {
			return $default;
		}
	}
}
