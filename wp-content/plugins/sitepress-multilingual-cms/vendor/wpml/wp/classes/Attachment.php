<?php

namespace WPML\LIB\WP;

use WPML\FP\Logic;
use WPML\FP\Str;
use function WPML\FP\pipe;

class Attachment {

	private static $withOutSizeRegEx = '/-\d+[Xx]\d+\./';

	/**
	 * @param string $url
	 *
	 * @return int|null The found post ID, or null on failure.
	 */
	public static function idFromUrl( $url ) {
		$removeSize = Str::pregReplace( self::$withOutSizeRegEx, '.' );

		return Logic::firstSatisfying(
			function ( $id ) { return $id !== 0; },
			[
				'attachment_url_to_postid',
				pipe( $removeSize, 'attachment_url_to_postid' ),
				[ self::class, 'idByGuid' ],
				pipe( $removeSize, [ self::class, 'idByGuid' ] ),
			],
			$url
		);
	}

	/**
	 * @param string $url
	 *
	 * @return int The found post ID, or 0 on failure.
	 */
	public static function idByGuid( $url ) {
		global $wpdb;

		$attachment = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE guid='%s'", $url ) );

		return $attachment && count( $attachment )
			? $attachment[0]
			: 0;
	}

}
