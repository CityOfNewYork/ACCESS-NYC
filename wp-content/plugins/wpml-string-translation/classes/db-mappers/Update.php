<?php

namespace WPML\ST\DB\Mappers;


class Update {
	/**
	 * @param  int  $stringId
	 * @param  string  $domain
	 *
	 * @return bool
	 */
	public static function moveStringToDomain( $stringId, $domain ) {
		global $wpdb;

		return $wpdb->update( $wpdb->prefix . 'icl_strings', [ 'context' => $domain ], [ 'id' => $stringId ] ) > 0;
	}

	/**
	 * @param $oldDomain
	 * @param $newDomain
	 *
	 * @return int
	 */
	public static function moveAllStringsToNewDomain( $oldDomain, $newDomain ) {
		global $wpdb;

		return (int) $wpdb->update(
			$wpdb->prefix . 'icl_strings',
			[ 'context' => $newDomain ],
			[ 'context' => $oldDomain ]
		);
	}
}