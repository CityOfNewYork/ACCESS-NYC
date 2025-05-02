<?php

namespace WPML\ST\Batch\Translation;

use WPML\FP\Fns;
use WPML\FP\Obj;
use WPML\LIB\WP\Hooks as WPHooks;
use function WPML\FP\spreadArgs;

class Hooks {

	public static function addHooks(
		callable $getBatchId,
		callable $setBatchRecord,
		callable $getBatchRecord,
		callable $getString
	) {

		WPHooks::onFilter( 'wpml_tm_batch_factory_elements', 10, 2 )
			->then( spreadArgs( Convert::toBatchElements( $getBatchId, $setBatchRecord ) ) );

		WPHooks::onFilter( 'wpml_tm_basket_items_types', 10, 1 )
			->then( spreadArgs( Obj::set( Obj::lensProp( 'st-batch' ), 'core' ) ) );

		WPHooks::onFilter( 'wpml_is_external', 10, 2 )
			->then(
				spreadArgs(
					function ( $state, $type ) {
						return $state
						  || ( is_object( $type ) && Obj::prop( 'post_type', $type ) === 'strings' )
						  || $type === 'st-batch';
					}
				)
			);

		WPHooks::onFilter( 'wpml_get_translatable_item', 10, 3 )
			->then( spreadArgs( Strings::get( $getBatchRecord, $getString ) ) );

		WPHooks::onAction( 'wpml_save_external', 10, 3 )
			->then( spreadArgs( StringTranslations::save() ) );

		WPHooks::onFilter( 'wpml_tm_populate_prev_translation', 10, 3 )
			->then( spreadArgs( StringTranslations::addExisting() ) );
	}

	public static function addStringTranslationStatusHooks(
		callable $updateTranslationStatus,
		callable $initializeTranslation
	) {
		self::initializeStringTranslationStatusHooks( $initializeTranslation );

		WPHooks::onAction( 'wpml_tm_job_in_progress', 10, 2 )->then( spreadArgs( $updateTranslationStatus ) );
		WPHooks::onAction( 'wpml_tm_job_cancelled', 10, 1 )->then( spreadArgs( StringTranslations::cancelTranslations() ) );
		WPHooks::onAction( 'wpml_tm_jobs_cancelled', 10, 1 )->then( spreadArgs( function ( $jobs ) {
			/**
			 * We need this check because if we pass only one job to the hook:
			 *  do_action( 'wpml_tm_jobs_cancelled', [ $job ] )
			 * then WordPress converts it to $job.
			 */
			if ( is_object( $jobs ) ) {
				$jobs = [ $jobs ];
			}

			Fns::map( StringTranslations::cancelTranslations(), $jobs );
		} ) );
	}

	/**
	 * We have to defer the initialization of the translation status hooks because in the moment of triggering
	 * `wpml_tm_added_translation_element` hook, the status in `wp_icl_translation_status` does not have its final value.
	 *
	 * If it is automatic ATE job, it can be set to `in-progress` immediately
	 * in `WPML_TM_ATE_Jobs_Actions::added_translation_jobs`.
	 *
	 * @param callable $initializeTranslation
	 *
	 * @return void
	 */
	private static function initializeStringTranslationStatusHooks( callable $initializeTranslation ) {
		$deferredInitializeTranslations = [];
		$deferAddedTranslationElement   = function ( $element, $post ) use ( $initializeTranslation, &$deferredInitializeTranslations ) {
			$deferredInitializeTranslations[] = function () use ( $element, $post, $initializeTranslation ) {
				$initializeTranslation( $element, $post );
			};
		};

		$callDeferredInitializeTranslations = function () use ( &$deferredInitializeTranslations ) {
			foreach ( $deferredInitializeTranslations as $fn ) {
				$fn();
			}
			$deferredInitializeTranslations = [];
		};

		WPHooks::onAction( 'wpml_tm_added_translation_element', 10, 2 )->then( spreadArgs( $deferAddedTranslationElement ) );

		// The priority value must be greater than `WPML_TM_ATE_Jobs_Actions::added_translation_jobs` priority
		// to make sure that the status is set correctly.
		WPHooks::onAction( 'wpml_added_translation_jobs', 11, 0 )->then( $callDeferredInitializeTranslations );
	}
}

