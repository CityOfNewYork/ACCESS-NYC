<?php
/**
 * /premium/admin-ajax.php
 *
 * @package Relevanssi_Premium
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

add_action( 'wp_ajax_relevanssi_list_pdfs', 'relevanssi_list_pdfs_action' );
add_action( 'wp_ajax_relevanssi_wipe_pdfs', 'relevanssi_wipe_pdfs_action' );
add_action( 'wp_ajax_relevanssi_wipe_server_errors', 'relevanssi_wipe_server_errors_action' );
add_action( 'wp_ajax_relevanssi_index_pdfs', 'relevanssi_index_pdfs_action' );
add_action( 'wp_ajax_relevanssi_send_pdf', 'relevanssi_send_pdf' );
add_action( 'wp_ajax_relevanssi_send_url', 'relevanssi_send_url' );
add_action( 'wp_ajax_relevanssi_get_pdf_errors', 'relevanssi_get_pdf_errors_action' );
add_action( 'wp_ajax_relevanssi_index_taxonomies', 'relevanssi_index_taxonomies_ajax_wrapper' );
add_action( 'wp_ajax_relevanssi_count_taxonomies', 'relevanssi_count_taxonomies_ajax_wrapper' );
add_action( 'wp_ajax_relevanssi_index_post_type_archives', 'relevanssi_index_post_type_archives_ajax_wrapper' );
add_action( 'wp_ajax_relevanssi_index_users', 'relevanssi_index_users_ajax_wrapper' );
add_action( 'wp_ajax_relevanssi_count_users', 'relevanssi_count_users_ajax_wrapper' );
add_action( 'wp_ajax_relevanssi_list_taxonomies', 'relevanssi_list_taxonomies_wrapper' );
add_action( 'wp_ajax_relevanssi_related_posts', 'relevanssi_get_related_posts' );
add_action( 'wp_ajax_relevanssi_related_remove', 'relevanssi_add_to_exclude_list' );
add_action( 'wp_ajax_relevanssi_related_return', 'relevanssi_remove_from_exclude_list' );
add_action( 'wp_ajax_relevanssi_pin_post', 'relevanssi_pin_post' );
add_action( 'wp_ajax_relevanssi_unpin_post', 'relevanssi_unpin_post' );
add_action( 'wp_ajax_relevanssi_get_words', 'relevanssi_ajax_get_words' );
add_action( 'wp_ajax_nopriv_relevanssi_get_words', 'relevanssi_ajax_get_words' );
add_action( 'wp_ajax_relevanssi_index_pdf', 'relevanssi_ajax_index_pdf' );

/**
 * Performs the "list PDF files" AJAX action.
 *
 * Uses relevanssi_get_posts_with_attachments() to get a list of posts with files
 * attached to them.
 *
 * @since 2.0.0
 */
function relevanssi_list_pdfs_action() {
	check_ajax_referer( 'relevanssi-list-pdfs', 'security' );
	relevanssi_current_user_can_access_options();

	$limit = 0;
	if ( isset( $_POST['limit'] ) ) { // WPCS: input var ok.
		$limit = intval( wp_unslash( $_POST['limit'] ) ); // WPCS: input var ok.
	}
	$pdfs = relevanssi_get_posts_with_attachments( $limit );
	echo wp_json_encode( $pdfs );

	wp_die();
}

/**
 * Performs the "wipe PDF content" AJAX action.
 *
 * Removes all '_relevanssi_pdf_content' and '_relevanssi_pdf_error' post meta
 * fields from the wp_postmeta table. However, if '_relevanssi_pdf_modified' is
 * set, the content is not removed for that post.
 *
 * @since 2.0.0
 */
function relevanssi_wipe_pdfs_action() {
	check_ajax_referer( 'relevanssi-wipe-pdfs', 'security' );
	relevanssi_current_user_can_access_options();

	$deleted_content = relevanssi_delete_all_but(
		'_relevanssi_pdf_content',
		'_relevanssi_pdf_modified',
		'1'
	);
	$deleted_errors  = delete_post_meta_by_key( '_relevanssi_pdf_error' );

	$response                    = array();
	$response['deleted_content'] = false;
	$response['deleted_errors']  = false;

	if ( $deleted_content ) {
		$response['deleted_content'] = true;
	}
	if ( $deleted_errors ) {
		$response['deleted_errors'] = true;
	}

	echo wp_json_encode( $response );

	wp_die();
}

