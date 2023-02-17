<?php

namespace WPML\ST\Batch\Translation;

use WPML\FP\Curryable;
use WPML\FP\Fns;
use WPML\FP\Lst;
use function WPML\Container\make;

/**
 * @method static callable|void installSchema( ...$wpdb ) :: wpdb → void
 * @method static callable|int get( ...$wpdb, ...$batchId ) :: wpdb → int → [int]
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
	'get',
	2,
	function ( \wpdb $wpdb, $batchId ) {
		return $wpdb->get_col(
			$wpdb->prepare( "SELECT string_id FROM {$wpdb->prefix}icl_string_batches WHERE batch_id = %d", $batchId )
		);
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
		return $wpdb->get_var(
			$wpdb->prepare( "SELECT batch_id FROM {$wpdb->prefix}icl_string_batches WHERE string_id = %d", $stringId )
		);
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
