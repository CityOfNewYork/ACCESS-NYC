<?php
/**
 * /premium/click-tracking.php
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

add_action( 'init', 'relevanssi_enable_clicktracking' );
add_action( 'relevanssi_create_tables', 'relevanssi_create_tracking_table', 10, 2 );
add_action( 'relevanssi_trim_click_logs', 'relevanssi_trim_click_logs' );
add_action( 'relevanssi_init', 'relevanssi_schedule_click_tracking_trim' );

/**
 * Adds the click tracking functionality.
 *
 * If the click tracking option is enabled, this function adds the necessary
 * hooked functions to enable the click tracking.
 */
function relevanssi_enable_clicktracking() {
	if ( 'on' !== get_option( 'relevanssi_click_tracking', 'off' ) ) {
		return;
	}
	add_action( 'wp_head', 'relevanssi_log_click' );
	add_action( 'wp_footer', 'relevanssi_remove_clicktracking' );
	add_filter( 'relevanssi_hits_filter', 'relevanssi_record_positions', PHP_INT_MAX );
	add_filter( 'relevanssi_hits_to_show', 'relevanssi_current_page_hits', PHP_INT_MAX );

}

/**
 * Logs the click.
 *
 * Saves the click data to the tracking table. Only logs one click per post per
 * timestamp, to avoid duplicates in case of page reloads. Also uses a nonce to
 * avoid multiple logs.
 */
function relevanssi_log_click() {
	global $post, $relevanssi_variables, $wpdb;

	if ( ! relevanssi_is_ok_to_log() ) {
		return;
	}

	if ( ! isset( $_REQUEST['_rt'] ) || ! is_string( $_REQUEST['_rt'] ) ) {
		return;
	}

	if ( ! isset( $_REQUEST['_rt_nonce'] ) || ! is_string( $_REQUEST['_rt_nonce'] ) ) {
		return;
	}

	$post_id = relevanssi_get_post_identifier( $post );
	if ( is_wp_error( $post_id ) ) {
		return;
	}

	if ( isset( $_REQUEST['_rt_nonce'] ) &&
		! wp_verify_nonce(
			$_REQUEST['_rt_nonce'],
			'relevanssi_click_tracking_' . $post_id
		) ) {
		return;
	}

	$rt = relevanssi_extract_rt( relevanssi_base64url_decode( $_REQUEST['_rt'] ) );
	if ( is_wp_error( $rt ) ) {
		return;
	}

	$wpdb->query(
		$wpdb->prepare(
			"INSERT IGNORE INTO {$relevanssi_variables['tracking_table']} " . // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared
			'(`post_id`, `query`, `rank`, `page`, `timestamp`) VALUES (%d, %s, %d, %d, %s)',
			$post->ID,
			$rt['query'],
			$rt['rank'],
			$rt['page'],
			gmdate( 'c', $rt['time'] )
		)
	);
}

/**
 * Extracts the values from the _rt URL parameter.
 *
 * @param string $rt The URL parameter.
 *
 * @return array|WP_Error An array of values: 'rank', 'page', 'query', and
 * 'time'. Returns a WP_Error if the value doesn't explode into right number of
 * parts.
 */
function relevanssi_extract_rt( string $rt ) {
	$rt_values = explode( '|', $rt );
	if ( count( $rt_values ) !== 4 ) {
		return new WP_Error( 'invalid-rt', __( 'Invalid click tracking value format.', 'relevanssi' ) );
	}
	$rank = intval( $rt_values[0] );
	$page = intval( $rt_values[1] );
	$time = intval( $rt_values[3] );
	if ( 0 === $rank || 0 === $page || 0 === $time ) {
		return new WP_Error( 'invalid-rt', __( 'Invalid click tracking value format.', 'relevanssi' ) );
	}
	return array(
		'rank'  => $rank,
		'page'  => $page,
		'query' => $rt_values[2],
		'time'  => $time,
	);
}

/**
 * Adds tracking information to a permalink.
 *
 * Called from the `relevanssi_permalink` filter function to add the tracking
 * data to the link.
 *
 * @param string $permalink The permalink to modify.
 * @param object $link_post A post object, default null in which case the global
 * $post is used.
 *
 * @global $relevanssi_tracking_positions An array of post ID => rank pairs used
 * to get the post rankings. If a post does not appear in this array, the
 * tracking data is not added to the permalink.
 * @global $relevanssi_tracking_permalink A cache of permalinks to avoid doing
 * work that is already done.
 *
 * @return string The modified permalink.
 */
