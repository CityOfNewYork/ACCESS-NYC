<?php
/**
 * /premium/class-relevanssi-wp-cli-command.php
 *
 * @package Relevanssi_Premium
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

/**
 * Relevanssi Premium WP CLI command.
 *
 * Implements the WP CLI support for Relevanssi Premium.
 */
class Relevanssi_WP_CLI_Command extends WP_CLI_Command {

	/**
	 * Rebuilds the index or reindexes one post.
	 *
	 * ## OPTIONS
	 *
	 * [--target=<target>]
	 * : What to index. No value means everything. Valid values are
	 * "taxonomies" and "users" to index taxonomy terms and user profiles
	 * respectively.
	 * ---
	 * options:
	 *   - post_types
	 *   - taxonomies
	 *   - users
	 * ---
	 *
	 * [--post=<post_ID>]
	 * : Post ID, if you only want to reindex one post.
	 *
	 * [--limit=<limit>]
	 * : Number of posts you want to index at one go.
	 *
	 * [--extend=<extend>]
	 * : If true, do not truncate the index or index users and taxonomies.
	 * If false, first truncate the index, then index user profiles and
	 * taxonomy terms.
	 * ---
	 * default: false
	 * options:
	 *   - true
	 *   - false
	 * ---
	 *
	 * [--index_debug=<debug>]
	 * : If true, display debugging information when indexing a single post.
	 * ---
	 * default: false
	 * options:
	 *   - true
	 *   - false
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp relevanssi index
	 *     wp relevanssi index --post=1
	 *     wp relevanssi index --target=taxonomies
	 *     wp relevanssi index --target=users
	 *     wp relevanssi index --post=1 --index_debug=true
	 *     wp relevanssi index --limit=100
	 *     wp relevanssi index --extend=true --limit=100
	 *
	 * @param array $args       Command arguments (not used).
	 * @param array $assoc_args Command arguments as associative array.
	 */
	public function index( $args, $assoc_args ) {
		remove_filter( 'relevanssi_search_ok', '__return_true' );

		$post_id = null;
		if ( isset( $assoc_args['post'] ) ) {
			$post_id = $assoc_args['post'];
		}

		$limit = null;
		if ( isset( $assoc_args['limit'] ) ) {
			$limit = $assoc_args['limit'];
		}

		$extend = false;
		if ( isset( $assoc_args['extend'] ) ) {
			$extend = $assoc_args['extend'];
		}

		$debug = false;
		if ( isset( $assoc_args['index_debug'] ) ) {
			$debug = $assoc_args['index_debug'];
		}

		$target = null;
		if ( isset( $assoc_args['target'] ) ) {
			$target = $assoc_args['target'];
		}

		if ( 'taxonomies' === $target ) {
			relevanssi_index_taxonomies();
			WP_CLI::success( 'Done!' );
		} elseif ( 'users' === $target ) {
			relevanssi_index_users();
			WP_CLI::success( 'Done!' );
		} elseif ( 'post_types' === $target ) {
			relevanssi_index_post_type_archives();
			WP_CLI::success( 'Done!' );
		} elseif ( isset( $post_id ) ) {
			$n = relevanssi_index_doc( $post_id, true, relevanssi_get_custom_fields(), true, $debug );
			switch ( $n ) {
				case -1:
					WP_CLI::error( "No such post: $post_id!" );
					break;
				case 'hide':
					WP_CLI::error( "Post $post_id is excluded from indexing." );
					break;
				case 'donotindex':
					WP_CLI::error( "Post $post_id is excluded from indexing by the relevanssi_do_not_index filter." );
					break;
				default:
					WP_CLI::success( "Reindexed post $post_id!" );
			}
		} else {
			$verbose              = false;
			list( $complete, $n ) = relevanssi_build_index( $extend, $verbose, $limit );

			$completion = 'Index is not complete yet.';
			if ( $complete ) {
				$completion = 'Index is complete.';
			}

			WP_CLI::success( "$n posts indexed. $completion" );
		}
	}

