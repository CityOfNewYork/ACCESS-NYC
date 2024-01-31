<?php

namespace WPML\ST\Batch\Translation;

use WPML\FP\Curryable;
use WPML\FP\Fns;
use WPML\FP\Lst;
use function WPML\Container\make;
use function WPML\FP\curryN;

/**
 * @phpstan-type curried '__CURRIED_PLACEHOLDER__'
 *
 * @method static callable|void installSchema( ...$wpdb ) :: wpdb → void
 * @method static callable|void set( ...$wpdb, ...$batchId, ...$stringId ) :: wpdb → int → int → void
 * @method static callable|int[] findBatches( ...$wpdb, ...$stringId ) :: wpdb → int → int[]
 */
class Records {

	use Curryable;

	/** @var string */
	public static $string_batch_sql_prototype = '
	CREATE TABLE IF NOT EXISTS `%sicl_string_batches` (
	  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
	  `string_id` bigint(20) unsigned NOT NULL,
	  `batch_id` bigint(20) unsigned NOT NULL,
	  PRIMARY KEY (`id`)
		)
	';

	/**
	 * @param \wpdb|null $wpdb
	 * @param int|curried $batchId
	 * @return int|callable
	 *
	 * @phpstan-return ($batchId is not null ? int : callable)
	 */
	public static function get( \wpdb $wpdb = null, $batchId = null ) {
		return call_user_func_array(
			curryN(
				2,
				function ( \wpdb $wpdb, $batchId ) {
					/** @var string $sql */
					$sql = $wpdb->prepare( "SELECT string_id FROM {$wpdb->prefix}icl_string_batches WHERE batch_id = %d", $batchId );
					return $wpdb->get_col( $sql );
				}
			),
			func_get_args()
		);
	}

}

Records::curryN(
	'installSchema',
	1,
	function ( \wpdb $wpdb ) {
		$option = make( 'WPML\WP\OptionManager' );
		if ( ! $option->get( 'ST', Records::class . '_schema_installed' ) ) {
			$wpdb->query( sprintf( Records::$string_batch_sql_prototype, $wpdb->prefix ) );
			$option->set( 'ST', Records::class . '_schema_installed', true );
		}
	}
);

Records::curryN(
	'set',
	3,
	function ( \wpdb $wpdb, $batchId, $stringId ) {
		// TODO: ignore duplicates
		$wpdb->insert(
			"{$wpdb->prefix}icl_string_batches",
			[
				'batch_id'  => $batchId,
				'string_id' => $stringId,
			],
			[ '%d', '%d' ]
		);
	}
);

Records::curryN(
	'findBatch',
	2,
	function ( \wpdb $wpdb, $stringId ) {
		/** @var string $sql */
		$sql = $wpdb->prepare( "SELECT batch_id FROM {$wpdb->prefix}icl_string_batches WHERE string_id = %d", $stringId );
		return $wpdb->get_var( $sql );
	}
);

Records::curryN(
	'findBatches',
	2,
	function ( \wpdb $wpdb, $stringIds ) {
		$in   = wpml_prepare_in( $stringIds, '%d' );
		$data = $wpdb->get_results(
			"SELECT batch_id, string_id FROM {$wpdb->prefix}icl_string_batches WHERE string_id IN ({$in})"
		);

		$keyByStringId = Fns::converge( Lst::zipObj(), [ Lst::pluck( 'string_id' ), Lst::pluck( 'batch_id' ) ] );

		return $keyByStringId( $data );
	}
);
