<?php

namespace WPML\ST\DB\Mappers;

class Update {
	/**
	 * @param  callable $getStringById
	 * @param  int      $stringId
	 * @param  string   $domain
	 *
	 * @return bool
	 */
	public static function moveStringToDomain( callable $getStringById, $stringId, $domain ) {
		global $wpdb;

		$string = $getStringById( $stringId );
		if ( $string ) {
			$wpdb->update( $wpdb->prefix . 'icl_strings', [ 'context' => $domain ], [ 'id' => $stringId ] );
			self::regenerateMOFiles( $string->context, $domain );

			return true;
		}

		return false;
	}

	/**
	 * @param string $oldDomain
	 * @param string $newDomain
	 *
	 * @return int
	 */
	public static function moveAllStringsToNewDomain( $oldDomain, $newDomain ) {
		global $wpdb;

		$affected = (int) $wpdb->update(
			$wpdb->prefix . 'icl_strings',
			[ 'context' => $newDomain ],
			[ 'context' => $oldDomain ]
		);

		if ( $affected ) {
			self::regenerateMOFiles( $oldDomain, $newDomain );
		}

		return $affected;
	}

	private static function regenerateMOFiles( $oldDomain, $newDomain ) {
		do_action( 'wpml_st_refresh_domain', $oldDomain );
		do_action( 'wpml_st_refresh_domain', $newDomain );
	}
}
