<?php
/**
 * /premium/pdf-upload.php
 *
 * @package Relevanssi_Premium
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

add_action( 'add_attachment', 'relevanssi_read_attachment', 10 );
add_action( 'add_meta_boxes_attachment', 'relevanssi_add_pdf_metaboxes' );
add_filter( 'relevanssi_hits_to_show', 'relevanssi_prime_pdf_content', 10, 2 );
add_filter( 'relevanssi_index_custom_fields', 'relevanssi_add_pdf_customfield' );
add_filter( 'relevanssi_pre_excerpt_content', 'relevanssi_add_pdf_content_to_excerpt', 10, 2 );
add_filter( 'wp_media_attach_action', 'relevanssi_media_attach_action', 10, 3 );

define( 'RELEVANSSI_ERROR_01', 'R_ERR01: ' . __( 'Post excluded from the index by the user.', 'relevanssi' ) );
define( 'RELEVANSSI_ERROR_02', 'R_ERR02: ' . __( 'Relevanssi is in privacy mode and not allowed to contact Relevanssiservices.com.', 'relevanssi' ) );
define( 'RELEVANSSI_ERROR_03', 'R_ERR03: ' . __( 'Attachment MIME type blocked.', 'relevanssi' ) );
define( 'RELEVANSSI_ERROR_04', 'R_ERR04: ' . __( 'Attachment file size is too large.', 'relevanssi' ) );
define( 'RELEVANSSI_ERROR_05', 'R_ERR05: ' . __( 'Attachment reading in process, please try again later.', 'relevanssi' ) );
define( 'RELEVANSSI_ERROR_06', 'R_ERR06: ' . __( 'Server did not respond.', 'relevanssi' ) );

/**
 * Reads the attachment content when an attachment is saved.
 *
 * Uses relevanssi_index_pdf() to read in the attachment content whenever an
 * attachment is saved. Works on the 'add_attachment' filter hook.
 *
 * @param int $post_id The post ID for the attachment.
 *
 * @since 2.0.0
 */
function relevanssi_read_attachment( $post_id ) {
	$post_status = get_post_status( $post_id );
	if ( 'auto-draft' === $post_status ) {
		return;
	}

	if ( 'on' !== get_option( 'relevanssi_read_new_files' ) ) {
		return;
	}

	$mime_type = get_post_mime_type( $post_id );
	$read_this = relevanssi_mime_type_ok( $mime_type );
	if ( $read_this ) {
		relevanssi_launch_ajax_action(
			'relevanssi_index_pdf',
			array( 'post_id' => $post_id )
		);
		// Remove the usual relevanssi_publish action because
		// relevanssi_index_pdf() already indexes the post.
		remove_action( 'add_attachment', 'relevanssi_publish', 12 );
	}
}

/**
 * Checks the MIME type of the attachment to determine if it's allowed or not.
 *
 * By default this function blocks all images, all video, and zip files.
 *
 * @param string $mime_type The attachment MIME type.
 *
 * @return boolean True, if ok to read the attachment, false if not.
 */
function relevanssi_mime_type_ok( $mime_type ) {
	$read_this  = true;
	$mime_parts = explode( '/', $mime_type );
	if ( in_array( $mime_parts[0], array( 'image', 'video' ), true ) ) {
		$read_this = false;
	}
	if ( isset( $mime_parts[1] ) && in_array( $mime_parts[1], array( 'zip', 'octet-stream', 'x-zip-compressed', 'x-zip' ), true ) ) {
		$read_this = false;
	}
	/**
	 * Allows the filtering of attachment reading based on MIME type.
	 *
	 * @param boolean $read_this True, if ok to read the attachment, false if not.
	 * @param string  $mime_type The attachment MIME type.
	 *
	 * @return boolean True, if ok to read the attachment, false if not.
	 */
	$read_this = apply_filters( 'relevanssi_accept_mime_type', $read_this, $mime_type );
	return $read_this;
}

