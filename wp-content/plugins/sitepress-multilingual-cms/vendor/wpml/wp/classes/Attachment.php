<?php

namespace WPML\LIB\WP;

use WPML\FP\Logic;
use WPML\FP\Str;
use function WPML\FP\pipe;

class Attachment {
	private static $cache = [];

	private static $withOutSizeRegEx = '/-\d+[Xx]\d+\./';

	/**
	 * @param string $url
	 *
	 * @return int|null The found post ID, or null on failure.
	 */
	public static function idFromUrl( $url ) {
		if ( array_key_exists( $url , self::$cache ) ) {
			return self::$cache[$url];
		}

		$urlWithoutSize = Str::pregReplace( self::$withOutSizeRegEx, '.', $url );
		if ( array_key_exists( $urlWithoutSize , self::$cache ) ) {
			return self::$cache[$urlWithoutSize];
		}

		if ( $url !== $urlWithoutSize && $id = attachment_url_to_postid( $urlWithoutSize ) ) {
			self::$cache[ $url ]            = $id;
			self::$cache[ $urlWithoutSize ] = $id;
			return $id;
		}

		if ( $id = attachment_url_to_postid( $url ) ) {
			self::$cache[ $url ] = $id;
			return $id;
		}

		if ( $id = self::idByGuid( $url ) ) {
			self::$cache[ $url ] = $id;
			return $id;
		}

		if ( $url !== $urlWithoutSize && $id = self::idByGuid( $urlWithoutSize ) ) {
			self::$cache[ $url ]            = $id;
			self::$cache[ $urlWithoutSize ] = $id;
			return $id;
		}

		self::$cache[ $url ] = null;
		return null;
	}

	/**
	 * @param string $url
	 *
	 * @return int The found post ID, or 0 on failure.
	 */
	public static function idByGuid( $url ) {
		if ( array_key_exists( $url , self::$cache ) ) {
			return self::$cache[$url];
		}

		global $wpdb;

		$attachment = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE guid='%s' LIMIT 1", $url ) );

		return $attachment && count( $attachment )
			? $attachment[0]
			: 0;
	}


	public static function addToCache( $media ) {
		self::$cache = array_merge( self::$cache, $media );
	}
}
