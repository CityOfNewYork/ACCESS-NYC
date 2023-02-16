<?php


namespace WPML\TM\Editor;


use WPML\LIB\WP\Option;

class ATERetry {

	/**
	 * @param int $jobId
	 *
	 * @return bool
	 */
	public static function hasFailed( $jobId ) {
		return self::getCount( $jobId ) >= 0;
	}

	/**
	 * @param int $jobId
	 *
	 * @return int
	 */
	public static function getCount( $jobId ) {
		return (int) Option::getOr( self::getOptionName( $jobId ), - 1 );
	}

	/**
	 * @param int $jobId
	 */
	public static function incrementCount( $jobId ) {
		Option::update( self::getOptionName( $jobId ), self::getCount( $jobId ) + 1 );
	}

	/**
	 * @param int $jobId
	 */
	public static function reset( $jobId ) {
		Option::delete( self::getOptionName( $jobId ) );
	}

	/**
	 * @param int $jobId
	 *
	 * @return string
	 */
	public static function getOptionName( $jobId ) {
		return sprintf( 'wpml-ate-job-retry-counter-%d', $jobId );
	}
}