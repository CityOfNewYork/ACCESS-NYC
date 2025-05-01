<?php
/**
 * /premium/post-metabox.php
 *
 * Relevanssi Premium post metaboxes controls.
 *
 * @package Relevanssi_Premium
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

/**
 * Adds the Relevanssi metaboxes for post edit pages.
 *
 * Adds the Relevanssi Post Controls meta box on the post edit pages on post
 * types that are indexed by Relevanssi.
 */
function relevanssi_add_metaboxes() {
	global $post, $relevanssi_variables;
	if ( null === $post ) {
		return;
	}

	if ( ! current_user_can(
		/**
		 * Filters the capability required to access the Relevanssi sidebar.
		 *
		 * @param string The capability required, default 'edit_others_posts'.
		 */
		apply_filters( 'relevanssi_sidebar_capability', $relevanssi_variables['sidebar_capability'] )
	)
	) {
		return;
	}

	$indexed_post_types = get_option( 'relevanssi_index_post_types', array() );
	if ( ! in_array( $post->post_type, $indexed_post_types, true ) ) {
		return;
	}
	add_meta_box(
		'relevanssi_hidebox',
		__( 'Relevanssi', 'relevanssi' ),
		'relevanssi_post_metabox',
		array( $post->post_type, 'edit-category' ),
		'side',
		'default',
		array( '__back_compat_meta_box' => true )
	);
	add_thickbox(); // Make sure Thickbox is enabled.
}

/**
 * Prints out the Relevanssi Post Controls meta box.
 *
 * Prints out the Relevanssi Post Controls meta box that is displayed on the post edit pages.
 *
 * @global array  $relevanssi_variables The Relevanssi global variables array, used to get the file name for nonce.
 * @global object $post                 The global post object.
 */
