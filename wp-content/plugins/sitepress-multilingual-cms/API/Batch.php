<?php

namespace WPML\TM\API;


use WPML\FP\Curryable;
use WPML\FP\Fns;
use WPML\FP\Obj;
use WPML\TM\Jobs\Dispatch\Messages;
use function WPML\Container\make;

/**
 * Class Batch
 * @package WPML\TM\API
 *
 * @method static callable|void rollback( ...$batchName ) - Curried :: string->void
 *
 * It rollbacks just sent batch.
 */
class Batch {

	use Curryable;

	public static function init() {

		self::curryN( 'rollback', 1, function ( $basketName ) {
			$batch = make( \WPML_Translation_Basket::class )->get_basket_batch( $basketName );
			$batch->cancel_all_jobs();
			$batch->clear_batch_data();
		} );


	}

	public static function sendPosts( Messages $messages, $batch, $sendFrom = Jobs::SENT_VIA_BASKET ) {
		$dispatchActions = function ( $batch ) use ( $sendFrom ) {
			$allowedTypes = array_keys( \TranslationProxy_Basket::get_basket_items_types() );

			foreach ( $allowedTypes as $type ) {
				do_action( 'wpml_tm_send_' . $type . '_jobs', $batch, $type, $sendFrom );
			}
		};

		self::send( $dispatchActions, [ $messages, 'showForPosts' ], $batch );
	}

	public static function sendStrings( Messages $messages, $batch ) {
		$dispatchActions = function ( $batch ) {
			do_action( 'wpml_tm_send_st-batch_jobs', $batch, 'st-batch' );
		};

		self::send( $dispatchActions, [ $messages, 'showForStrings' ], $batch );
	}

	private static function send( callable $dispatchAction, callable $displayErrors, $batch ) {
		$dispatchAction( $batch );

		$errors = wpml_load_core_tm()->messages_by_type( 'error' );

		if ( $errors ) {
			self::rollback( $batch->get_basket_name() );

			$displayErrors( Fns::map( Obj::prop( 'text' ), $errors ), 'error' );
		}
	}
}

Batch::init();