/**
 * Performs the "wipe server errors" AJAX action.
 *
 * Removes all '_relevanssi_pdf_error' post meta fields from the wp_postmeta
 * table where the meta_value is 'R_ERR06: Server did not respond.'.
 */
function relevanssi_wipe_server_errors_action() {
	check_ajax_referer( 'relevanssi-wipe-errors', 'security' );
	relevanssi_current_user_can_access_options();

	global $wpdb;
	$result = $wpdb->delete(
		$wpdb->postmeta,
		array(
			'meta_key'   => '_relevanssi_pdf_error',
			'meta_value' => 'R_ERR06: Server did not respond.',
		),
		array(
			'%s',
			'%s',
		)
	);

	$response['deleted_rows'] = $result;

	echo wp_json_encode( $response );

	wp_die();
}

/**
 * Deletes all post meta for certain meta key unless another meta key is set.
 *
 * @global object $wpdb The WordPress database interface.
 *
 * @param string $meta_key      The meta key to delete.
 * @param string $exclusion_key The conditional meta key.
 * @param string $value         Value for the conditional meta to check.
 *
 * @return boolean True if something was deleted, false if not.
 *
 * @since 2.5.0
 */
function relevanssi_delete_all_but( $meta_key, $exclusion_key, $value ) {
	global $wpdb;

	$query = $wpdb->prepare(
		"SELECT meta_id FROM $wpdb->postmeta WHERE meta_key = %s
		AND post_id NOT IN (
			SELECT post_id FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value = %s
		)",
		$meta_key,
		$exclusion_key,
		$value
	);

	$meta_ids = $wpdb->get_col( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	if ( ! count( $meta_ids ) ) {
		return false;
	}

	$query = "DELETE FROM $wpdb->postmeta WHERE meta_id IN ( " . implode( ',', $meta_ids ) . ' )';

	$count = $wpdb->query( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

	if ( ! $count ) {
		return false;
	}

	return true;
}

/**
 * Performs the "index PDFs" AJAX action.
 *
 * Reads in the PDF content for PDF files fetched using the relevanssi_get_posts_with_attachments() function.
 *
 * @since 2.0.0
 */
function relevanssi_index_pdfs_action() {
	check_ajax_referer( 'relevanssi-index-pdfs', 'security' );
	relevanssi_current_user_can_access_options();

	$pdfs = relevanssi_get_posts_with_attachments( 3 );

	if ( ! isset( $_POST['completed'] ) || ! isset( $_POST['total'] ) ) { // WPCS: input var ok.
		wp_die();
	}

	$post_data = $_POST; // WPCS: input var ok.

	$completed = absint( $post_data['completed'] );
	$total     = absint( $post_data['total'] );

	$response             = array();
	$response['feedback'] = '';

	if ( empty( $pdfs ) ) {
		$response['feedback']   = __( 'Indexing complete!', 'relevanssi' );
		$response['completed']  = 'done';
		$response['percentage'] = 100;
	} else {
		foreach ( $pdfs as $post_id ) {
			$echo_and_die = false;
			$send_files   = get_option( 'relevanssi_send_pdf_files' );
			if ( 'off' === $send_files ) {
				$send_files = false;
			}

			$index_response = relevanssi_index_pdf( $post_id, $echo_and_die, $send_files );
			++$completed;

			if ( $index_response['success'] ) {
				// translators: placeholder is the post ID.
				$response['feedback'] .= sprintf( esc_html__( 'Successfully indexed attachment id %d.', 'relevanssi' ), esc_html( $post_id ) ) . "\n";
			} else {
				// translators: the numeric placeholder is the post ID, the string is the error message.
				$response['feedback'] .= sprintf( esc_html__( 'Failed to index attachment id %1$d: %2$s', 'relevanssi' ), esc_html( $post_id ), esc_html( $index_response['error'] ) ) . "\n";
			}
		}
		$response['completed'] = $completed;
		if ( $total > 0 ) {
			$response['percentage'] = round( $completed / $total * 100, 0 );
		} else {
			$response['percentage'] = 0;
		}
	}

	echo wp_json_encode( $response );

	wp_die();
}

/**
 * Performs the "send PDF" AJAX action.
 *
 * Reads in the PDF content for one PDF file, based on the 'post_id' parameter, sending the PDF over.
 *
 * @since 2.0.0
 */
function relevanssi_send_pdf() {
	check_ajax_referer( 'relevanssi_send_pdf', 'security' );
	if ( ! current_user_can( 'upload_files' ) ) {
		wp_die();
	}

	if ( ! isset( $_REQUEST['post_id'] ) ) { // WPCS: input var ok.
		wp_die();
	}
	$post_id      = intval( wp_unslash( $_REQUEST['post_id'] ) ); // WPCS: input var ok.
	$echo_and_die = true;
	$send_file    = true;
	relevanssi_index_pdf( $post_id, $echo_and_die, $send_file );

	// Just for sure; relevanssi_index_pdf() should echo necessary responses and die, so don't expect this to ever happen.
	wp_die();
}

/**
 * Performs the "send URL" AJAX action.
 *
 * Reads in the PDF content for one PDF file, based on the 'post_id' parameter, using the PDF URL.
 *
 * @since 2.0.0
 */
function relevanssi_send_url() {
	check_ajax_referer( 'relevanssi_send_pdf', 'security' );
	if ( ! current_user_can( 'upload_files' ) ) {
		wp_die();
	}

	if ( ! isset( $_REQUEST['post_id'] ) ) { // WPCS: input var ok.
		wp_die();
	}
	$post_id      = intval( wp_unslash( $_REQUEST['post_id'] ) ); // WPCS: input var ok.
	$echo_and_die = true;
	$send_file    = false;
	relevanssi_index_pdf( $post_id, $echo_and_die, $send_file );

	// Just for sure; relevanssi_index_pdf() should echo necessary responses and die, so don't expect this to ever happen.
	wp_die();
}

/**
 * Reads all PDF errors.
 *
 * Gets a list of all PDF errors in the database and prints out a list of them.
 *
 * @global $wpdb The WordPress database interface, used to fetch the meta fields.
 *
 * @since 2.0.0
 */
function relevanssi_get_pdf_errors_action() {
	if ( ! current_user_can( 'upload_files' ) ) {
		wp_die();
	}

	global $wpdb;

	$errors        = $wpdb->get_results( "SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = '_relevanssi_pdf_error'" );
	$error_message = array();
	foreach ( $errors as $error ) {
		$row             = __( 'Attachment ID', 'relevanssi' ) . ' ' . $error->post_id . ': ' . $error->meta_value;
		$row             = str_replace( 'PDF Processor error: ', '', $row );
		$error_message[] = $row;
	}

	echo wp_json_encode( implode( "\n", $error_message ) );
	wp_die();
}

/**
 * Reads a list of taxonomies.
 *
 * Gets a list of taxonomies selected for indexing from the relevanssi_list_taxonomies() function.
 *
 * @since 2.0.0
 */
function relevanssi_list_taxonomies_wrapper() {
	relevanssi_current_user_can_access_options();

	$taxonomies = array();
	if ( function_exists( 'relevanssi_list_taxonomies' ) ) {
		$taxonomies = relevanssi_list_taxonomies();
	}
	echo wp_json_encode( $taxonomies );
	wp_die();
}

/**
 * Indexes taxonomy terms for AJAX indexing.
 *
 * Reads in the parameters, indexes taxonomy terms and reports the results.
 *
 * @since 2.0.0
 */
function relevanssi_index_taxonomies_ajax_wrapper() {
	check_ajax_referer( 'relevanssi_taxonomy_indexing_nonce', 'security' );
	relevanssi_current_user_can_access_options();

	if ( ! isset( $_POST['completed'] ) || ! isset( $_POST['total'] ) || ! isset( $_POST['taxonomy'] ) || ! isset( $_POST['offset'] ) || ! isset( $_POST['limit'] ) ) { // WPCS: input var ok.
		wp_die();
	}

	$post_data = $_POST; // WPCS: input var ok.

	$completed = absint( $post_data['completed'] );
	$total     = absint( $post_data['total'] );
	$taxonomy  = relevanssi_validate_taxonomy( $post_data['taxonomy'] );
	$offset    = intval( $post_data['offset'] );
	$limit     = intval( $post_data['limit'] );

	if ( empty( $taxonomy ) ) {
		// Non-valid taxonomy.
		wp_die();
	}

	$response = array();

	$indexing_response = relevanssi_index_taxonomies_ajax( $taxonomy, $limit, $offset );

	$completed += $indexing_response['indexed'];
	if ( $completed === $total ) {
		$response['completed']   = 'done';
		$response['total_posts'] = $completed;
		$response['percentage']  = 100;
		// translators: number of terms indexed on this go, total indexed terms, total number of terms.
		$response['feedback'] = sprintf( _n( '%1$d taxonomy term, total %2$d / %3$d.', '%1$d taxonomy terms, total %2$d / %3$d.', $indexing_response['indexed'], 'relevanssi' ), $indexing_response['indexed'], $completed, $total ) . "\n";
	} else {
		$response['completed'] = $completed;
		// translators: number of terms indexed on this go, total indexed terms, total number of terms.
		$response['feedback'] = sprintf( _n( '%1$d taxonomy term, total %2$d / %3$d.', '%1$d taxonomy terms, total %2$d / %3$d.', $indexing_response['indexed'], 'relevanssi' ), $indexing_response['indexed'], $completed, $total ) . "\n";

		if ( $total > 0 ) {
			$response['percentage'] = $completed / $total * 100;
		} else {
			$response['percentage'] = 0;
		}

		$response['new_taxonomy'] = false;
		if ( 'done' === $indexing_response['taxonomy_completed'] ) {
			$response['new_taxonomy'] = true;
		}
	}
	$response['offset'] = $offset + $limit;

	echo wp_json_encode( $response );
	wp_die();
}

/**
 * Indexes post type archives for AJAX indexing.
 *
 * Indexes post type archives and reports the results. The post type archives are
 * always indexed all at one go; I don't think there's often a case where there are
 * too many.
 *
 * @since 2.2.0
 */
function relevanssi_index_post_type_archives_ajax_wrapper() {
	check_ajax_referer( 'relevanssi_post_type_archive_indexing_nonce', 'security' );
	relevanssi_current_user_can_access_options();

	$response = array();

	if ( 'on' !== get_option( 'relevanssi_index_post_type_archives' ) ) {
		$response['feedback'] = __( 'disabled.', 'relevanssi' ) . "\n";
	} else {
		$indexing_response = relevanssi_index_post_type_archives_ajax();

		$response['completed']   = 'done';
		$response['total_posts'] = $indexing_response['indexed'];
		$response['percentage']  = 100;
		// translators: number of terms indexed on this go, total indexed terms, total number of terms.
		$response['feedback'] = sprintf( _n( '%1$d post type archive indexed.', '%1$d post type archives indexed.', $indexing_response['indexed'], 'relevanssi' ), $indexing_response['indexed'] ) . "\n";
	}

	echo wp_json_encode( $response );
	wp_die();
}

/**
 * Indexes users for AJAX indexing.
 *
 * Reads in the parameters, indexes users and reports the results.
 *
 * @since 2.0.0
 */
function relevanssi_index_users_ajax_wrapper() {
	check_ajax_referer( 'relevanssi_user_indexing_nonce', 'security' );
	relevanssi_current_user_can_access_options();

	if ( ! isset( $_POST['completed'] ) || ! isset( $_POST['total'] ) || ! isset( $_POST['limit'] ) ) { // WPCS: input var ok.
		wp_die();
	}

	$post_data = $_POST; // WPCS: input var ok.

	$completed = absint( $post_data['completed'] );
	$total     = absint( $post_data['total'] );
	$limit     = $post_data['limit'];
	if ( isset( $post_data['offset'] ) ) {
		$offset = $post_data['offset'];
	} else {
		$offset = 0;
	}

	$response = array();

	$indexing_response = relevanssi_index_users_ajax( $limit, $offset );

	$completed += $indexing_response['indexed'];
	$processed  = $offset;

	if ( $completed === $total || $processed > $total ) {
		$response['completed']   = 'done';
		$response['total_posts'] = $completed;
		$response['percentage']  = 100;
		$processed               = $total;
	} else {
		$response['completed'] = $completed;
		$offset                = $offset + $limit;

		if ( $total > 0 ) {
			$response['percentage'] = $completed / $total * 100;
		} else {
			$response['percentage'] = 0;
		}
	}

	// translators: number of users indexed on this go, total indexed users, total processed users, total number of users.
	$response['feedback'] = sprintf( _n( 'Indexed %1$d user (total %2$d), processed %3$d / %4$d.', 'Indexed %1$d users (total %2$d), processed %3$d / %4$d.', $indexing_response['indexed'], 'relevanssi' ), $indexing_response['indexed'], $completed, $processed, $total ) . "\n";
	$response['offset']   = $offset;

	echo wp_json_encode( $response );
	wp_die();
}

/**
 * Counts the users.
 *
 * Counts the users for indexing purposes using the relevanssi_count_users() function.
 *
 * @since 2.0.0
 */
function relevanssi_count_users_ajax_wrapper() {
	relevanssi_current_user_can_access_options();

	$count = -1;
	if ( function_exists( 'relevanssi_count_users' ) ) {
		$count = relevanssi_count_users();
	}
	echo wp_json_encode( $count );
	wp_die();
}

/**
 * Counts the taxonomy terms.
 *
 * Counts the taxonomy terms for indexing purposes using the relevanssi_count_taxonomy_terms() function.
 *
 * @since 2.0.0
 */
function relevanssi_count_taxonomies_ajax_wrapper() {
	relevanssi_current_user_can_access_options();

	$count = -1;
	if ( function_exists( 'relevanssi_count_taxonomy_terms' ) ) {
		$count = relevanssi_count_taxonomy_terms();
	}
	echo wp_json_encode( $count );
	wp_die();
}

/**
 * Creates a list of related posts.
 *
 * @since 2.2.4
 */
function relevanssi_get_related_posts() {
	check_ajax_referer( 'relevanssi_metabox_nonce', 'security' );
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die();
	}

	$post_id = (int) $_POST['post_id']; // WPCS: input var ok.

	if ( 0 === $post_id ) {
		wp_die();
	}

	if ( isset( $_POST['keywords'] ) ) {
		$keywords = sanitize_text_field( $_POST['keywords'] ); // WPCS: input var ok.

		delete_post_meta( $post_id, '_relevanssi_related_keywords' );
		add_post_meta( $post_id, '_relevanssi_related_keywords', $keywords );

		// Keywords have changed, flush the cache.
		delete_post_meta( $post_id, '_relevanssi_related_posts' );
	}

	if ( isset( $_POST['ids'] ) ) {
		$include_ids_array = explode( ',', $_POST['ids'] );
		$valid_ids         = array();
		foreach ( $include_ids_array as $id ) {
			if ( get_post( $id ) ) {
				$valid_ids[] = $id;
			}
		}
		if ( ! empty( $valid_ids ) ) {
			$id_string = implode( ',', $valid_ids );
			delete_post_meta( $post_id, '_relevanssi_related_include_ids' );
			add_post_meta( $post_id, '_relevanssi_related_include_ids', $id_string );
		} else {
			delete_post_meta( $post_id, '_relevanssi_related_include_ids' );
		}

		// Included IDs have changed, flush the cache.
		delete_post_meta( $post_id, '_relevanssi_related_posts' );
	}

	$list = relevanssi_generate_related_list( $post_id );

	$response = array(
		'list' => $list,
	);

	echo wp_json_encode( $response );
	wp_die();
}

/**
 * Adds a post ID to the excluded ID list.
 *
 * @since 2.2.4
 */
function relevanssi_add_to_exclude_list() {
	check_ajax_referer( 'relevanssi_metabox_nonce', 'security' );
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die();
	}

	$post_id = (int) $_POST['post_id']; // WPCS: input var ok.

	if ( 0 === $post_id ) {
		wp_die();
	}

	if ( isset( $_POST['remove_id'] ) ) {
		relevanssi_exclude_a_related_post( $post_id, $_POST['remove_id'] );
	}

	$related  = relevanssi_generate_related_list( $post_id );
	$excluded = relevanssi_generate_excluded_list( $post_id );

	$response = array(
		'related'  => $related,
		'excluded' => $excluded,
	);

	echo wp_json_encode( $response );
	wp_die();
}