/**
 * Includes the PDF content custom field in the list of custom fields.
 *
 * This function works on 'relevanssi_index_custom_fields' filter and makes sure the
 * '_relevanssi_pdf_content' custom field is included.
 *
 * @since 2.0.0
 *
 * @param array $custom_fields The custom fields array.
 *
 * @return array $custom_fields The custom fields array.
 */
function relevanssi_add_pdf_customfield( $custom_fields ) {
	if ( ! is_array( $custom_fields ) ) {
		$custom_fields = array();
	}
	if ( ! in_array( '_relevanssi_pdf_content', $custom_fields, true ) ) {
		$custom_fields[] = '_relevanssi_pdf_content';
	}
	return $custom_fields;
}

/**
 * Reads in all _relevanssi_pdf_content for found posts to avoid database calls.
 *
 * @global array An array of the PDF content.
 *
 * @param array    $hits  An array of posts found.
 * @param WP_Query $query The WP_Query object.
 *
 * @return array The posts found, untouched.
 */
function relevanssi_prime_pdf_content( $hits, $query ) {
	global $relevanssi_pdf_content;

	if ( ! isset( $query->query_vars['fields'] ) || empty( $query->query_vars['fields'] ) ) {
		$relevanssi_pdf_content = relevanssi_get_post_meta_for_all_posts(
			wp_list_pluck( $hits, 'ID' ),
			'_relevanssi_pdf_content'
		);
	}

	return $hits;
}


/**
 * Includes the PDF content custom field for excerpt-building.
 *
 * This function works on 'relevanssi_pre_excerpt_content' filter and makes sure
 * the '_relevanssi_pdf_content' custom field content is included when excerpts
 * are built.
 *
 * @since 2.0.0
 *
 * @see relevanssi_prime_pdf_content
 *
 * @param string $content The post content.
 * @param object $post    The post object.
 *
 * @return string $content The post content.
 */
function relevanssi_add_pdf_content_to_excerpt( $content, $post ) {
	global $relevanssi_pdf_content;
	$pdf_content = $relevanssi_pdf_content[ $post->ID ] ?? '';
	$content    .= ' ' . $pdf_content;
	return $content;
}

/**
 * Adds the PDF control metabox.
 *
 * Adds the PDF control metaboxes on post edit pages for posts in the
 * 'attachment' post type, with a MIME type that is not 'image/*' or
 * 'video/*'.
 *
 * @since 2.0.0
 *
 * @param object $post    The post object.
 */
function relevanssi_add_pdf_metaboxes( $post ) {
	// Do not display on image pages.
	$mime_parts = explode( '/', $post->post_mime_type );
	if ( in_array( $mime_parts[0], array( 'image', 'video' ), true ) ) {
		return;
	}

	add_meta_box(
		'relevanssi_pdf_box',
		__( 'Relevanssi attachment controls', 'relevanssi' ),
		'relevanssi_attachment_metabox',
		$post->post_type
	);
}

/**
 * Prints out the attachment control metabox.
 *
 * Prints out the attachment control metabox used for reading attachments and
 * examining the read attachment content.
 *
 * @global object $post The global post object.
 * @global array  $relevanssi_variables The Relevanssi global variables array,
 * used to get the file name for nonce.
 *
 * @since 2.0.0
 */