function relevanssi_add_tracking( string $permalink, $link_post = null ): string {
	if ( 'on' !== get_option( 'relevanssi_click_tracking', 'off' ) ) {
		return $permalink;
	}
	if ( empty( get_search_query() ) ) {
		return $permalink;
	}
	if ( ! relevanssi_is_ok_to_log() ) {
		return $permalink;
	}
	if ( is_numeric( $link_post ) ) {
		$link_post = relevanssi_get_post( $link_post );
	}
	if ( ! $link_post ) {
		global $post;
		$link_post = $post;
	}
	if ( ! is_object( $link_post ) || is_wp_error( $link_post ) ) {
		return $permalink;
	}
	$id = relevanssi_get_post_identifier( $link_post );
	if ( ! isset( $link_post->blog_id ) || get_current_blog_id() === $link_post->blog_id ) {
		if ( relevanssi_is_front_page_id( $link_post->ID ) ) {
			return $permalink;
		}
	}

	global $relevanssi_tracking_positions, $relevanssi_tracking_permalink;
	$position = $relevanssi_tracking_positions[ $id ] ?? null;

	if ( ! $position ) {
		return $permalink;
	}

	if ( isset( $relevanssi_tracking_permalink[ $id ] ) ) {
		return $relevanssi_tracking_permalink[ $id ];
	}

	$page  = get_query_var( 'paged' ) > 0 ? get_query_var( 'paged' ) : 1;
	$nonce = wp_create_nonce( 'relevanssi_click_tracking_' . $id );
	$query = relevanssi_strtolower( str_replace( '|', ' ', get_search_query() ) );
	$time  = time();
	$value = "$position|$page|$query|$time";

	$permalink = add_query_arg(
		array(
			'_rt'       => relevanssi_base64url_encode( $value ),
			'_rt_nonce' => $nonce,
		),
		$permalink
	);

	$relevanssi_tracking_permalink[ $id ] = $permalink;

	return $permalink;
}

/**
 * URL-friendly base64 encode.
 *
 * @param string $data String to encode.
 * @return string Encoded string.
 */
function relevanssi_base64url_encode( string $data ): string {
	return rtrim(
		strtr(
			base64_encode( $data ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
			'+/',
			'-_'
		),
		'='
	);
}

/**
 * URL-friendly base64 decode.
 *
 * @param string $data String to decode.
 * @return string Decoded string.
 */
function relevanssi_base64url_decode( string $data ): string {
	return base64_decode( // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
		strtr( $data, '-_', '+/' )
	);
}

/**
 * Records the ranking positions for the posts found.
 *
 * Runs as the last thing (at PHP_INT_MAX) on the `relevanssi_hits_filter` hook
 * to record the ranking positions of each post.
 *
 * @global array $relevanssi_tracking_positions An array of post ID => rank
 * pairs.
 *
 * @param array $hits The hits found.
 *
 * @return array The hits found, unmodified.
 */
function relevanssi_record_positions( array $hits ): array {
	global $relevanssi_tracking_positions;

	$position = 0;
	foreach ( $hits[0] as $hit ) {
		++$position;
		$hit = relevanssi_get_an_object( $hit )['object'];
		if ( ! $hit ) {
			continue;
		}
		if ( $hit->ID > 0 ) {
			$id = relevanssi_get_post_identifier( $hit );

			$relevanssi_tracking_positions[ $id ] = $position;
		} elseif ( isset( $hit->term_id ) ) {
			$relevanssi_tracking_positions[ $hit->post_type . '_' . $hit->term_id ] = $position;
		} elseif ( isset( $hit->user_id ) ) {
			$relevanssi_tracking_positions[ 'user_' . $hit->user_id ] = $position;
		}
	}

	return $hits;
}

/**
 * Removes the undisplayed posts from the $relevanssi_tracking_positions array.
 *
 * Goes through the $relevanssi_tracking_positions array and only keeps the
 * posts that appear on the current page of results.
 *
 * @global array $relevanssi_tracking_positions An array of post ID => rank
 * pairs.
 *
 * @param array $hits The hits displayed.
 *
 * @return array The hits displayed, unmodified.
 */
function relevanssi_current_page_hits( array $hits ): array {
	global $relevanssi_tracking_positions;

	$all_positions                 = $relevanssi_tracking_positions;
	$relevanssi_tracking_positions = array();

	foreach ( $hits as $hit ) {
		$hit = relevanssi_get_an_object( $hit )['object'];
		$id  = relevanssi_get_post_identifier( $hit );

		if ( $hit->ID > 0 ) {
			$relevanssi_tracking_positions[ $id ] = $all_positions[ $id ];
		} elseif ( isset( $hit->term_id ) ) {
			$id = $hit->post_type . '_' . $hit->term_id;

			$relevanssi_tracking_positions[ $id ] = $all_positions[ $id ];
		} elseif ( isset( $hit->user_id ) ) {
			$id = 'user_' . $hit->user_id;

			$relevanssi_tracking_positions[ $id ] = $all_positions[ $id ];
		}
	}

	return $hits;
}

/**
 * Creates the tracking table.
 *
 * @param string $charset_collate Character set collation.
 *
 * @return void
 */
function relevanssi_create_tracking_table( string $charset_collate ) {
	global $wpdb;

	$sql = 'CREATE TABLE ' . $wpdb->prefix . 'relevanssi_tracking ' .
		"(`id` int(11) NOT NULL AUTO_INCREMENT,
		`post_id` int(11) NOT NULL DEFAULT '0',
		`query` varchar(200) NOT NULL,
		`rank` int(11) NOT NULL DEFAULT '0',
		`page` int(11) NOT NULL DEFAULT '0',
		`timestamp` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY id (id),
		UNIQUE INDEX post_id_timestamp (post_id, timestamp)) $charset_collate";

	dbDelta( $sql );
}