/**
 * Adds a post ID to the list of excluded post IDs of a post.
 *
 * @param int $post_id       Add to this post IDs exclude list.
 * @param int $excluded_post Add this post ID to the exclude list.
 */
function relevanssi_exclude_a_related_post( $post_id, $excluded_post ) {
	$remove_id    = (int) $excluded_post;
	$excluded_ids = trim( get_post_meta( $post_id, '_relevanssi_related_exclude_ids', true ) );
	if ( $excluded_ids ) {
		$excluded_ids = explode( ',', $excluded_ids );
	} else {
		$excluded_ids = array();
	}
	$excluded_ids[] = $remove_id;
	$excluded_ids   = array_keys( array_flip( $excluded_ids ) );
	$excluded_ids   = implode( ',', $excluded_ids );
	update_post_meta( $post_id, '_relevanssi_related_exclude_ids', $excluded_ids );

	// Excluded IDs have changed, flush the cache.
	delete_post_meta( $post_id, '_relevanssi_related_posts' );
}

/**
 * Removes a post ID from the excluded ID list and regenerates the lists.
 *
 * @since 2.2.4
 */
function relevanssi_remove_from_exclude_list() {
	check_ajax_referer( 'relevanssi_metabox_nonce', 'security' );
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die();
	}

	$post_id = (int) $_POST['post_id']; // WPCS: input var ok.

	if ( 0 === $post_id ) {
		wp_die();
	}

	if ( isset( $_POST['return_id'] ) ) {
		relevanssi_unexclude_a_related_post( $post_id, $_POST['return_id'] );
	}

	$related  = relevanssi_generate_related_list( $post_id );
	$excluded = relevanssi_generate_excluded_list( $post_id );

	$response = array(
		'related'  => $related,
		'excluded' => $excluded,
	);

	echo wp_json_encode( $response );
	wp_die();
}

