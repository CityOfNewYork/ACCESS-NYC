<?php

namespace ACFML\FieldGroup;

use WPML\FP\Obj;

class AttachedPosts {


	/**
	 * As we're processing batches of 10 posts for each AJAX request,
	 * let's consider 5 seconds per request, so 0.5 second per post as a rough estimation.
	 */
	const PROCESS_TIME_PER_POST = 0.5;

	/**
	 * The SQL query is inspired on `\WPML\TM\Settings\ProcessNewTranslatableFields::getPosts`.
	 * The `meta_key <> ''` part is not totally clear though.
	 *
	 * @see \WPML\TM\Settings\ProcessNewTranslatableFields::getPosts
	 *
	 * @param int $fieldGroupId
	 *
	 * @return int
	 */
	public static function getCount( $fieldGroupId ) {
		/** @var \wpdb $wpdb */
		global $wpdb;

		$fieldKeys = wpml_collect( acf_get_fields( $fieldGroupId ) )
			->map( Obj::prop( 'name' ) )
			->toArray();

		if ( $fieldKeys ) {
			return (int) $wpdb->get_var(
				"SELECT COUNT(DISTINCT(post_id))
				FROM {$wpdb->postmeta}
				WHERE meta_key IN (" . wpml_prepare_in( $fieldKeys ) . ") AND meta_key <> ''"
			);
		}

		return 0;
	}

	/**
	 * @param int $postCount
	 *
	 * @return string
	 */
	public static function getProcessConfirmationMessage( $postCount ) {
		$totalTimeInSeconds = $postCount * self::PROCESS_TIME_PER_POST;

		if ( $totalTimeInSeconds <= MINUTE_IN_SECONDS ) {
			return esc_html__( 'Some posts using this field group have translations. Once you change the translation option, WPML needs to update the translation status of the posts. This can take up to 1 minute.', 'acfml' );
		} elseif ( $totalTimeInSeconds > 1.5 * HOUR_IN_SECONDS ) {
			/* translators: %d is the number of hours. */
			return sprintf( esc_html__( 'Some posts using this field group have translations. Once you change the translation option, WPML needs to update the translation status of the posts. This can take up to %d hours.', 'acfml' ), ceil( $totalTimeInSeconds / HOUR_IN_SECONDS ) );
		}

		/* translators: %d is the number of minutes. */
		return sprintf( esc_html__( 'Some posts using this field group have translations. Once you change the translation option, WPML needs to update the translation status of the posts. This can take up to %d minutes.', 'acfml' ), ceil( $totalTimeInSeconds / MINUTE_IN_SECONDS ) );
	}
}