/**
 * Generates an array with date indices and 0 values for each date.
 *
 * Uses the `relevanssi_trim_click_logs` option to determine the length of the
 * date range.
 *
 * @param string $type The type of date count: 'clicks', 'log' or 'both'.
 *
 * @return array An array of 'Y-m-d' date indices.
 */
function relevanssi_default_date_count( string $type ): array {
	global $wpdb, $relevanssi_variables;

	if ( 'clicks' === $type ) {
		$amount_of_days = get_option( 'relevanssi_trim_click_logs', 90 );
	}
	if ( 'log' === $type ) {
		$amount_of_days = get_option( 'relevanssi_trim_logs', 30 );
		if ( 0 === $amount_of_days ) {
			$amount_of_days = abs( $wpdb->get_var( "SELECT TIMESTAMPDIFF(DAY, NOW(), time) FROM {$relevanssi_variables['log_table']} ORDER BY time ASC LIMIT 1" ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared.
		}
	}
	if ( 'both' === $type ) {
		$click_days = get_option( 'relevanssi_trim_click_logs', 90 );
		$log_days   = get_option( 'relevanssi_trim_logs', 30 );

		if ( '0' === $log_days ) {
			$log_days = abs( $wpdb->get_var( "SELECT TIMESTAMPDIFF(DAY, NOW(), time) FROM {$relevanssi_variables['log_table']} ORDER BY time ASC LIMIT 1" ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared.
		}
		$amount_of_days = max( $click_days, $log_days );
	}
	$date_counts = array();
	$start_date  = gmdate( 'Y-m-d', strtotime( intval( $amount_of_days ) . ' days ago' ) );
	$end_date    = gmdate( 'Y-m-d' );

	while ( strtotime( $start_date ) <= strtotime( $end_date ) ) {
		$date_counts[ $start_date ] = 0;

		$start_date = gmdate(
			'Y-m-d',
			strtotime( '+1 days', strtotime( $start_date ) )
		);
	}

	return $date_counts;
}

/**
 * Determines what happens when a request for a post insights screen is made.
 *
 * @param array $request The $_REQUEST array to dig for parameters.
 *
 * @return bool True, if a screen was displayed and false if not.
 */
function relevanssi_handle_insights_screens( array $request ): bool {
	if ( isset( $request['insights'] ) ) {
		if ( isset( $request['action'] ) && isset( $request['query'] ) && 'delete_query' === $request['action'] ) {
			check_admin_referer( 'relevanssi_delete_query' );
			relevanssi_delete_query( $request['query'] );
		}
		if ( isset( $request['action'] ) && isset( $request['query'] ) && 'delete_query_from_log' === $request['action'] ) {
			check_admin_referer( 'relevanssi_delete_query' );
			relevanssi_delete_query_from_log( $request['query'] );
		}
		relevanssi_show_insights( stripslashes( $request['insights'] ) );
		return true;
	}

	if ( isset( $request['post_insights'] ) ) {
		relevanssi_show_post_insights( $request['post_insights'] );
		return true;
	}

	return false;
}

/**
 * Deletes a query from the click tracking database.
 *
 * @param string $query The query to delete.
 */
function relevanssi_delete_query( string $query ) {
	global $wpdb, $relevanssi_variables;

	$deleted = $wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$relevanssi_variables['tracking_table']} WHERE query = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared
			stripslashes( $query )
		)
	);

	if ( $deleted ) {
		printf(
			"<div id='message' class='updated fade'><p>%s</p></div>",
			sprintf(
				// Translators: %s is the stopword.
				esc_html__(
					"The query '%s' deleted from the click tracking log.",
					'relevanssi'
				),
				esc_html( stripslashes( $query ) )
			)
		);
	} else {
		printf(
			"<div id='message' class='updated fade'><p>%s</p></div>",
			sprintf(
				// Translators: %s is the stopword.
				esc_html__(
					"Couldn't remove the query '%s' from the click tracking log.",
					'relevanssi'
				),
				esc_html( stripslashes( $query ) )
			)
		);

	}
}

/**
 * Displays the search query insights screen.
 *
 * Prints out the display for a single search query insights screen.
 *
 * @param string $query The search query.
 */
function relevanssi_show_insights( string $query ) {
	global $wpdb, $relevanssi_variables;

	?>
	<a href="<?php echo get_admin_url( null, '?page=relevanssi_user_searches' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>"
	><?php esc_html_e( 'Back to the User Searches page', 'relevanssi' ); ?></a>
	<?php

	printf(
		'<h2>' .
		// Translators: %s is the search query string.
		esc_html__( 'Search insights for %s', 'relevanssi' ) .
		'</h2>',
		esc_html( '"' . $query . '"' )
	);

	$results = $wpdb->get_results(
		$wpdb->prepare(
			'SELECT * FROM ' . $relevanssi_variables['tracking_table'] // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared
			. ' WHERE query = %s',
			$query
		)
	);

	$posts             = array();
	$post_average_rank = array();
	$post_average_page = array();
	$date_counts       = relevanssi_default_date_count( 'both' );
	$oldest_date       = array_keys( $date_counts )[0];
	foreach ( $results as $row ) {
		if ( $row->timestamp < $oldest_date ) {
			continue;
		}
		relevanssi_increase_value( $posts[ $row->post_id ] );
		relevanssi_increase_value( $post_average_rank[ $row->post_id ], $row->rank );
		relevanssi_increase_value( $post_average_page[ $row->post_id ], $row->page );
		relevanssi_increase_value( $date_counts[ gmdate( 'Y-m-d', strtotime( $row->timestamp ) ) ] );
	}

	relevanssi_average_array( $post_average_rank, $posts );
	relevanssi_average_array( $post_average_page, $posts );

	$results = $wpdb->get_results(
		$wpdb->prepare(
			'SELECT * FROM ' . $relevanssi_variables['log_table'] // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared
			. ' WHERE query = %s',
			$query
		)
	);

	$date_numbers = relevanssi_default_date_count( 'both' );

	foreach ( $results as $row ) {
		relevanssi_increase_value( $date_numbers[ gmdate( 'Y-m-d', strtotime( $row->time ) ) ] );
	}
	ksort( $date_numbers );
	arsort( $posts );

	$dates_array = $date_counts + $date_numbers;
	ksort( $dates_array );
	$dates = array_map(
		function ( $v ) {
			return gmdate( 'M j', strtotime( $v ) );
		},
		array_keys( $dates_array )
	);

	relevanssi_create_line_chart(
		$dates,
		array(
			__( '# of Searches', 'relevanssi' ) => array_values( $date_numbers ),
			__( '# of Clicks', 'relevanssi' )   => array_values( $date_counts ),
		)
	);
	if ( count( $posts ) > 0 ) {
		?>

	<h2><?php esc_html_e( 'Posts found with this search term', 'relevanssi' ); ?></h2>

	<table class="widefat" style="margin-bottom: 2em">
	<thead>
	<tr>
	<th><?php esc_html_e( 'Post', 'relevanssi' ); ?></th>
	<th><?php esc_html_e( 'Times clicked', 'relevanssi' ); ?></th>
	<th><?php esc_html_e( 'Avg rank', 'relevanssi' ); ?></th>
	<th><?php esc_html_e( 'Avg page', 'relevanssi' ); ?></th>
	</tr>
	</thead>
	<tbody>
		<?php
		foreach ( $posts as $post_id => $count ) {
			$insights_url = relevanssi_get_insights_url( intval( $post_id ) );
			$insights     = sprintf( "<a href='%s'>%s</a>", esc_url( $insights_url ), get_the_title( $post_id ) );

			$link = get_permalink( $post_id );
			$edit = get_edit_post_link( $post_id );
			?>
			<tr>
				<td><?php echo $insights; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				(<a href="<?php echo esc_attr( $link ); ?>"><?php esc_html_e( 'View post', 'relevanssi' ); ?></a>
				| <a href="<?php echo esc_attr( $edit ); ?>"><?php esc_html_e( 'Edit post', 'relevanssi' ); ?></a>)</td>
				<td><?php echo esc_html( $count ); ?></td>
				<td><?php echo esc_html( $post_average_rank[ $post_id ] ); ?></td>
				<td><?php echo esc_html( $post_average_page[ $post_id ] ); ?></td>
			</tr>
			<?php
		}
		?>
	</tbody>
	</table>

	<div style="width: 48%; float: left">
	<h2><?php esc_html_e( 'Remove this query from the click log', 'relevanssi' ); ?></h2>

	<form method="post">
		<input type="hidden" name="action" value="delete_query" />
		<input type="hidden" name="query" value="<?php echo esc_attr( $query ); ?>" />
			<?php wp_nonce_field( 'relevanssi_delete_query' ); ?>
		<input type="submit" value="<?php esc_attr_e( 'Delete', 'relevanssi' ); ?>" id="delete_query" />
	</form>
	</div>
		<?php
	}
	?>
	<div style="width: 48%; float: left">
	<h2><?php esc_html_e( 'Remove this query from the search log', 'relevanssi' ); ?></h2>

	<form method="post">
		<input type="hidden" name="action" value="delete_query_from_log" />
		<input type="hidden" name="query" value="<?php echo esc_attr( $query ); ?>" />
			<?php wp_nonce_field( 'relevanssi_delete_query' ); ?>
		<input type="submit" value="<?php esc_attr_e( 'Delete', 'relevanssi' ); ?>" id="delete_query" />
	</form>
	</div>
	<?php
}

/**
 * Returns the URL to post or query insights page.
 *
 * @param int|string $target If int, return link to post insights page for that
 * post ID; if string, return link to query insights page.
 *
 * @return string The link to the insights page.
 */
function relevanssi_get_insights_url( $target ): string {
	global $relevanssi_variables;

	$parameter = is_int( $target ) ? 'post_insights' : 'insights';

	return admin_url(
		'admin.php?page=relevanssi_user_searches'
	) . '&' . $parameter . '=' . rawurlencode( $target );
}

/**
 * Displays the post insights screen.
 *
 * Prints out the display for a single post insights screen.
 *
 * @param string $post_id_string The post ID (as a string, because it's coming
 * from the query variable).
 */
function relevanssi_show_post_insights( string $post_id_string ) {
	global $wpdb, $relevanssi_variables;

	$post_id = intval( $post_id_string );
	$title   = get_the_title( $post_id );

	?>
	<a href="<?php echo get_admin_url( null, '?page=relevanssi_user_searches' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>"
	><?php esc_html_e( 'Back to the User Searches page', 'relevanssi' ); ?></a>
	<?php

	printf(
		'<h2>' .
		// Translators: %s is the search query string.
		esc_html__( 'Search insights for %s', 'relevanssi' ) .
		'</h2>',
		esc_html( '"' . $title . '"' )
	);

	$link = get_permalink( $post_id );
	$edit = get_edit_post_link( $post_id );
	?>
	<p><a href="<?php echo esc_attr( $link ); ?>"><?php esc_html_e( 'View post', 'relevanssi' ); ?></a>
	| <a href="<?php echo esc_attr( $edit ); ?>"><?php esc_html_e( 'Edit post', 'relevanssi' ); ?></a>
	</p>
	<?php

	$results = $wpdb->get_results(
		$wpdb->prepare(
			'SELECT * FROM ' . $relevanssi_variables['tracking_table'] // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared
			. ' WHERE post_id = %d',
			$post_id
		)
	);

	$queries            = array();
	$query_average_rank = array();
	$query_average_page = array();
	$date_counts        = relevanssi_default_date_count( 'clicks' );

	foreach ( $results as $row ) {
		relevanssi_increase_value( $queries[ $row->query ] );
		relevanssi_increase_value( $query_average_rank[ $row->query ], $row->rank );
		relevanssi_increase_value( $query_average_page[ $row->query ], $row->page );
		relevanssi_increase_value( $date_counts[ gmdate( 'Y-m-d', strtotime( $row->timestamp ) ) ] );
	}

	relevanssi_average_array( $query_average_rank, $queries );
	relevanssi_average_array( $query_average_page, $queries );

	arsort( $queries );

	$dates = array_map(
		function ( $v ) {
			return gmdate( 'M j', strtotime( $v ) );
		},
		array_keys( $date_counts )
	);

	relevanssi_create_line_chart(
		$dates,
		array(
			__( '# of Clicks', 'relevanssi' ) => array_values( $date_counts ),
		)
	);

	?>

	<h2><?php esc_html_e( 'Search queries for this post', 'relevanssi' ); ?></h2>

	<table class="widefat">
	<thead>
	<tr>
	<th><?php esc_html_e( 'Query', 'relevanssi' ); ?></th>
	<th><?php esc_html_e( 'Times clicked', 'relevanssi' ); ?></th>
	<th><?php esc_html_e( 'Avg rank', 'relevanssi' ); ?></th>
	<th><?php esc_html_e( 'Avg page', 'relevanssi' ); ?></th>
	</tr>
	</thead>
	<tbody>
	<?php
	foreach ( $queries as $query => $count ) {
		$insights_url = relevanssi_get_insights_url( $query );
		$insights     = sprintf( "<a href='%s'>%s</a>", esc_url( $insights_url ), $query );
		?>
		<tr>
			<td><?php echo $insights; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
			<td><?php echo esc_html( $count ); ?></td>
			<td><?php echo esc_html( $query_average_rank[ $query ] ); ?></td>
			<td><?php echo esc_html( $query_average_page[ $query ] ); ?></td>
		</tr>
		<?php
	}
	?>
	</tbody>
	</table>
	<?php
}

/**
 * Fetches the number of clicks for a query.
 *
 * Only fetches the data once for all queries and then serves results from the
 * cache.
 *
 * @global array $relevanssi_variables Used for the database table name, and the
 * cache is stored in the 'query_clicks' in this array.
 *
 * @param string $query The search query.
 *
 * @return int The number of clicks for the query, default 0.
 */
function relevanssi_get_query_clicks( string $query ): int {
	global $wpdb, $relevanssi_variables;

	if ( isset( $relevanssi_variables['query_clicks'] ) ) {
		return $relevanssi_variables['query_clicks'][ $query ] ?? 0;
	}

	$data = $wpdb->get_results(
		'SELECT LOWER(query) AS query, COUNT(*) AS count '
		. "FROM {$relevanssi_variables['tracking_table']} " // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared
		. 'GROUP BY query'
	);

	$relevanssi_variables['query_clicks'] = array_combine(
		wp_list_pluck( $data, 'query' ),
		wp_list_pluck( $data, 'count' )
	);

	return $relevanssi_variables['query_clicks'][ $query ] ?? 0;
}

/**
 * Prints out the user interface for setting the click tracking options.
 */
function relevanssi_click_tracking_interface() {
	$click_tracking  = relevanssi_check( get_option( 'relevanssi_click_tracking' ) );
	$trim_click_logs = get_option( 'relevanssi_trim_click_logs' );

	?>
	<h2><?php esc_html_e( 'Click tracking', 'relevanssi' ); ?></h2>

	<p><?php esc_html_e( 'Enabling this option will add click tracking information to the post URLs on Relevanssi search results pages, allowing to you see stats on which posts are clicked and what their rankings are. You can find the stats on individual post edit pages in the Relevanssi sidebar, or from the User searches page by clicking the search term.', 'relevanssi' ); ?></p>

	<p><?php esc_html_e( 'Click tracking stores post ID, the search query and ranking information for the post. No personal information about the user doing the search are stored.', 'relevanssi' ); ?></p>

	<table id="relevanssi_clicktracking" class="form-table" role="presentation">
	<tr>
		<th scope="row">
			<?php esc_html_e( 'Enable click tracking', 'relevanssi' ); ?>
		</th>
		<td>
			<label for='relevanssi_click_tracking'>
				<input
					type='checkbox'
					name='relevanssi_click_tracking'
					id='relevanssi_click_tracking'
					<?php echo esc_html( $click_tracking ); ?>
				/>
				<?php esc_html_e( 'Enable click tracking on Relevanssi search results pages.', 'relevanssi' ); ?>
			</label>
		</td>
	</tr>

	<tr>
		<th scope="row">
			<label for='relevanssi_trim_click_logs'><?php esc_html_e( 'Trim logs', 'relevanssi' ); ?></label>
		</th>
		<td>
			<input type='number' name='relevanssi_trim_click_logs' id='relevanssi_trim_click_logs' value='<?php echo esc_attr( $trim_click_logs ); ?>' />
			<?php esc_html_e( 'How many days of click tracking logs to keep in the database.', 'relevanssi' ); ?>
			<?php
			echo '<p class="description">';
			// Translators: %d is the setting for no trim (probably 0).
			printf( esc_html__( 'Set to %d for no trimming. The click tracking logs will be smaller than the search logs, so this value can be bigger than the value for regular logs.', 'relevanssi' ), 0 );
			echo '</p>';
			?>
		</td>
	</tr>

	<tr>
		<th scope="row">
			<?php esc_html_e( 'Export click logs', 'relevanssi' ); ?>
		</th>
		<td>
			<?php submit_button( __( 'Export the click tracking log as a CSV file', 'relevanssi' ), 'secondary', 'relevanssi_export_clicks' ); ?>
			<p class="description"><?php esc_html_e( 'Push the button to export the click tracking log as a CSV file.', 'relevanssi' ); ?></p>
		</td>
	</tr>

	</table>
	<?php
}

/**
 * Shows the click tracking info on the User Searches screen.
 *
 * @global $wpdb The WordPress database interface.
 * @global $relevanssi_variables The global Relevanssi variables, used for the
 * click tracking log table name.
 *
 * @param string $from  The start date.
 * @param string $to    The end date.
 * @param int    $total The amount of queries in total.
 *
 * @return void
 */
function relevanssi_user_searches_clicks( string $from, string $to, int $total ) {
	global $wpdb, $relevanssi_variables;
	$click_table = $relevanssi_variables['tracking_table'];

	$total_clicks = $wpdb->get_var(
		$wpdb->prepare(
			'SELECT COUNT(*) FROM ' . $click_table . ' ' . // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'WHERE timestamp >= %s AND timestamp <= %s',
			$from . ' 00:00:00',
			$to . ' 23:59:59'
		)
	);

	$click_ratio = 0;
	if ( $total > 0 ) {
		$click_ratio = round( 100 * $total_clicks / $total, 1 );
	}
	?>
	<div><?php esc_html_e( 'Total clicks', 'relevanssi' ); ?>
		<div style="display: grid; grid-template-columns: 1fr 2fr; grid-gap: 5px">
			<div style="font-size: 42px; font-weight: bolder; line-height: 50px">
				<?php echo intval( $total_clicks ); ?>
			</div>
			<div style="font-size: 16px; font-weight: normal; padding-top: 10px">
				<?php // Translators: %s is the percentage of queries that are clicked. ?>
				(<?php printf( esc_html__( '%s %% of all queries', 'relevanssi' ), esc_html( $click_ratio ) ); ?>)
			</div>
		</div>
	</div>

	<h3><?php esc_html_e( 'Click tracking insights', 'relevanssi' ); ?></h3>
	<?php

	$something_printed = false;

	$results = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT post_id, COUNT(*) AS hits, AVG(`rank`) AS average
			FROM $click_table " . // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'WHERE timestamp >= %s AND timestamp <= %s
			GROUP BY post_id ORDER BY hits DESC',
			$from . ' 00:00:00',
			$to . ' 23:59:59'
		)
	);

	$top_ten = array_slice( $results, 0, 10 );

	$list = array();
	foreach ( $top_ten as $result ) {
		$title        = get_the_title( $result->post_id );
		$insights_url = relevanssi_get_insights_url( intval( $result->post_id ) );

		$list[] = '<li><a href="' . esc_url( $insights_url ) . '">'
			. wp_kses_post( $title ) . '</a> (' . intval( $result->hits )
			. ')</li>';
	}

	if ( count( $list ) > 0 ) {
		?>
		<div>
			<p><?php echo esc_html__( 'These posts got the most clicks. This is content users like!', 'relevanssi' ); ?></p>
			<ul>
		<?php
		echo '<ul>' . implode( "\n", $list ) . '</ul>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		$something_printed = true;
		?>
		</div>
		<?php
	}
	usort(
		$results,
		function ( $a, $b ) {
			return $b->average - $a->average;
		}
	);

	$top_ten = array_slice( $results, 0, 10 );

	$list = array();
	foreach ( $top_ten as $result ) {
		$title        = get_the_title( $result->post_id );
		$insights_url = relevanssi_get_insights_url( intval( $result->post_id ) );

		$list[] = '<li><a href="' . esc_url( $insights_url ) . '">' . wp_kses_post( $title ) . '</a> ('
			. round( $result->average, 0 ) . ')</li>';
	}
	if ( count( $list ) > 0 ) {
		?>
		<div>
			<p><?php echo esc_html__( 'These posts were got clicks from a low ranking. Should they be boosted higher?', 'relevanssi' ); ?></p>
			<ul>
		<?php
		echo '<ul>' . implode( "\n", $list ) . '</ul>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		$something_printed = true;
		?>
		</div>
		<?php
	}

	$results = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT query, COUNT(DISTINCT(post_id)) AS posts
			FROM $click_table " . // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'WHERE timestamp >= %s AND timestamp <= %s
			GROUP BY query ORDER BY posts DESC LIMIT 10',
			$from . ' 00:00:00',
			$to . ' 23:59:59'
		)
	);

	$list = array();
	foreach ( $results as $result ) {
		if ( $result->posts < 3 ) {
			continue;
		}
		$insights_url = relevanssi_get_insights_url( $result->query );

		$list[] = '<li><a href="' . esc_url( $insights_url ) . '">'
			. esc_html( $result->query ) . '</a> ('
			// Translators: %1$s is the number of posts.
			. sprintf( __( '%1$s posts', 'relevanssi' ), intval( $result->posts ) )
			. ')</li>';
	}
	if ( count( $list ) > 0 ) {
		?>
		<p><?php echo esc_html__( 'You have search queries that generate clicks to many posts. Perhaps more focus would be good?', 'relevanssi' ); ?></p>
		<?php
		echo '<ul>' . implode( "\n", $list ) . '</ul>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		$something_printed = true;
	}

	if ( ! $something_printed ) {
		?>
		<p><?php esc_html_e( 'Sorry, no clicks recorded for this period!', 'relevanssi' ); ?></p>
		<?php
	}
}