/**
 * Removes a post ID from the related posts exclude list.
 *
 * @param int $post_id         The post ID that owns the related posts exclude list.
 * @param int $unexcluded_post The post ID which is removed from the exclude list.
 */
function relevanssi_unexclude_a_related_post( $post_id, $unexcluded_post ) {
	$return_id    = (int) $unexcluded_post;
	$excluded_ids = trim( get_post_meta( $post_id, '_relevanssi_related_exclude_ids', true ) );
	$excluded_ids = array_flip( explode( ',', $excluded_ids ) );
	unset( $excluded_ids[ $return_id ] );
	$excluded_ids = array_keys( $excluded_ids );
	$excluded_ids = implode( ',', $excluded_ids );
	update_post_meta( $post_id, '_relevanssi_related_exclude_ids', $excluded_ids );

	// Excluded IDs have changed, flush the cache.
	delete_post_meta( $post_id, '_relevanssi_related_posts' );
}

/**
 * Adds a keyword to the pinned keywords list for a post.
 *
 * @since 2.2.6
 */
function relevanssi_pin_post() {
	check_ajax_referer( 'relevanssi_admin_search_nonce', 'security' );
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die();
	}

	$post_id = (int) $_POST['post_id']; // WPCS: input var ok.
	$keyword = $_POST['keyword']; // WPCS: input var ok.

	if ( 0 === $post_id || empty( $keyword ) ) {
		wp_die();
	}

	$result         = false;
	$already_pinned = false;
	$pins           = get_post_meta( $post_id, '_relevanssi_pin' );
	foreach ( $pins as $pin ) {
		if ( $pin === $keyword ) {
			$already_pinned = true;
			break;
		}
	}
	if ( ! $already_pinned ) {
		$result = add_post_meta( $post_id, '_relevanssi_pin', $keyword );
	}

	$response = array(
		'success' => $result,
	);

	echo wp_json_encode( $response );
	wp_die();
}