	/**
	 * Refreshes the Relevanssi index.
	 *
	 * ## EXAMPLES
	 *
	 *    wp relevanssi refresh
	 *
	 * @param array $args       Command arguments (not used).
	 * @param array $assoc_args Command arguments as associative array.
	 */
	public function refresh( $args, $assoc_args ) {
		$index_post_types  = get_option( 'relevanssi_index_post_types', '' );
		$index_statuses    = apply_filters(
			'relevanssi_valid_status',
			array( 'publish', 'private', 'draft', 'pending', 'future' )
		);
		$all_indexed_posts = get_posts(
			array(
				'post_type'   => $index_post_types,
				'fields'      => 'ids',
				'numberposts' => -1,
				'post_status' => $index_statuses,
			)
		);

		$found_posts = count( $all_indexed_posts );
		$progress    = $this->relevanssi_generate_progress_bar( 'Indexing posts', $found_posts );

		WP_CLI::log( 'Found ' . $found_posts . ' posts to refresh.' );
		foreach ( $all_indexed_posts as $post_id ) {
			relevanssi_index_doc( $post_id, true, relevanssi_get_custom_fields(), true, false );
			$progress->tick();
		}
		$progress->finish();

		WP_CLI::success( 'Index refresh done!' );
	}

	/**
	 * Empties the Relevanssi index.
	 *
	 * ## EXAMPLES
	 *
	 *     wp relevanssi truncate_index
	 *
	 * @param array $args       Command arguments (not used).
	 * @param array $assoc_args Command arguments as associative array.
	 */
	public function truncate_index( $args, $assoc_args ) {
		$result = relevanssi_truncate_index();
		switch ( $result ) {
			case false:
				WP_CLI::error( "Couldn't truncate the Relevanssi database!" );
				break;
			default:
				WP_CLI::success( 'Relevanssi database truncated.' );
		}
	}

	/**
	 * Adds a stopword to the list of stopwords and removes it from the index.
	 *
	 * ## OPTIONS
	 *
	 * <stopword>...
	 * : Stopwords to add.
	 *
	 * ## EXAMPLES
	 *
	 *     wp relevanssi add_stopword stop halt seis
	 *
	 * @param array $args       Command arguments (not used).
	 * @param array $assoc_args Command arguments as associative array.
	 */
	public function add_stopword( $args, $assoc_args ) {
		if ( is_array( $args ) ) {
			foreach ( $args as $stopword ) {
				if ( relevanssi_add_single_stopword( $stopword ) ) {
					WP_CLI::success( "Stopword added: $stopword" );
				} else {
					WP_CLI::error( "Couldn't add stopword: $stopword!" );
				}
			}
		} else {
			WP_CLI::error( 'No stopwords listed.' );
		}
	}

	/**
	 * Removes a stopword from the list of stopwords. Reindex to get it back to the index.
	 *
	 * ## OPTIONS
	 *
	 * <stopword>...
	 * : Stopwords to remove.
	 *
	 * ## EXAMPLES
	 *
	 *     wp relevanssi remove_stopword stop halt seis
	 *
	 * @param array $args       Command arguments (not used).
	 * @param array $assoc_args Command arguments as associative array.
	 */
	public function remove_stopword( $args, $assoc_args ) {
		$verbose = false;
		if ( is_array( $args ) ) {
			foreach ( $args as $stopword ) {
				if ( relevanssi_remove_stopword( $stopword, $verbose ) ) {
					WP_CLI::success( "Stopword removed: $stopword" );
				} else {
					WP_CLI::error( "Couldn't remove stopword: $stopword!" );
				}
			}
		} else {
			WP_CLI::error( 'No stopwords listed.' );
		}
	}

	/**
	 * Empties the Relevanssi logs.
	 *
	 * ## EXAMPLES
	 *
	 *     wp relevanssi reset_log
	 *
	 * @param array $args       Command arguments (not used).
	 * @param array $assoc_args Command arguments as associative array.
	 */
	public function reset_log( $args, $assoc_args ) {
		$verbose = false;
		$result  = relevanssi_truncate_logs( $verbose );
		switch ( $result ) {
			case false:
				WP_CLI::error( "Couldn't reset the logs!" );
				break;
			default:
				WP_CLI::success( 'Relevanssi log truncated.' );
		}
	}