/**
 * Adds a small bit of JS to remove the click tracking tags from the URL.
 *
 * Modifies the page URL to remove the _rt and _rt_nonce tags from the URL to
 * avoid them from being copy-pasted on.
 */
function relevanssi_remove_clicktracking() {
	if ( 'on' !== get_option( 'relevanssi_click_tracking', 'off' ) ) {
		return;
	}
	$script = <<<EOJS
	var relevanssi_rt_regex = /(&|\?)_(rt|rt_nonce)=(\w+)/g
	var newUrl = window.location.search.replace(relevanssi_rt_regex, '')
	history.replaceState(null, null, window.location.pathname + newUrl + window.location.hash)
EOJS;
	if ( function_exists( 'wp_print_inline_script_tag' ) ) {
		// Introduced in 5.7.0.
		wp_print_inline_script_tag( $script );
	} else {
		echo '<script>' . $script . '</script>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

/**
 * Creates a link to the search query insights page.
 *
 * @param object $query The query log row object, used for the query string in
 * $query->query.
 *
 * @return string The HTML link tag to link to the insights page.
 */
function relevanssi_insights_link( $query ): string {
	$insights_url = admin_url( 'admin.php?page=relevanssi_user_searches' )
		. '&insights=' . rawurlencode( $query->query );
	$insights     = sprintf( "<a href='%s'>%s</a>", esc_url( $insights_url ), esc_html( relevanssi_hyphenate( $query->query ) ) );
	return $insights;
}

/**
 * Trims Relevanssi click tracking table.
 *
 * Trims Relevanssi click tracking table, using the day interval setting from
 * 'relevanssi_trim_click_logs'.
 *
 * @global object $wpdb                 The WordPress database interface.
 * @global array  $relevanssi_variables The global Relevanssi variables, used
 * for database table names.
 *
 * @return int|bool Number of rows deleted, or false on error.
 */
function relevanssi_trim_click_logs() {
	global $wpdb, $relevanssi_variables;
	$interval = intval( get_option( 'relevanssi_trim_click_logs' ) );
	return $wpdb->query(
		$wpdb->prepare(
			'DELETE FROM ' . $relevanssi_variables['tracking_table'] . // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			' WHERE timestamp < TIMESTAMP(DATE_SUB(NOW(), INTERVAL %d DAY))',
			$interval
		)
	);
}

/**
 * Sets up the Relevanssi click tracking log trimming action.
 */
function relevanssi_schedule_click_tracking_trim() {
	if ( get_option( 'relevanssi_trim_click_logs' ) > 0 ) {
		if ( ! wp_next_scheduled( 'relevanssi_trim_click_logs' ) ) {
			wp_schedule_event( time(), 'daily', 'relevanssi_trim_click_logs' );
		}
	} elseif ( wp_next_scheduled( 'relevanssi_trim_click_logs' ) ) {
		wp_clear_scheduled_hook( 'relevanssi_trim_click_logs' );
	}
}

/**
 * Prints out the Relevanssi click tracking log as a CSV file.
 *
 * Exports the whole Relevanssi click tracking log as a CSV file.
 *
 * @uses relevanssi_output_exported_log
 */
function relevanssi_export_click_log() {
	global $wpdb, $relevanssi_variables;

	$data = $wpdb->get_results( 'SELECT * FROM ' . $relevanssi_variables['tracking_table'], ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

	relevanssi_output_exported_log(
		'relevanssi_click_log.csv',
		$data,
		__( 'No search clicks logged.', 'relevanssi' )
	);
}

/**
 * Returns the post ID prefixed with the blog ID.
 *
 * @param object $post_object The post object.
 *
 * @return string|WP_Error Post ID, "blog ID-post ID" or a WP_Error in case of
 * failure.
 */
function relevanssi_get_post_identifier( $post_object ) {
	if ( ! isset( $post_object->ID ) ) {
		return new WP_Error( 'no_post_id', 'No post ID attribute.' );
	}
	if ( is_multisite() ) {
		if ( isset( $post_object->blog_id ) ) {
			return $post_object->blog_id . '-' . $post_object->ID;
		} else {
			return get_current_blog_id() . '-' . $post_object->ID;
		}
	} else {
		return $post_object->ID;
	}
}