function relevanssi_post_metabox() {
	global $relevanssi_variables, $post;
	wp_nonce_field( plugin_basename( $relevanssi_variables['file'] ), 'relevanssi_hidepost' );

	$hide_post    = checked( 'on', get_post_meta( $post->ID, '_relevanssi_hide_post', true ), false );
	$hide_content = checked( 'on', get_post_meta( $post->ID, '_relevanssi_hide_content', true ), false );
	$pin_for_all  = checked( 'on', get_post_meta( $post->ID, '_relevanssi_pin_for_all', true ), false );

	$pins          = get_post_meta( $post->ID, '_relevanssi_pin', false );
	$pin_weights   = get_post_meta( $post->ID, '_relevanssi_pin_weights', true );
	$weighted_pins = array();
	foreach ( $pins as $pin ) {
		if ( isset( $pin_weights[ $pin ] ) ) {
			$pin .= ' (' . $pin_weights[ $pin ] . ')';
		}
		$weighted_pins[] = $pin;
	}
	$pin = implode( ', ', $weighted_pins );

	$unpins = get_post_meta( $post->ID, '_relevanssi_unpin', false );
	$unpin  = implode( ', ', $unpins );

	// The actual fields for data entry.
	?>
	<input type="hidden" id="relevanssi_metabox" name="relevanssi_metabox" value="true" />

	<div class="section relevanssi-sees-post">
		<div class="contents">
			<p><a name="<?php esc_html_e( 'How Relevanssi sees this post', 'relevanssi' ); ?>" href="#TB_inline?width=800&height=600&inlineId=relevanssi_sees_container" class="thickbox button"><?php esc_html_e( 'How Relevanssi sees this post', 'relevanssi' ); ?></a></p>
		</div>
	</div>
	<div class="section relevanssi-pin-post">
		<h3><?php esc_html_e( 'Pin this post', 'relevanssi' ); ?></h3>
		<div class="contents">
			<p><?php esc_html_e( 'A comma-separated list of single word keywords or multi-word phrases. If any of these keywords are present in the search query, this post will be moved on top of the search results.', 'relevanssi' ); ?></p>
			<label for="relevanssi_pin" class="screen-reader-text"><?php esc_html_e( 'Pinned keywords for this post', 'relevanssi' ); ?></label>
			<textarea id="relevanssi_pin" name="relevanssi_pin" cols="30" rows="2" style="max-width: 100%"><?php echo esc_html( $pin ); ?></textarea/>

			<p><?php esc_html_e( "You can add weights to pinned keywords like this: 'keyword (100)'. The post with the highest weight will be sorted first if there are multiple posts pinned to the same keyword.", 'relevanssi' ); ?></p>

			<?php
			if ( 0 === intval( get_option( 'relevanssi_content_boost' ) ) ) {
				?>
				<p><?php esc_html_e( "NOTE: You have set the post content weight to 0. This means that keywords that don't appear elsewhere in the post won't work, because they are indexed as part of the post content. If you set the post content weight to any positive value, the pinned keywords will work again.", 'relevanssi' ); ?></p>
				<?php
			}
			?>

			<p class="checkbox"><input type="checkbox" id="relevanssi_pin_for_all" name="relevanssi_pin_for_all" <?php echo esc_attr( $pin_for_all ); ?> />
			<label for="relevanssi_pin_for_all">
				<?php esc_html_e( 'Pin this post for all searches it appears in.', 'relevanssi' ); ?>
			</label></p>
		</div>
	</div>
	<div class="section relevanssi-exclude-post">
		<h3><?php esc_html_e( 'Exclude this post', 'relevanssi' ); ?></h3>
		<div class="contents">
			<p><?php esc_html_e( 'A comma-separated list of single word keywords or multi-word phrases. If any of these keywords are present in the search query, this post will be removed from the search results.', 'relevanssi' ); ?></p>
			<label for="relevanssi_unpin" class="screen-reader-text"><?php esc_html_e( 'Excluded keywords for this post', 'relevanssi' ); ?></label>
			<textarea id="relevanssi_unpin" name="relevanssi_unpin" cols="30" rows="2" style="max-width: 100%"><?php echo esc_html( $unpin ); ?></textarea>

			<p class="checkbox"><input type="checkbox" id="relevanssi_hide_post" name="relevanssi_hide_post" <?php echo esc_attr( $hide_post ); ?> />
			<label for="relevanssi_hide_post">
				<?php esc_html_e( 'Exclude this post or page from the index.', 'relevanssi' ); ?>
			</label></p>

			<p class="checkbox"><input type="checkbox" id="relevanssi_hide_content" name="relevanssi_hide_content" <?php echo esc_attr( $hide_content ); ?> />
			<label for="relevanssi_hide_content">
				<?php esc_html_e( 'Ignore post content in the indexing.', 'relevanssi' ); ?>
			</label></p>
		</div>
	</div>
	<?php
	$related_posts_settings = get_option( 'relevanssi_related_settings', relevanssi_related_default_settings() );
	if ( isset( $related_posts_settings['enabled'] ) && 'on' === $related_posts_settings['enabled'] ) {
		relevanssi_related_posts_metabox( $post->ID );
	}

	$display = false;
	$element = relevanssi_generate_how_relevanssi_sees( $post->ID, $display );
	echo $element;  // phpcs:ignore WordPress.Security.EscapeOutput
}

/**
 * Saves the Relevanssi Gutenberg sidebar meta data.
 *
 * When a post is saved in Gutenberg, this function saves the Relevanssi
 * sidebar meta data.
 *
 * @param object $post The post object.
 */
function relevanssi_save_gutenberg_postdata( $post ) {
	// Verify if this is an auto save routine.
	// If it is, our form has not been submitted, so we dont want to do anything.
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	// Check nonce here?

	$keywords = get_post_meta( $post->ID, '_relevanssi_pin_keywords', true );
	relevanssi_update_pin_fields( $post->ID, $keywords );

	$keywords = get_post_meta( $post->ID, '_relevanssi_unpin_keywords', true );
	relevanssi_update_unpin_fields( $post->ID, $keywords );
}

/**
 * Updates the _relevanssi_pin custom fields based on a list of keywords.
 *
 * @param int    $post_id  The post ID.
 * @param string $keywords The keywords.
 */
