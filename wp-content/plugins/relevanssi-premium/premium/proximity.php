<?php
/**
 * /premium/proximity.php
 *
 * @package Relevanssi_Premium
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

add_action( 'init', 'relevanssi_enable_proximity_sorting' );

/**
 * Adds the proximity sorting filters.
 *
 * If the relevanssi_proximity_sorting filter returns true, adds the required
 * filters to make the proximity searching work.
 */
function relevanssi_enable_proximity_sorting() {
	/**
	 * Controls whether the proximity searching is enabled or not.
	 *
	 * @param boolean If true, enable proximity sorting. Default false.
	 */
	if ( apply_filters( 'relevanssi_proximity_sorting', false ) ) {
		add_filter( 'relevanssi_results', 'relevanssi_add_distance', 1 );
		add_filter( 'relevanssi_search_params', 'relevanssi_pick_up_coordinates', 10, 2 );
	}
}

/**
 * Counts the distances to the found posts.
 *
 * The coordinates from each post come from the relevanssi_proximity_coordinates
 * filter hook based on the post ID. The comparison coordinates come from the
 * global $relevanssi_coordinates variable, filtered by the
 * relevanssi_proximity_comparison filter hook. The coordinates need to be in
 * the floating point format, separated with a comma, latitude first.
 *
 * The distance for each post to the comparison coordinates is stored to the
 * global $relevanssi_distance array for the post ID.
 *
 * @uses relevanssi_get_distance()
 *
 * @param array $doc_weight An array of post ID => weight pairs. This function
 * ignores the weight and only cares about the post ID.
 *
 * @return array The $doc_weight array untouched.
 */
function relevanssi_add_distance( $doc_weight ) {
	global $relevanssi_coordinates;

	/**
	 * Filters the comparison coordinates Relevanssi uses.
	 *
	 * @param string The coordinates in "latitude, longitude" format.
	 */
	$compare_coordinates = apply_filters(
		'relevanssi_proximity_comparison',
		$relevanssi_coordinates // // phpcs:ignore WordPress.Security.NonceVerification
	);

	if ( ! $compare_coordinates ) {
		return $doc_weight;
	}

	list( $latitude_from, $longitude_from ) = explode( ',', $compare_coordinates );

	$latitude_from  = floatval( $latitude_from );
	$longitude_from = floatval( $longitude_from );

	global $relevanssi_distance;
	$relevanssi_distance = array();

	foreach ( array_keys( $doc_weight ) as $post_id ) {
		/**
		 * Filters the coordinates for each post.
		 *
		 * @param string The coordinates.
		 * @param int    The post ID.
		 */
		$hit_coordinates = apply_filters(
			'relevanssi_proximity_coordinates',
			'',
			$post_id
		);
		if ( ! $hit_coordinates ) {
			/**
			 * Filters the default distance for posts without coordinates.
			 *
			 * @param int The default distance, default PHP_INT_MAX.
			 */
			$default_distance                = apply_filters(
				'relevanssi_proximity_default_distance',
				PHP_INT_MAX
			);
			$relevanssi_distance[ $post_id ] = $default_distance;
		}

		list( $latitude_to, $longitude_to ) = explode( ',', $hit_coordinates );

		$latitude_to  = floatval( $latitude_to );
		$longitude_to = floatval( $longitude_to );

		$distance = relevanssi_get_distance(
			$latitude_from,
			$longitude_from,
			$latitude_to,
			$longitude_to
		);

		$relevanssi_distance[ $post_id ] = $distance;
	}

	return $doc_weight;
}

/**
 * Calculates the great-circle distance between two points.
 *
 * Uses the Haversine formula.
 *
 * @param float $latitude_from  Latitude of start point in [deg decimal].
 * @param float $longitude_from Longitude of start point in [deg decimal].
 * @param float $latitude_to    Latitude of target point in [deg decimal].
 * @param float $longitude_to   Longitude of target point in [deg decimal].
 *
 * @return float Distance between points in kilometers.
 */
function relevanssi_get_distance( float $latitude_from, float $longitude_from, float $latitude_to, float $longitude_to ): float {
	$earth_radius = 6371;

	$lat_from = deg2rad( $latitude_from );
	$lon_from = deg2rad( $longitude_from );
	$lat_to   = deg2rad( $latitude_to );
	$lon_to   = deg2rad( $longitude_to );

	$lat_delta = $lat_to - $lat_from;
	$lon_delta = $lon_to - $lon_from;

	$angle = 2 * asin(
		sqrt(
			pow( sin( $lat_delta / 2 ), 2 )
			+ cos( $lat_from ) * cos( $lat_to )
			* pow( sin( $lon_delta / 2 ), 2 )
		)
	);

	return $angle * $earth_radius;
}

/**
 * Returns the distances for compared posts.
 *
 * Gets the distances from the $relevanssi_distance global array.
 *
 * @param object $post_a The first post object.
 * @param object $post_b The second post object.
 *
 * @return array Array containing the distance to post A and to post B. Default
 * value is 0.
 */
function relevanssi_get_proximity_values( $post_a, $post_b ) {
	global $relevanssi_distance;

	$distance_to_a = $relevanssi_distance[ $post_a->ID ] ?? 0;
	$distance_to_b = $relevanssi_distance[ $post_b->ID ] ?? 0;
	return array( $distance_to_a, $distance_to_b );
}

/**
 * Takes the 'coordinates' query variable and stores it to a global variable.
 *
 * Stores the comparison coordinates from the 'coordinates' query variable in
 * the $relevanssi_coordinates global variable, because that is the easiest way
 * to access that data in the relevanssi_add_distance() function.
 *
 * @see relevanssi_add_distance().
 *
 * @param array    $params The search parameters; ignored.
 * @param WP_Query $query  The query object.
 *
 * @return array The search parameters untouched.
 */
function relevanssi_pick_up_coordinates( $params, $query ) {
	global $relevanssi_coordinates;
	$relevanssi_coordinates = $query->query_vars['coordinates'];
	return $params;
}