	/**
	 * Shows common words in the index.
	 *
	 * ## OPTIONS
	 *
	 * [--limit=<limit>]
	 * : How many words to show. Defaults to 25.
	 *
	 * ## EXAMPLES
	 *
	 *     wp relevanssi common
	 *
	 * @param array $args       Command arguments (not used).
	 * @param array $assoc_args Command arguments as associative array.
	 */
	public function common( $args, $assoc_args ) {
		$wp_cli = true;
		$limit  = 25;
		if ( isset( $assoc_args['limit'] ) && is_numeric( $assoc_args['limit'] ) ) {
			$limit = $assoc_args['limit'];
		}

		$words = relevanssi_common_words( $limit, $wp_cli );
		if ( is_array( $words ) ) {
			foreach ( $words as $word ) {
				WP_CLI::log( sprintf( '%s (%d)', $word->term, $word->cnt ) );
			}
		} else {
			WP_CLI::error( 'No words returned.' );
		}
	}

	/**
	 * Reads the attachment content for all attachments that haven't been read
	 * yet.
	 *
	 * ## EXAMPLES
	 *
	 *      wp relevanssi read_attachments
	 *
	 * @param array $args       Command arguments (not used).
	 * @param array $assoc_args Command arguments as associative array.
	 */
	public function read_attachments( $args, $assoc_args ) {
		$attachment_posts = relevanssi_get_posts_with_attachments( 0 );
		WP_CLI::log( 'Found ' . count( $attachment_posts ) . ' attachments to read.' );
		foreach ( $attachment_posts as $post_id ) {
			$exit_and_die = false;
			WP_CLI::log( 'Reading attachment ' . $post_id . '...' );
			$response = relevanssi_index_pdf( $post_id, $exit_and_die );
			if ( $response['success'] ) {
				WP_CLI::log( "Successfully read the content for post $post_id." );
			} else {
				WP_CLI::log( "Couldn't read the post $post_id: " . $response['error'] );
			}
		}
	}

	/**
	 * Removes all attachment content for all posts. Use with care! Does not
	 * reindex the posts or remove them from the index.
	 *
	 * ## EXAMPLES
	 *
	 *      wp relevanssi remove_attachment_content
	 *
	 * @param array $args       Command arguments (not used).
	 * @param array $assoc_args Command arguments as associative array (not
	 * used).
	 */
	public function remove_attachment_content( $args, $assoc_args ) {
		delete_post_meta_by_key( '_relevanssi_pdf_content' );
		delete_post_meta_by_key( '_relevanssi_pdf_error' );

		WP_CLI::log( 'Removed all attachment content.' );
	}

	/**
	 * Removes all attachment errors for all posts.
	 *
	 * ## EXAMPLES
	 *
	 *      wp relevanssi remove_attachment_errors
	 *
	 * @param array $args       Command arguments (not used).
	 * @param array $assoc_args Command arguments as associative array (not
	 * used).
	 */
	public function remove_attachment_errors( $args, $assoc_args ) {
		delete_post_meta_by_key( '_relevanssi_pdf_error' );

		WP_CLI::log( 'Removed all attachment errors.' );
	}