function relevanssi_update_pin_fields( $post_id, $keywords ) {
	$pin_weights = array();
	if ( $keywords ) {
		delete_post_meta( $post_id, '_relevanssi_pin' );
		$pins = explode( ',', sanitize_text_field( wp_unslash( $keywords ) ) );
		foreach ( $pins as $pin ) {
			list( $pin, $weight ) = array_pad( explode( '(', $pin, 2 ), 2, '1' );

			$weight = str_replace( ')', '', $weight );
			$weight = intval( $weight );
			if ( $weight < 1 ) {
				$weight = 1;
			}
			$pin = trim( $pin );

			if ( $weight > 1 ) {
				$pin_weights[ $pin ] = $weight;
			}

			if ( ! empty( $pin ) ) {
				add_post_meta( $post_id, '_relevanssi_pin', $pin );
			}
		}
	} else {
		delete_post_meta( $post_id, '_relevanssi_pin' );
	}
	if ( ! empty( $pin_weights ) ) {
		update_post_meta( $post_id, '_relevanssi_pin_weights', $pin_weights );
	} else {
		delete_post_meta( $post_id, '_relevanssi_pin_weights' );
	}
}

/**
 * Updates the _relevanssi_unpin custom fields based on a list of keywords.
 *
 * @param int    $post_id  The post ID.
 * @param string $keywords The keywords.
 */
function relevanssi_update_unpin_fields( $post_id, $keywords ) {
	if ( $keywords ) {
		delete_post_meta( $post_id, '_relevanssi_unpin' );
		$pins = explode( ',', sanitize_text_field( wp_unslash( $keywords ) ) );
		foreach ( $pins as $pin ) {
			$pin = trim( $pin );
			if ( ! empty( $pin ) ) {
				add_post_meta( $post_id, '_relevanssi_unpin', $pin );
			}
		}
	} else {
		delete_post_meta( $post_id, '_relevanssi_unpin' );
	}
}

/**
 * Saves Relevanssi metabox data.
 *
 * When a post is saved in the Classic Editor, this function saves the
 * Relevanssi Post Controls metabox data.
 *
 * @param int $post_id The post ID that is being saved.
 */
