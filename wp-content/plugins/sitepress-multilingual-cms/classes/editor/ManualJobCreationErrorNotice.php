<?php

namespace WPML\TM\Editor;

use WPML\FP\Cast;
use WPML\FP\Fns;
use WPML\FP\Logic;
use WPML\FP\Obj;
use WPML\FP\Relation;
use WPML\LIB\WP\Hooks;
use WPML\LIB\WP\Option;
use WPML\TM\API\Jobs;
use WPML\UIPage;
use function WPML\Container\make;
use function WPML\FP\pipe;

class ManualJobCreationErrorNotice implements \IWPML_Backend_Action {

	const RETRY_LIMIT = 3;

	public function add_hooks() {
		if ( \WPML_TM_ATE_Status::is_enabled() ) {

			Hooks::onAction( 'wp_loaded' )
			     ->then( function () {
				     /** @var  \WPML_Notices $notices */
				     $notices = make( \WPML_Notices::class );

				     if ( isset( $_GET['ateJobCreationError'] ) ) {
					     $notice = $notices->create_notice( __CLASS__, $this->getContent( $_GET ) );

					     $notice->set_css_class_types( 'error' );
					     $notice->set_dismissible( false );

					     $notices->add_notice( $notice );
				     } else {
					     $notices->remove_notice( 'default', __CLASS__ );
				     }
			     } );
		}
	}

	/**
	 * @param array $params
	 *
	 * @return string
	 */
	private function getContent( array $params ) {
		$isATENotActiveError  = pipe( Obj::prop( 'ateJobCreationError' ), Cast::toInt(), Relation::equals( Editor::ATE_IS_NOT_ACTIVE ) );
		$isRetryLimitExceeded = pipe( Obj::prop( 'jobId' ), [ ATERetry::class, 'getCount' ], Relation::gt( self::RETRY_LIMIT ) );

		return Logic::cond( [
			[ $isATENotActiveError, [ self::class, 'ateNotActiveMessage' ] ],
			[ $isRetryLimitExceeded, [ self::class, 'retryMessage' ] ],
			[ Fns::always( true ), [ self::class, 'retryFailedMessage' ] ]
		], $params );
	}

	public static function retryMessage( array $params ) {
		$returnUrl = \remove_query_arg( [ 'ateJobCreationError', 'jobId' ], Jobs::getCurrentUrl() );

		return sprintf(
			'<div class="wpml-display-flex wpml-display-flex-center">%1$s <a class="button wpml-margin-left-sm" href="%2$s">%3$s</a></div>',
			__( "WPML didn't manage to translate this page.", 'wpml-translation-management' ),
			Jobs::getEditUrl( $returnUrl, Obj::prop( 'jobId', $params ) ),
			__( 'Try again', 'wpml-translation-management' )
		);
	}

	public static function retryFailedMessage() {
		return '<div>' .
		       sprintf(
			       __( 'WPML tried to translate this page three times and failed. To get it fixed, contact %s', 'wpml-translation-management' ),
			       '<a target=\'_blank\' href="https://wpml.org/forums/forum/english-support/">' . __( 'WPML support', 'wpml-translation-management' ) . '</a>'
		       ) . '</div>';
	}

	public static function ateNotActiveMessage() {
		return '<div>' .
		       sprintf(
			       __( 'WPML’s Advanced Translation Editor is enabled but not activated. Go to %s to resolve the issue.', 'wpml-translation-management' ),
			       '<a href="' . UIPage::getTMDashboard() . '">' . __( 'WPML Translation Management Dashboard', 'wpml-translation-management' ) . '</a>'
		       )
		       . '</div>';
	}
}