function relevanssi_attachment_metabox() {
	global $post, $relevanssi_variables;
	wp_nonce_field( plugin_basename( $relevanssi_variables['file'] ), 'relevanssi_pdfcontent' );

	$pdf_modified = get_post_meta( $post->ID, '_relevanssi_pdf_modified', true );

	/**
	 * Filters the attachment URL.
	 *
	 * If you want to make Relevanssi index attached file content from
	 * files that are stored outside the WP attachment system, use this
	 * filter to provide the URL of the file.
	 *
	 * @param string The URL of the attached file.
	 * @param int    The post ID of the attachment post.
	 */
	$url         = apply_filters(
		'relevanssi_get_attachment_url',
		wp_get_attachment_url( $post->ID ),
		$post->ID
	);
	$id          = $post->ID;
	$button_text = $pdf_modified ? __( 'Reread the attachment content', 'relevanssi' ) : __( 'Read the attachment content', 'relevanssi' );
	$api_key     = get_network_option( null, 'relevanssi_api_key' );
	$action      = 'sendUrl';
	$explanation = __( 'Indexer will fetch the file from your server.', 'relevanssi' );
	if ( 'on' === get_option( 'relevanssi_send_pdf_files' ) ) {
		$action      = 'sendPdf';
		$explanation = __( 'The file will be uploaded to the indexer.', 'relevanssi' );
	}

	if ( ! $api_key ) {
		// get_network_option() falls back to get_option(), but if this is a single
		// install on a multisite, it won't work correctly.
		$api_key = get_option( 'relevanssi_api_key' );
	}

	if ( ! $api_key ) {
		printf( '<p>%s</p>', esc_html__( 'No API key set. API key is required for attachment indexing.', 'relevanssi' ) );
	} else {
		printf(
			'<p><input type="button" id="%s" value="%s" class="button-primary button-large" data-api_key="%s" data-post_id="%d" data-url="%s" title="%s"/></p>',
			esc_attr( $action ),
			esc_attr( $button_text ),
			esc_attr( $api_key ),
			intval( $id ),
			esc_attr( $url ),
			esc_attr( $explanation )
		);

		if ( $pdf_modified ) {
			esc_html_e( "The attachment content has been modified and won't be reread from the file when doing a general rereading. If you want to reread the attachment contents from the file, you can force rereading here.", 'relevanssi' );
		}

		$pdf_content = get_post_meta( $post->ID, '_relevanssi_pdf_content', true );
		if ( $pdf_content ) {
			$pdf_content_title = __( 'Attachment content', 'relevanssi' );
			printf(
				'<h3><label for="relevanssi_pdf_content">%s</label></h3> <p><textarea id="relevanssi_pdf_content" name="relevanssi_pdf_content" cols="80" rows="4">%s</textarea></p>',
				esc_html( $pdf_content_title ),
				esc_html( $pdf_content )
			);
		}

		$pdf_error = get_post_meta( $post->ID, '_relevanssi_pdf_error', true );
		if ( false !== strpos( $pdf_error, 'R_ERR05' ) ) {
			printf(
				'<h3>%s</h3>',
				esc_html__( 'Relevanssi is currently in process of reading the file contents, please return here later.', 'relevanssi' )
			);
		} elseif ( $pdf_error ) {
			$pdf_error_title = __( 'Attachment error message', 'relevanssi' );
			printf(
				'<h3><label for="relevanssi_pdf_error">%s</label></h3> <p><textarea id="relevanssi_pdf_error" cols="80" rows="4" readonly>%s</textarea></p>',
				esc_html( $pdf_error_title ),
				esc_html( $pdf_error )
			);
		}

		if ( empty( $pdf_content ) && empty( $pdf_error ) ) {
			printf(
				'<p>%s</p>',
				esc_html__( 'No attachment content found for this post at the moment.', 'relevanssi' )
			);
		}
	}
}

/**
 * Reads in attachment content from a attachment file.
 *
 * Reads in the attachment content, either by sending an URL or the file itself to
 * the Relevanssi attachment reading service.
 *
 * @param int     $post_id   The attachment post ID.
 * @param boolean $ajax      Is this in AJAX context? Default false.
 * @param string  $send_file Should the file be sent ('on'), or just the URL ('off')?
 * Default null.
 *
 * @return array An array with two items: boolean 'success' and 'error'
 * containing the possible error message.
 *
 * @since 2.0.0
 */