function relevanssi_save_postdata( $post_id ) {
	global $relevanssi_variables;
	// Verify if this is an auto save routine. If it is, our form has not been
	// submitted, so we dont want to do anything.
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	// Verify the nonce.
	if ( isset( $_POST['relevanssi_hidepost'] ) ) { // WPCS: input var okey.
		if ( ! wp_verify_nonce(
			sanitize_key( $_POST['relevanssi_hidepost'] ),
			plugin_basename( $relevanssi_variables['file'] )
		)
		) { // WPCS: input var okey.
			return;
		}
	}

	$post = $_POST; // WPCS: input var okey.

	// If relevanssi_metabox is not set, it's a quick edit.
	if ( ! isset( $post['relevanssi_metabox'] ) ) {
		return;
	}

	// Check permissions.
	if ( isset( $post['post_type'] ) ) {
		if ( 'page' === $post['post_type'] ) {
			if ( ! current_user_can( 'edit_page', $post_id ) ) {
				return;
			}
		} elseif ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
	}

	$hide = '';
	if ( isset( $post['relevanssi_hide_post'] ) && 'on' === $post['relevanssi_hide_post'] ) {
		$hide = 'on';
	}

	if ( 'on' === $hide ) {
		// Post is marked hidden, so remove it from the index.
		relevanssi_remove_doc( $post_id );
	}

	if ( 'on' === $hide ) {
		update_post_meta( $post_id, '_relevanssi_hide_post', $hide );
	} else {
		delete_post_meta( $post_id, '_relevanssi_hide_post' );
	}

	$hide_content = '';
	if ( isset( $post['relevanssi_hide_content'] ) && 'on' === $post['relevanssi_hide_content'] ) {
		$hide_content = 'on';
	}

	if ( 'on' === $hide_content ) {
		update_post_meta( $post_id, '_relevanssi_hide_content', $hide_content );
	} else {
		delete_post_meta( $post_id, '_relevanssi_hide_content' );
	}

	$pin_for_all = '';
	if ( isset( $post['relevanssi_pin_for_all'] ) && 'on' === $post['relevanssi_pin_for_all'] ) {
		$pin_for_all = 'on';
	}

	if ( 'on' === $pin_for_all ) {
		update_post_meta( $post_id, '_relevanssi_pin_for_all', $pin_for_all );
	} else {
		delete_post_meta( $post_id, '_relevanssi_pin_for_all' );
	}

	if ( isset( $post['relevanssi_pin'] ) ) {
		relevanssi_update_pin_fields( $post_id, $post['relevanssi_pin'] );
	} else {
		delete_post_meta( $post_id, '_relevanssi_pin' );
	}

	if ( isset( $post['relevanssi_unpin'] ) ) {
		delete_post_meta( $post_id, '_relevanssi_unpin' );
		$pins = explode( ',', sanitize_text_field( wp_unslash( $post['relevanssi_unpin'] ) ) );
		foreach ( $pins as $pin ) {
			$pin = trim( $pin );
			if ( ! empty( $pin ) ) {
				add_post_meta( $post_id, '_relevanssi_unpin', $pin );
			}
		}
	} else {
		delete_post_meta( $post_id, '_relevanssi_unpin' );
	}

	$no_append = '';
	if ( isset( $post['relevanssi_related_no_append'] ) && 'on' === $post['relevanssi_related_no_append'] ) {
		$no_append = 'on';
	}

	if ( 'on' === $no_append ) {
		update_post_meta( $post_id, '_relevanssi_related_no_append', $no_append );
	} else {
		delete_post_meta( $post_id, '_relevanssi_related_no_append' );
	}

	$not_related = '';
	if ( isset( $post['relevanssi_related_not_related'] ) && 'on' === $post['relevanssi_related_not_related'] ) {
		$not_related = 'on';
	}

	if ( 'on' === $not_related ) {
		update_post_meta( $post_id, '_relevanssi_related_not_related', $not_related );
	} else {
		delete_post_meta( $post_id, '_relevanssi_related_not_related' );
	}

	if ( isset( $post['relevanssi_related_keywords'] ) ) {
		delete_post_meta( $post_id, '_relevanssi_related_keywords' );
		$keywords = sanitize_text_field( $post['relevanssi_related_keywords'] );
		if ( $keywords ) {
			add_post_meta( $post_id, '_relevanssi_related_keywords', $keywords );
		}
	} else {
		delete_post_meta( $post_id, '_relevanssi_related_keywords' );
	}

	if ( isset( $post['relevanssi_related_include_ids'] ) ) {
		delete_post_meta( $post_id, '_relevanssi_related_include_ids' );
		$include_ids_array = explode( ',', $post['relevanssi_related_include_ids'] );
		$valid_ids         = array();
		foreach ( $include_ids_array as $id ) {
			$id = (int) trim( $id );
			if ( is_int( $id ) ) {
				if ( get_post( $id ) ) {
					$valid_ids[] = $id;
				}
			}
		}
		if ( ! empty( $valid_ids ) ) {
			$id_string = implode( ',', $valid_ids );
			add_post_meta( $post_id, '_relevanssi_related_include_ids', $id_string );
		}
	} else {
		delete_post_meta( $post_id, '_relevanssi_related_include_ids' );
	}

	// Clear the related posts cache for this post.
	delete_post_meta( $post_id, '_relevanssi_related_posts' );
}

/**
 * Prints out the metabox part for related posts.
 *
 * @param int $post_id The post ID.
 */