	/**
	 * Regenerates the related posts for all posts.
	 *
	 * ## OPTIONS
	 *
	 * [--post_type=<post_types>]
	 * : A comma-separated list of post types to cover. If empty, generate the post
	 * types chosen in the Related posts options, or if that's empty, all public post
	 * types.
	 *
	 * [--post_objects=<post_objects>]
	 * : If true, doesn't generate the related posts HTML code and instead stores the
	 * post objects of the related posts in the transient. If false, the transient
	 * will contain the generated related posts HTML code. Default false.
	 * ---
	 * default: false
	 * options:
	 *   - true
	 *   - false
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *      wp relevanssi regenerate_related
	 *      wp relevanssi regenerate_related --post_type=post,page
	 *
	 * @param array $args       Command arguments (not used).
	 * @param array $assoc_args Command arguments as associative array.
	 */
	public function regenerate_related( $args, $assoc_args ) {
		relevanssi_flush_related_cache();

		$settings = get_option( 'relevanssi_related_settings', relevanssi_related_default_settings() );

		if ( isset( $settings['enabled'] ) && 'off' === $settings['enabled'] ) {
			WP_CLI::error( 'Related posts feature is disabled.' );
		}

		$post_types = array();
		if ( isset( $settings['append'] ) && ! empty( $settings['append'] ) ) {
			// Related posts are automatically appended to certain post types, so
			// regenerate the related posts for those post types.
			$post_types = explode( ',', $settings['append'] );
		} else {
			// Nothing set, so regenerate for all public post types.
			$pt_args    = array(
				'public' => true,
			);
			$post_types = get_post_types( $pt_args, 'names' );
		}

		if ( isset( $assoc_args['post_type'] ) ) {
			$post_types = explode( ',', $assoc_args['post_type'] );
		}

		$post_objects = false;
		if ( isset( $assoc_args['post_objects'] ) ) {
			if ( filter_var( $assoc_args['post_objects'], FILTER_VALIDATE_BOOLEAN ) ) {
				$post_objects = true;
			}
		}

		$post_args = array(
			'post_type'      => $post_types,
			'fields'         => 'ids',
			'posts_per_page' => -1,
		);
		$posts     = get_posts( $post_args ); // Get all posts for the wanted post types.
		$count     = count( $posts );
		WP_CLI::log( 'Regenerating related posts for post types ' . implode( ', ', $post_types ) . ", total $count posts." );

		$progress = $this->relevanssi_generate_progress_bar( 'Regenerating', $count );

		foreach ( $posts as $post_id ) {
			relevanssi_related_posts( $post_id, $post_objects );
			$progress->tick();
		}

		$progress->finish();
		WP_CLI::success( 'Done!' );
	}

	/**
	 * Lists pinned posts.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Format of the results. Possible values are "table", "json", "csv",
	 * "yaml" and "count". Default: "table".
	 *
	 * [--type=<type>]
	 * : Type of the results. Possible values are "pinned", "unpinned", and
	 * "both". Default: "both".
	 *
	 * ## EXAMPLES
	 *
	 *      wp relevanssi list_pinned_posts
	 *      wp relevanssi list_pinned_posts --format=csv --type=pinned
	 *
	 * @param array $args       Command arguments (not used).
	 * @param array $assoc_args Command arguments as associative array.
	 */
	public function list_pinned_posts( $args, $assoc_args ) {
		global $wpdb;

		$posts = $wpdb->get_results(
			"SELECT p.ID, p.post_title, pm.meta_key, pm.meta_value FROM $wpdb->posts AS p, $wpdb->postmeta AS pm
			WHERE p.ID = pm.post_id
				AND (
    				pm.meta_key = '_relevanssi_pin'
    				OR pm.meta_key = '_relevanssi_unpin'
    				OR (
        				pm.meta_key = '_relevanssi_pin_for_all' AND pm.meta_value = 'on'
    				)
				)
			ORDER BY p.ID ASC"
		);

		$pinned   = array();
		$unpinned = array();

		$format = $assoc_args['format'] ?? 'table';
		if ( 'ids' === $format ) {
			$format = 'table';
		}

		$include_pinned   = true;
		$include_unpinned = true;
		if ( isset( $assoc_args['type'] ) ) {
			if ( 'pinned' === $assoc_args['type'] ) {
				$include_unpinned = false;
			} elseif ( 'unpinned' === $assoc_args['type'] ) {
				$include_pinned = false;
			}
		}

		foreach ( $posts as $post ) {
			if ( $include_pinned && '_relevanssi_pin_for_all' === $post->meta_key ) {
				$pinned[] = array(
					'ID'              => $post->ID,
					'Title'           => $post->post_title,
					'Pinned keywords' => __( 'all keywords' ),
				);
			} elseif ( $include_pinned && '_relevanssi_pin' === $post->meta_key ) {
				$pinned[] = array(
					'ID'              => $post->ID,
					'Title'           => $post->post_title,
					'Pinned keywords' => $post->meta_value,
				);
			} elseif ( $include_unpinned && '_relevanssi_unpin' === $post->meta_key ) {
				$unpinned[] = array(
					'ID'                => $post->ID,
					'Title'             => $post->post_title,
					'Unpinned keywords' => $post->meta_value,
				);
			}
		}

		if ( $pinned ) {
			if ( 'table' === $format || 'count' === $format ) {
				$header = "\n" . __( 'Pinned posts' ) . ':';
				WP_CLI::log( $header );
				WP_CLI::log( str_pad( '', strlen( $header ), '=' ) );
			}
			WP_CLI\Utils\format_items( $format, $pinned, array( 'ID', 'Title', 'Pinned keywords' ) );
		}

		if ( $unpinned ) {
			if ( 'table' === $format || 'count' === $format ) {
				$header = "\n" . __( 'Unpinned posts' ) . ':';
				WP_CLI::log( $header );
				WP_CLI::log( str_pad( '', strlen( $header ), '=' ) );
			}
			WP_CLI\Utils\format_items( $format, $unpinned, array( 'ID', 'Title', 'Unpinned keywords' ) );
		}

		if ( ! $pinned && ! $unpinned ) {
			WP_CLI::log( __( 'No pinned posts found.' ) );
		}
	}

