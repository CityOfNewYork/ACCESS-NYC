<?php

use \WPML\Collect\Support\Traits\Macroable;
use function \WPML\FP\curryN;
use \WPML\LIB\WP\Cache;
use \WPML\FP\Logic;

/**
 * Class TranslationProxy_Batch
 *
 * @method static callable|int getBatchId( ...$name ) :: string → int
 */
class TranslationProxy_Batch {

	use Macroable;

	public static function update_translation_batch(
		$batch_name = false,
		$tp_id = false
	) {
		$batch_name = $batch_name
			? $batch_name
			: ( ( (bool) $tp_id === false || $tp_id === 'local' )
				? self::get_generic_batch_name() : TranslationProxy_Basket::get_basket_name() );
		if ( ! $batch_name ) {
			return null;
		}

		$getBatchId = function( $batch_name, $tp_id ) {
			$batch_id = self::getBatchId( $batch_name );

			return $batch_id ?: self::createBatchRecord( $batch_name, $tp_id );
		};

		$cache = Cache::memorizeWithCheck( 'update_translation_batch', Logic::isNotNull(), 0, $getBatchId );
		return $cache( $batch_name, $tp_id );
	}

	/**
	 * returns the name of a generic batch
	 * name is built based on the current's date
	 *
	 * @param bool $isAuto
	 *
	 * @return string
	 */
	public static function get_generic_batch_name( $isAuto = false ) {
		if ( ! $isAuto && defined( 'WPML_DEBUG_TRANSLATION_PROXY' )  )
			\WPML\Utilities\DebugLog::storeBackTrace();

		return ( $isAuto ? 'Automatic Translations from ' : 'Manual Translations from ' ) . date( 'F \t\h\e jS\, Y' );
	}

	/**
	 * returns the id of a generic batch
	 *
	 * @return int
	 */
	private static function create_generic_batch() {
		$batch_name = self::get_generic_batch_name();
		$batch_id   = self::update_translation_batch( $batch_name );

		return $batch_id;
	}

	public static function maybe_assign_generic_batch( $data ) {
		global $wpdb;

		$batch_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT batch_id
														 FROM {$wpdb->prefix}icl_translation_status
														 WHERE translation_id=%d",
				$data['translation_id']
			)
		);

		// if the batch id is smaller than 1 we assign the translation to the generic manual translations batch for today if the translation_service is local
		if ( ( $batch_id < 1 ) && isset( $data ['translation_service'] ) && $data ['translation_service'] == 'local' ) {
			// first we retrieve the batch id for today's generic translation batch
			$batch_id = self::create_generic_batch();
			// then we update the entry in the icl_translation_status table accordingly
			$data_where = array( 'rid' => $data['rid'] );
			$wpdb->update(
				$wpdb->prefix . 'icl_translation_status',
				array( 'batch_id' => $batch_id ),
				$data_where
			);
		}
	}

	/**
	 * @param $batch_name
	 * @param $tp_id
	 *
	 * @return mixed
	 */
	private static function createBatchRecord( $batch_name, $tp_id ) {
		global $wpdb;

		$data = [
			'batch_name'  => $batch_name,
			'last_update' => date( 'Y-m-d H:i:s' ),
		];
		if ( $tp_id ) {
			$data['tp_id'] = $tp_id === 'local' ? 0 : $tp_id;
		}
		$wpdb->insert( $wpdb->prefix . 'icl_translation_batches', $data );

		return $wpdb->insert_id;
	}
}

/**
 * @param $batch_name
 *
 * @return mixed
 */
TranslationProxy_Batch::macro(
	'getBatchId',
	curryN(
		1,
		function( $batch_name ) {
			global $wpdb;

			$batch_id_sql      = "SELECT id FROM {$wpdb->prefix}icl_translation_batches WHERE batch_name=%s";
			$batch_id_prepared = $wpdb->prepare( $batch_id_sql, $batch_name );
			return $wpdb->get_var( $batch_id_prepared );
		}
	)
);