function relevanssi_related_posts_metabox( $post_id ) {
	$related     = get_post_meta( $post_id, '_relevanssi_related_keywords', true );
	$include_ids = get_post_meta( $post_id, '_relevanssi_related_include_ids', true );
	$no_append   = checked( 'on', get_post_meta( $post_id, '_relevanssi_related_no_append', true ), false );
	$not_related = checked( 'on', get_post_meta( $post_id, '_relevanssi_related_not_related', true ), false );

	if ( '0' === $include_ids ) {
		$include_ids = '';
	}
	?>
	<div class="section relevanssi-related-posts">
		<h3><?php esc_html_e( 'Related Posts', 'relevanssi' ); ?></h3>
		<div class="contents">

			<p class="checkbox"><label><input type="checkbox" name="relevanssi_related_no_append" id="relevanssi_related_no_append" <?php echo esc_attr( $no_append ); ?>/>
			<?php esc_html_e( "Don't append the related posts to this page.", 'relevanssi' ); ?></label></p>

			<p class="checkbox"><label><input type="checkbox" name="relevanssi_related_not_related" id="relevanssi_related_not_related" <?php echo esc_attr( $not_related ); ?>/>
			<?php esc_html_e( "Don't show this as a related post for any post.", 'relevanssi' ); ?></label></p>

			<p><strong><?php esc_html_e( 'Related Posts keywords', 'relevanssi' ); ?></strong></p>
			<p><?php esc_html_e( 'A comma-separated list of keywords to use for the Related Posts feature. Anything entered here will be used when searching for related posts. Using phrases with quotes is allowed, but will restrict the related posts to posts including that phrase.', 'relevanssi' ); ?></p>
			<label for="relevanssi_related_keywords" class="screen-reader-text"><?php esc_html_e( 'Related posts keywords for this post', 'relevanssi' ); ?></label>
			<p><textarea id="relevanssi_related_keywords" name="relevanssi_related_keywords" cols="30" rows="2" style="max-width: 100%"><?php echo esc_html( $related ); ?></textarea></p>

			<p><label for="relevanssi_related_include_ids"><?php esc_html_e( 'A comma-separated list of post IDs to use as related posts for this post', 'relevanssi' ); ?></label>:</p>
			<p><input type="text" id="relevanssi_related_include_ids" name="relevanssi_related_include_ids" value="<?php echo esc_html( $include_ids ); ?>"/></p>

			<p><?php esc_html_e( 'These are the related posts Relevanssi currently will show for this post:', 'relevanssi' ); ?></p>

			<input type="hidden" id="this_post_id" value="<?php echo esc_attr( $post_id ); ?>" />
			<ol id='related_posts_list'>
			<?php
			echo relevanssi_generate_related_list( $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
			</ol>

			<p><?php esc_html_e( 'These posts are excluded from related posts for this post', 'relevanssi' ); ?>:</p>
			<ul id='excluded_posts_list'>
			<?php
			echo relevanssi_generate_excluded_list( $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
			</ul>
		</div>
	</div>
	<div class="section relevanssi-insights">
		<h3><?php esc_html_e( 'Insights', 'relevanssi' ); ?></h3>
		<div class="contents">
			<p><?php esc_html_e( 'The most common search terms for this post', 'relevanssi' ); ?>:</p>
			<ol id='most_common_terms'>
			<?php
			echo relevanssi_generate_tracking_insights_most_common( $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
			</ol>

			<p><?php esc_html_e( 'Low-ranking search terms for this post', 'relevanssi' ); ?>:</p>
			<ol id='low_ranking_terms'>
			<?php
			echo relevanssi_generate_tracking_insights_low_ranking( $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
			</ol>
		</div>
	</div>
	<?php
}

/**
 * Generates tracking insights.
 *
 * @param int    $post_id The post ID.
 * @param string $output  If 'HTML', output HTML code. If 'ARRAY', output an
 * array. Default value is 'HTML'.
 */
function relevanssi_generate_tracking_insights_most_common( int $post_id, string $output = 'HTML' ) {
	global $wpdb, $relevanssi_variables;
	$table = $relevanssi_variables['tracking_table'];

	$output_html = 'ARRAY' !== $output ? true : false;
	if ( $output_html ) {
		$list = '';
	} else {
		$list = array();
	}

	$common_terms = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT DISTINCT(query), COUNT(*) AS `count` FROM $table" . // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared
			' WHERE post_id = %d
			 GROUP BY query
			 ORDER BY count DESC
			 LIMIT 5',
			$post_id
		)
	);

	if ( $common_terms ) {
		if ( $output_html ) {
			$list = '<li>' . implode(
				'</li><li>',
				array_map(
					function ( $v ) {
						return "$v->query ($v->count)";
					},
					$common_terms
				)
			) . '</li></ol>';
		} else {
			$list = $common_terms;
		}
	}

	return $list;
}

/**
 * Generates tracking insights.
 *
 * @param int    $post_id The post ID.
 * @param string $output  If 'HTML', output HTML code. If 'ARRAY', output an
 * array. Default value is 'HTML'.
 */
function relevanssi_generate_tracking_insights_low_ranking( int $post_id, string $output = 'HTML' ) {
	global $wpdb, $relevanssi_variables;
	$table = $relevanssi_variables['tracking_table'];

	$output_html = 'ARRAY' !== $output ? true : false;
	if ( $output_html ) {
		$list = '';
	} else {
		$list = array();
	}

	$low_ranking_terms = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT `query`, `rank` FROM $table" . // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared
			' WHERE post_id = %d
			 AND `rank` > 1
			 ORDER BY `rank` DESC
			 LIMIT 5',
			$post_id
		)
	);

	if ( $low_ranking_terms ) {
		if ( $output_html ) {
			$list = '<li>' . implode(
				'</li><li>',
				array_map(
					function ( $v ) {
						return "$v->query ($v->rank)";
					},
					$low_ranking_terms
				)
			) . '</li></ol>';
		} else {
			$list = $low_ranking_terms;
		}
	}

	return $list;
}