	/**
	 * Lists posts in or not in the index.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Format of the results. Possible values are "table", "json", "csv",
	 * "yaml", and "count". Default: "table".
	 *
	 * [--post_type=<type>]
	 * : Post type. Default: none, show all post types.
	 *
	 * [--type=<type>]
	 * : Type of the results. Possible values are "post", "term", and "user".
	 * Default: "post".
	 *
	 * [--status=<status>]
	 * : Status of the results. Possible values are "indexed" and "unindexed".
	 * Default: "indexed".
	 *
	 * ## EXAMPLES
	 *
	 *      wp relevanssi list
	 *
	 * @param array $args       Command arguments (not used).
	 * @param array $assoc_args Command arguments as associative array.
	 */
	public function list( $args, $assoc_args ) {
		global $wpdb, $relevanssi_variables;

		$status    = $assoc_args['status'] ?? 'indexed';
		$post_type = $assoc_args['post_type'] ?? '';
		$type      = $assoc_args['type'] ?? 'post';

		if ( 'indexed' === $status ) {
			if ( 'post' === $type ) {
				if ( $post_type ) {
					$post_type = "AND post_type = '" . esc_sql( $post_type ) . "'";
				}
				$posts = $wpdb->get_results(
					"SELECT DISTINCT(p.ID), p.post_title AS Title, p.post_type AS 'Type' FROM $wpdb->posts AS p, " .
					"{$relevanssi_variables['relevanssi_table']} AS r WHERE p.ID = r.doc $post_type " . // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					'ORDER BY p.ID ASC'
				);
			} elseif ( 'term' === $type ) {
				$posts = $wpdb->get_results(
					"SELECT DISTINCT(t.term_id) AS ID, t.name AS 'Name', tt.taxonomy AS 'Taxonomy', tt.count AS 'Count' FROM $wpdb->terms AS t, $wpdb->term_taxonomy AS tt, " .
					"{$relevanssi_variables['relevanssi_table']} AS r " . // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					'WHERE tt.term_id = t.term_id AND t.term_id = r.item AND tt.taxonomy = r.type
					ORDER BY t.term_id ASC'
				);
			} elseif ( 'user' === $type ) {
				$users_in_index = $wpdb->get_col(
					"SELECT DISTINCT(r.item) FROM {$relevanssi_variables['relevanssi_table']} AS r " . // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"WHERE r.type = 'user'"
				);
				$users          = relevanssi_get_users(
					array(
						'number'  => -1,
						'orderby' => 'ID',
						'order'   => 'ASC',
						'include' => array_map( 'intval', $users_in_index ),
					)
				);
				foreach ( $users as $user ) {
					$posts[] = array(
						'ID'    => $user->ID,
						'Name'  => $user->display_name,
						'Login' => $user->user_login,
						'Roles' => implode( ', ', $user->roles ),
					);
				}
			}
		} elseif ( 'unindexed' === $status ) {
			if ( 'post' === $type ) {
				$indexed_post_types = str_replace( 'post.', 'p.', relevanssi_post_type_restriction() );
				if ( $post_type ) {
					$post_type          = "AND post_type = '" . esc_sql( $post_type ) . "'";
					$indexed_post_types = '';
				}

				$posts = $wpdb->get_results(
					"SELECT DISTINCT(p.ID), p.post_title AS Title, p.post_type AS 'Type' FROM $wpdb->posts AS p " .
					"LEFT JOIN {$relevanssi_variables['relevanssi_table']} AS r ON p.ID = r.doc " . // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					'WHERE r.doc IS NULL ' .
					"$indexed_post_types $post_type " . // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					'ORDER BY p.ID ASC'
				);
			} elseif ( 'term' === $type ) {
				$taxonomies = get_option( 'relevanssi_index_terms' );
				if ( ! is_array( $taxonomies ) ) {
					$taxonomies = array();
				}
				$taxonomies = "'" . implode( "', '", $taxonomies ) . "'";

				$posts = $wpdb->get_results(
					"SELECT DISTINCT(t.term_id) AS ID, t.name AS 'Name', tt.taxonomy AS 'Taxonomy', tt.count AS 'Count' " .
					"FROM $wpdb->term_taxonomy AS tt, $wpdb->terms AS t " .
					"LEFT JOIN {$relevanssi_variables['relevanssi_table']} AS r " . // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					'ON t.term_id = r.item
					WHERE r.item IS NULL AND tt.term_id = t.term_id AND tt.taxonomy IN (' . $taxonomies . ') ' . // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					'ORDER BY t.term_id ASC'
				);
			} elseif ( 'user' === $type ) {
				$users_in_index = $wpdb->get_col(
					"SELECT DISTINCT(r.item) FROM {$relevanssi_variables['relevanssi_table']} AS r " . // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"WHERE r.type = 'user'"
				);
				$users          = relevanssi_get_users(
					array(
						'number'  => -1,
						'orderby' => 'ID',
						'order'   => 'ASC',
						'exclude' => $users_in_index,
					)
				);
				foreach ( $users as $user ) {
					$posts[] = array(
						'ID'    => $user->ID,
						'Name'  => $user->display_name,
						'Login' => $user->user_login,
						'Roles' => implode( ', ', $user->roles ),
					);
				}
			}
		}

		$format = $assoc_args['format'] ?? 'table';
		if ( 'ids' === $format ) {
			$format = 'table';
		}

		if ( 'post' === $type ) {
			WP_CLI\Utils\format_items( $format, $posts, array( 'ID', 'Title', 'Type' ) );
		} elseif ( 'term' === $type ) {
			WP_CLI\Utils\format_items( $format, $posts, array( 'ID', 'Name', 'Taxonomy', 'Count' ) );
		} elseif ( 'user' === $type ) {
			WP_CLI\Utils\format_items( $format, $posts, array( 'ID', 'Name', 'Login', 'Roles' ) );
		} elseif ( 'post_type' === $type ) {
			WP_CLI\Utils\format_items( $format, $posts, array( 'Title', 'Type' ) );
		}
	}

	/**
	 * Generates a WP CLI progress bar.
	 *
	 * If WP CLI is enabled, creates a progress bar using WP_CLI\Utils\make_progress_bar().
	 *
	 * @param string $title Title of the progress bar.
	 * @param int    $count Total count for the bar.
	 */
	public static function relevanssi_generate_progress_bar( $title, $count ) {
		$progress = null;
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			$progress = WP_CLI\Utils\make_progress_bar( $title, $count );
		}
		return $progress;
	}
}

WP_CLI::add_command( 'relevanssi', 'Relevanssi_WP_Cli_Command' );