function relevanssi_index_pdf( $post_id, $ajax = false, $send_file = null ) {
	$hide_post = get_post_meta( $post_id, '_relevanssi_hide_post', true );
	/**
	 * Filters whether the attachment should be read or not.
	 *
	 * @param boolean $hide_post True if the attachment shouldn't be read,
	 * false if it should.
	 * @param int     $post_id   The attachment post ID.
	 */
	$hide_post = apply_filters( 'relevanssi_do_not_read', $hide_post, $post_id );
	if ( $hide_post ) {
		delete_post_meta( $post_id, '_relevanssi_pdf_content' );
		update_post_meta( $post_id, '_relevanssi_pdf_error', RELEVANSSI_ERROR_01 );

		$result = array(
			'success' => false,
			'error'   => RELEVANSSI_ERROR_01,
		);

		return $result;
	}

	$mime_type = get_post_mime_type( $post_id );
	if ( ! relevanssi_mime_type_ok( $mime_type ) ) {
		delete_post_meta( $post_id, '_relevanssi_pdf_content' );
		update_post_meta( $post_id, '_relevanssi_pdf_error', RELEVANSSI_ERROR_03 );

		$result = array(
			'success' => false,
			'error'   => RELEVANSSI_ERROR_03,
		);

		return $result;
	}

	if ( is_null( $send_file ) ) {
		$send_file = get_option( 'relevanssi_send_pdf_files' );
	} elseif ( $send_file ) {
		$send_file = 'on';
	}

	/**
	 * Filters whether the PDF files are uploaded for indexing or not.
	 *
	 * If you have some files that need to be uploaded and some where it's
	 * better if the indexer reads them from a URL, you can use this filter
	 * hook to adjust the setting on a per-file basis.
	 *
	 * @param string $sendfile If 'on', upload the file, otherwise the file
	 * will be read from the URL.
	 * @param int    $post_id  The post ID of the attachment post.
	 */
	$send_file = apply_filters( 'relevanssi_send_pdf_files', $send_file, $post_id );

	$api_key = get_network_option( null, 'relevanssi_api_key' );
	if ( ! $api_key ) {
		$api_key = get_option( 'relevanssi_api_key' );
	}
	$server_url = relevanssi_get_server_url();

	if ( 'on' === get_option( 'relevanssi_do_not_call_home' ) ) {
		if ( in_array( $server_url, array( RELEVANSSI_EU_SERVICES_URL, RELEVANSSI_US_SERVICES_URL ), true ) ) {
			delete_post_meta( $post_id, '_relevanssi_pdf_content' );
			update_post_meta( $post_id, '_relevanssi_pdf_error', RELEVANSSI_ERROR_02 );

			$result = array(
				'success' => false,
				'error'   => RELEVANSSI_ERROR_02,
			);

			return $result;
		}
	}
	if ( 'on' === $send_file ) {
		/**
		 * Filters the attachment file name.
		 *
		 * If you want to make Relevanssi index attached file content from
		 * files that are stored outside the WP attachment system, use this
		 * filter to provide the name of the file.
		 *
		 * @param string The filename of the attached file.
		 * @param int    The post ID of the attachment post.
		 */
		$file_name = apply_filters(
			'relevanssi_get_attached_file',
			get_attached_file( $post_id ),
			$post_id
		);

		$file = fopen( $file_name, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		if ( false === $file ) {
			$response = new WP_Error( 'fopen', 'Could not open the file for reading.' );
		} else {
			$file_size = filesize( $file_name );
			$file_data = fread( $file, $file_size ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			$args      = array(
				'headers' => array(
					'accept'       => 'application/json',   // The API returns JSON.
					'content-type' => 'application/binary', // Set content type to binary.
				),
				'body'    => $file_data,
				/**
				 * Changes the default reading timeout.
				 *
				 * By default, the timeout period is 45 seconds. If that's not
				 * enough, you can adjust the timeout period with this filter.
				 *
				 * @param int $timeout The timeout in seconds, default 45.
				 */
				'timeout' => apply_filters( 'relevanssi_pdf_read_timeout', 45 ),
			);
			$response = wp_safe_remote_post(
				$server_url . 'index.php?key=' . $api_key . '&upload=true',
				$args
			);
		}
	} else {
		/**
		 * Filters the attachment URL.
		 *
		 * If you want to make Relevanssi index attached file content from
		 * files that are stored outside the WP attachment system, use this
		 * filter to provide the URL of the file.
		 *
		 * @param string The URL of the attached file.
		 * @param int    The post ID of the attachment post.
		 */
		$url = apply_filters(
			'relevanssi_get_attachment_url',
			wp_get_attachment_url( $post_id ),
			$post_id
		);

		$args = array(
			'body'    => array(
				'key' => $api_key,
				'url' => $url,
			),
			'method'  => 'POST',
			/**
			 * Changes the default reading timeout.
			 *
			 * By default, the timeout period is 45 seconds. If that's not
			 * enough, you can adjust the timeout period with this filter.
			 *
			 * @param int $timeout The timeout in seconds, default 45.
			 */
			'timeout' => apply_filters( 'relevanssi_pdf_read_timeout', 45 ),
		);

		$response = wp_safe_remote_post( $server_url, $args );
	}

	$result = relevanssi_process_server_response( $response, $post_id );

	if ( $ajax ) {
		echo wp_json_encode( $result );
		wp_die();
	}

	// The PDF count is cached because the query is slow; delete the cache now, as
	// the value just changed.
	wp_cache_delete( 'relevanssi_pdf_count' );
	wp_cache_delete( 'relevanssi_pdf_error_count' );

	return $result;
}

/**
 * Processes the attachment reading server response.
 *
 * Takes in the response from the attachment reading server and stores the attachment
 * content or the error message to the appropriate custom fields.
 *
 * @param array|object $response The server response.
 * @param int          $post_id  The attachment post ID.
 *
 * @since 2.0.0
 */
function relevanssi_process_server_response( $response, $post_id ) {
	$success        = null;
	$response_error = '';
	if ( is_wp_error( $response ) ) {
		$error_message   = $response->get_error_message();
		$response_error .= $error_message . '\n';

		delete_post_meta( $post_id, '_relevanssi_pdf_content' );
		delete_post_meta( $post_id, '_relevanssi_pdf_modified' );
		update_post_meta( $post_id, '_relevanssi_pdf_error', $error_message );
		$success = false;
	} elseif ( isset( $response['body'] ) ) {
		$content = $response['body'];
		$content = json_decode( $content );

		$content_error = '';

		if ( 413 === $response['response']['code'] ) {
			$content_error = RELEVANSSI_ERROR_04;
		}

		if ( 504 === $response['response']['code'] ) {
			$content_error = RELEVANSSI_ERROR_06;
		}

		if ( isset( $content->error ) ) {
			$content_error = $content->error;
			$content       = $content->error;
		}

		if ( $content && stristr( $content, 'java.lang.OutOfMemoryError' ) ) {
			$content_error = RELEVANSSI_ERROR_04;
		}

		if ( $content && stristr( $content, 'Tika server returned error code' ) ) {
			$content_error = RELEVANSSI_ERROR_06;
		}

		if ( empty( $content ) ) {
			$content_error = RELEVANSSI_ERROR_06;
		}

		if ( ! empty( $content_error ) ) {
			delete_post_meta( $post_id, '_relevanssi_pdf_content' );
			delete_post_meta( $post_id, '_relevanssi_pdf_modified' );
			update_post_meta( $post_id, '_relevanssi_pdf_error', $content_error );

			$response_error .= $content_error;
			$success         = false;
		} else {
			delete_post_meta( $post_id, '_relevanssi_pdf_error' );
			delete_post_meta( $post_id, '_relevanssi_pdf_modified' );
			/**
			 * Filters the read file content before it is saved.
			 *
			 * @param string $content The file content as a string.
			 * @param int    $post_id The post ID of the attachment post.
			 */
			$success = update_post_meta( $post_id, '_relevanssi_pdf_content', apply_filters( 'relevanssi_file_content', $content, $post_id ) );
			relevanssi_index_doc( $post_id, false, relevanssi_get_custom_fields(), true );
			if ( 'on' === get_option( 'relevanssi_index_pdf_parent' ) ) {
				if ( function_exists( 'get_post_parent' ) ) {
					$parent = get_post_parent( $post_id );
				} else {
					// For WP < 5.7 compatibility, remove eventually.
					$_post  = get_post( $post_id );
					$parent = ! empty( $_post->post_parent ) ? get_post( $_post->post_parent ) : null;
				}
				if ( $parent ) {
					relevanssi_index_doc( $parent->ID, true, relevanssi_get_custom_fields(), true );
				}
			}

			if ( ! $success ) {
				$response_error = __( 'Could not save the file content to the custom field.', 'relevanssi' );
			}
		}
	}

	$response = array(
		'success' => $success,
		'error'   => $response_error,
	);

	return $response;
}

/**
 * Gets the posts with attachments.
 *
 * Finds the posts with non-image attachments that don't have read content or
 * errors. The posts that have timeout or connection errors (cURL error 7 and
 * 28, R_ERR06) and those that haven't been read for privacy mode issues
 * (R_ERR02) will be included for indexing, but the posts with other errors
 * (R_ERR01: post excluded by user, R_ERR03: blocked MIME type and R_ERR04: file
 * too large) will not be indexed.
 *
 * @since 2.0.0
 *
 * @param int $limit The number of posts to fetch, default 1.
 *
 * @return array The posts with attachments.
 */
function relevanssi_get_posts_with_attachments( $limit = 1 ) {
	global $wpdb;

	$meta_query_args = array(
		'relation' => 'AND',
		array(
			'key'     => '_relevanssi_pdf_content',
			'compare' => 'NOT EXISTS',
		),
		array(
			'relation' => 'OR',
			array(
				'key'     => '_relevanssi_pdf_error',
				'compare' => 'NOT EXISTS',
			),
			array(
				'key'     => '_relevanssi_pdf_error',
				'compare' => 'LIKE',
				'value'   => 'R_ERR02:',
			),
			array(
				'key'     => '_relevanssi_pdf_error',
				'compare' => 'LIKE',
				'value'   => 'cURL error 7:',
			),
			array(
				'key'     => '_relevanssi_pdf_error',
				'compare' => 'LIKE',
				'value'   => 'cURL error 28:',
			),
			array(
				'key'     => '_relevanssi_pdf_error',
				'compare' => 'LIKE',
				'value'   => 'is not valid.',
			),
		),
	);
	$meta_query      = new WP_Meta_Query( $meta_query_args );
	$meta_query_sql  = $meta_query->get_sql( 'post', $wpdb->posts, 'ID' );
	$meta_join       = '';
	$meta_where      = '';
	if ( $meta_query_sql ) {
		$meta_join  = $meta_query_sql['join'];
		$meta_where = $meta_query_sql['where'];
	}

	// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared
	if ( $limit > 0 ) {
		$query = $wpdb->prepare(
			/**
			 * Filters the SQL query that fetches posts with attachments.
			 *
			 * If you want to make Relevanssi index attachments that are not in
			 * the WP Media Library, you need to adjust this filter to change
			 * the SQL query so that it fetches the correct posts.
			 *
			 * @param string The SQL query
			 * @param int    $limit      The number of posts to fetch.
			 * @param string $meta_join  The SQL query join clause.
			 * @param string $meta_where The SQL query where clause.
			 */
			apply_filters(
				'relevanssi_get_attachment_posts_query',
				"SELECT DISTINCT(ID) FROM $wpdb->posts $meta_join WHERE post_type = 'attachment' AND post_status = 'inherit' AND post_mime_type LIKE %s $meta_where LIMIT %d",
				$limit,
				$meta_join,
				$meta_where
			),
			'application/%',
			$limit
		);
	} else {
		$query = $wpdb->prepare(
			/** Filter documented in /premium/pdf-upload.php. */
			apply_filters(
				'relevanssi_get_attachment_posts_query',
				"SELECT DISTINCT(ID) FROM $wpdb->posts $meta_join WHERE post_type = 'attachment' AND post_status = 'inherit' AND post_mime_type LIKE %s $meta_where",
				0,
				$meta_join,
				$meta_where
			),
			'application/%'
		);
	}
	/**
	 * Filters the final SQL query that fetches posts with attachments.
	 *
	 * @param string $query      The SQL query.
	 * @param int    $limit      The number of posts to fetch.
	 * @param string $meta_join  The SQL query join clause.
	 * @param string $meta_where The SQL query where clause.
	 */
	$query = apply_filters( 'relevanssi_get_attachment_posts_query_final', $query, $limit, $meta_join, $meta_where );
	// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared
	$posts = $wpdb->get_col( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared

	return $posts;
}

/**
 * Prints out the Javascript for PDF content reading.
 *
 * @since 2.0.0
 */
function relevanssi_pdf_action_javascript() {
	$list_pdfs_nonce   = wp_create_nonce( 'relevanssi-list-pdfs' );
	$wipe_pdfs_nonce   = wp_create_nonce( 'relevanssi-wipe-pdfs' );
	$wipe_errors_nonce = wp_create_nonce( 'relevanssi-wipe-errors' );
	$index_pdfs_nonce  = wp_create_nonce( 'relevanssi-index-pdfs' );

	$server_error = __( 'Server error', 'relevanssi' );

	?>
	<script type="text/javascript" >
	var time = 0;
	var intervalID = 0;

	function relevanssiUpdateClock( ) {
		time++;
		var time_formatted = rlv_format_time(Math.round(time));
		document.getElementById("relevanssi_elapsed").innerHTML = time_formatted;
	}

	jQuery(document).ready(function($ ) {
		$("#index").on("click", function( ) {
			$("#relevanssi-progress").show();
			$("#relevanssi_results").show();
			$("#relevanssi-timer").show();
			$("#stateofthepdfindex").html(relevanssi.reload_state);

			intervalID = window.setInterval(relevanssiUpdateClock, 1000);

			var data = {
				'action': 'relevanssi_list_pdfs',
				'security': '<?php echo esc_html( $list_pdfs_nonce ); ?>'
			};

			console.log("Getting a list of pdfs.");

			var relevanssi_results = document.getElementById("relevanssi_results");
			relevanssi_results.value += relevanssi.counting_attachments;

			var pdf_ids;

			jQuery.post(ajaxurl, data, function(response ) {
				pdf_ids = JSON.parse(response);
				console.log(pdf_ids);
				console.log("Fetching response: " + response);
				console.log("Heading into step 0");
				console.log(pdf_ids.length);
				relevanssi_results.value += pdf_ids.length + ' ' + relevanssi.attachments_found + "\n";
				relevanssi_results.value += relevanssi.indexing_attachments + "\n";
				process_step(0, pdf_ids.length, 0);
			})
			.fail(function(response ) {
				console.log("Error: " + response);
				relevanssi_results.value += relevanssi.error + "\n";
				relevanssi_results.value += response.responseText;
			});
		});
		$("#reset").on("click", function($ ) {
			if (confirm( relevanssi.pdf_reset_confirm ) ) {
				var data = {
					'action': 'relevanssi_wipe_pdfs',
					'security': '<?php echo esc_html( $wipe_pdfs_nonce ); ?>'
				}
				jQuery.post(ajaxurl, data, function(response ) {
					var delete_response = JSON.parse(response);
					if ( ! delete_response.deleted_content && ! delete_response.deleted_errors ) {
						alert( relevanssi.pdf_reset_problems );
					} else {
						alert( relevanssi.pdf_reset_done );
					}
					jQuery("#stateofthepdfindex").html(relevanssi.reload_state);
				});
			}
			else {
				return false;
			}
		});
		$("#clearservererrors").on("click", function($ ) {
			var data = {
				'action': 'relevanssi_wipe_server_errors',
				'security': '<?php echo esc_html( $wipe_errors_nonce ); ?>'
			}
			jQuery.post(ajaxurl, data, function(response ) {
				var delete_response = JSON.parse(response);
				if ( ! delete_response.deleted_rows ) {
					alert( relevanssi.error_reset_problems );
				} else {
					alert( relevanssi.error_reset_done );
				}
				jQuery("#stateofthepdfindex").html(relevanssi.reload_state);
			});
		});
	});

	function process_step(completed, total, total_seconds ) {
		var t0 = performance.now();
		jQuery.ajax({
			type: 'POST',
			url: ajaxurl,
			data: {
				action: 'relevanssi_index_pdfs',
				security: '<?php echo esc_html( $index_pdfs_nonce ); ?>',
				completed: completed,
				total: total,
			},
			dataType: 'json',
			success: function(response ) {
				console.log(response);
				var relevanssi_results = document.getElementById("relevanssi_results");
				if (response.completed == 'done' ) {
					relevanssi_results.value += response.feedback;
					jQuery( '.rpi-progress div').animate({
						width: response.percentage + '%',
						}, 50, function( ) {
						// Animation complete.
					});

					clearInterval(intervalID);
				}
				else {
					var t1 = performance.now();
					var time_seconds = (t1 - t0) / 1000;
					time_seconds = Math.round(time_seconds * 100) / 100;
					total_seconds += time_seconds;

					var estimated_time = rlv_format_approximate_time(Math.round(total_seconds / response.percentage * 100 - total_seconds));
					document.getElementById("relevanssi_estimated").innerHTML = estimated_time;

					relevanssi_results.value += response.feedback;
					relevanssi_results.scrollTop = relevanssi_results.scrollHeight;
					jQuery( '.rpi-progress div').animate({
						width: response.percentage + '%',
						}, 50, function( ) {
						// Animation complete.
					});
					console.log("Heading into step " + response.completed);
					process_step(parseInt(response.completed), total, total_seconds);
				}
			},
			error: function(response ) {
				console.log("Error: ", response.status);
				var relevanssi_results = document.getElementById("relevanssi_results");
				relevanssi_results.value += "<?php echo esc_html( $server_error ); ?>: " + response.status + " " + response.statusText + "\n";
				relevanssi_results.scrollTop = relevanssi_results.scrollHeight;
			}
		})
	}

	</script>
	<?php
}

/**
 * Saves attachment edit page metadata.
 *
 * Saves the attachment content metadata if it has been edited and sets the
 * _relevanssi_pdf_modified flag if necessary.
 *
 * @global $relevanssi_variables The global Relevanssi variables array.
 *
 * @param int $post_id The attachment post ID.
 */
function relevanssi_save_pdf_postdata( $post_id ) {
	global $relevanssi_variables;
	// Verify if this is an auto save routine. If it is, our form has not been
	// submitted, so we dont want to do anything.
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	// Verify the nonce.
	if ( isset( $_POST['relevanssi_pdfcontent'] ) ) { // WPCS: input var okey.
		if ( ! wp_verify_nonce(
			sanitize_key( $_POST['relevanssi_pdfcontent'] ),
			plugin_basename( $relevanssi_variables['file'] )
		)
		) { // WPCS: input var okey.
			return;
		}
	}

	$post = $_POST; // WPCS: input var okey.

	// If relevanssi_pdf_content is not set, it's a quick edit.
	if ( ! isset( $post['relevanssi_pdf_content'] ) ) {
		return;
	}

	// Check permissions.
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$original_post_meta = get_post_meta( $post_id, '_relevanssi_pdf_content', true );
	$new_post_meta      = stripslashes( $post['relevanssi_pdf_content'] );
	if ( $new_post_meta !== $original_post_meta ) {
		if ( $new_post_meta ) {
			update_post_meta(
				$post_id,
				'_relevanssi_pdf_content',
				$new_post_meta
			);
			update_post_meta(
				$post_id,
				'_relevanssi_pdf_modified',
				true
			);
		} else {
			delete_post_meta(
				$post_id,
				'_relevanssi_pdf_content'
			);
			delete_post_meta(
				$post_id,
				'_relevanssi_pdf_modified'
			);
		}
	}
}

/**
 * If attachments are indexed for the parent post, reindex the parent post.
 *
 * When attachments are attached or detached, this triggers a reindexing of the
 * parent post.
 *
 * @param string $action        The attachment action.
 * @param int    $attachment_id The attachment post ID.
 * @param int    $parent_id     The parent post ID.
 */
function relevanssi_media_attach_action( $action, $attachment_id, $parent_id ) {
	$index_pdf_parent = get_option( 'relevanssi_index_pdf_parent' );
	if ( 'on' === $index_pdf_parent ) {
		relevanssi_publish( $parent_id );
	}
}