/**
 * Generates a list of related posts for the related posts metabox.
 *
 * @param int    $post_id The post ID.
 * @param string $output  If 'HTML', output HTML code. If 'ARRAY', output an
 * array. Default value is 'HTML'.
 */
function relevanssi_generate_related_list( $post_id, $output = 'HTML' ) {
	$output_html = 'ARRAY' !== $output ? true : false;
	if ( $output_html ) {
		$list = '';
	} else {
		$list = array();
	}
	$related_posts = relevanssi_get_related_post_ids( $post_id );
	foreach ( $related_posts as $related_post_id ) {
		$title = get_the_title( $related_post_id );
		$link  = get_permalink( $related_post_id );
		if ( $output_html ) {
			$list .= '<li><a href="' . esc_attr( $link ) . '">'
				. esc_html( $title ) . '</a> '
				. '(<button type="button" class="removepost" data-removepost="'
				. esc_attr( $related_post_id ) . '">'
				. esc_html__( 'not this', 'relevanssi' ) .
				'</button>)</li>';
		} else {
			$list[] = array(
				'id'    => $related_post_id,
				'title' => $title,
				'link'  => $link,
			);
		}
	}
	return $list;
}

/**
 * Generates a list of excluded posts for the related posts metabox.
 *
 * @param int    $post_id The post ID.
 * @param string $output  If 'HTML', output HTML code. If 'ARRAY', output an
 * array. Default value is 'HTML'.
 */
function relevanssi_generate_excluded_list( $post_id, $output = 'HTML' ) {
	$output_html = 'ARRAY' !== $output ? true : false;
	if ( $output_html ) {
		$list = '';
	} else {
		$list = array();
	}
	$excluded_posts = get_post_meta( $post_id, '_relevanssi_related_exclude_ids', true );
	if ( $excluded_posts ) {
		$excluded_array = explode( ',', $excluded_posts );
		foreach ( $excluded_array as $excluded_post_id ) {
			$title = get_the_title( $excluded_post_id );
			$link  = get_permalink( $excluded_post_id );
			if ( $output_html ) {
				$list .= '<li><a href="' . esc_attr( $link ) . '">' . esc_html( $title ) . '</a> (<button type="button" class="returnpost" data-returnpost="' . esc_attr( $excluded_post_id ) . '">' . esc_html__( 'use this', 'relevanssi' ) . '</button>)</li>';
			} else {
				$list[] = array(
					'id'    => $excluded_post_id,
					'title' => $title,
					'link'  => $link,
				);
			}
		}
	} elseif ( $output_html ) {
		$list .= '<li>' . esc_html__( 'Nothing excluded.', 'relevanssi' ) . '</li>';
	}
	return $list;
}