/**
 * Removes a keyword from the pinned keywords list for a post.
 *
 * @since 2.2.6
 */
function relevanssi_unpin_post() {
	check_ajax_referer( 'relevanssi_admin_search_nonce', 'security' );
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die();
	}

	$post_id = (int) $_POST['post_id']; // WPCS: input var ok.
	$keyword = $_POST['keyword']; // WPCS: input var ok.

	if ( 0 === $post_id || empty( $keyword ) ) {
		wp_die();
	}

	$result = delete_post_meta( $post_id, '_relevanssi_pin', $keyword );

	$response = array(
		'success' => $result,
	);

	echo wp_json_encode( $response );
	wp_die();
}

/**
 * Fetches database words to the relevanssi_words option.
 *
 * An AJAX wrapper for relevanssi_update_words_option().
 *
 * @see relevanssi_update_words_option()
 *
 * @since 2.5.0
 */
function relevanssi_ajax_get_words() {
	if ( ! wp_verify_nonce( $_REQUEST['_nonce'], 'relevanssi_get_words' ) ) {
		wp_die();
	}

	relevanssi_update_words_option();

	wp_die();
}

/**
 * Launches the attachment content indexing.
 *
 * Sets the _relevanssi_pdf_error for the post to show RELEVANSSI_ERROR_05
 * (ie. "work in progress"), then launches the indexing and dies off.
 *
 * @since 2.5.0
 */
function relevanssi_ajax_index_pdf() {
	if ( ! wp_verify_nonce( $_REQUEST['_nonce'], 'relevanssi_index_pdf' ) ) {
		wp_die();
	}

	if ( ! current_user_can( 'upload_files' ) ) {
		wp_die();
	}

	$post_id = intval( $_REQUEST['post_id'] );

	update_post_meta( $post_id, '_relevanssi_pdf_error', RELEVANSSI_ERROR_05 );

	relevanssi_index_pdf( $post_id );

	wp_die();
}
