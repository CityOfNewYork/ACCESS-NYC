<?php
namespace GatherContent\Importer\Post_Types;
use GatherContent\Importer\Mapping_Post;
use GatherContent\Importer\API;
use WP_Query;
use WP_Error;

class Template_Mappings extends Base {
	const SLUG = 'gc_templates';
	public $slug = self::SLUG;
	public $listing_url = '';

	/**
	 * GatherContent\Importer\API instance
	 *
	 * @var GatherContent\Importer\API
	 */
	protected $api;

	/**
	 * Creates an instance of this class.
	 *
	 * @since 3.0.0
	 *
	 * @param $parent_menu_slug
	 * @param $api API object
	 */
	public function __construct( $parent_menu_slug, API $api ) {
		$this->api = $api;
		$this->listing_url = admin_url( 'edit.php?post_type=' . self::SLUG );
		new Async_Save_Hook( self::SLUG );

		parent::__construct(
			array(
				'name'                  => _x( 'Template Mappings', 'post type general name', 'gathercontent-import' ),
				'singular_name'         => _x( 'Template Mapping', 'post type singular name', 'gathercontent-import' ),
				'add_new'               => _x( 'Add New', 'post', 'gathercontent-import' ),
				'add_new_item'          => __( 'Add New Template Mapping', 'gathercontent-import' ),
				'edit_item'             => __( 'Edit Template Mapping', 'gathercontent-import' ),
				'new_item'              => __( 'New Template Mapping', 'gathercontent-import' ),
				'view_item'             => __( 'View Template Mapping', 'gathercontent-import' ),
				'item_updated'          => __( 'Template Mapping updated', 'gathercontent-import' ),
				'item_saved'            => __( 'Template Mapping saved', 'gathercontent-import' ),
				'search_items'          => __( 'Search Template Mappings', 'gathercontent-import' ),
				'not_found'             => __( 'No template mappings found.', 'gathercontent-import' ),
				'not_found_in_trash'    => __( 'No template mappings found in Trash.', 'gathercontent-import' ),
				'all_items'             => __( 'Template Mappings', 'gathercontent-import' ),
				'archives'              => __( 'Template Mapping Archives', 'gathercontent-import' ),
				'insert_into_item'      => __( 'Insert into template mapping', 'gathercontent-import' ),
				'uploaded_to_this_item' => __( 'Uploaded to this template mapping', 'gathercontent-import' ),
				'filter_items_list'     => __( 'Filter template mappings list', 'gathercontent-import' ),
				'items_list_navigation' => __( 'Template Mappings list navigation', 'gathercontent-import' ),
				'items_list'            => __( 'Template Mappings list', 'gathercontent-import' ),
			),
			array(
				'show_ui'              => true,
				'show_in_menu'         => false,
				'show_in_menu'         => $parent_menu_slug,
				'supports'             => array( 'title' ),
				'rewrite'              => false,
			)
		);
	}

	public function register_post_type() {
		parent::register_post_type();

		$post_type = self::SLUG;
		add_filter( "manage_{$post_type}_posts_columns", array( $this, 'register_column_headers' ) );
		add_action( "manage_{$post_type}_posts_custom_column", array( $this, 'column_display' ), 10, 2 );
		add_filter( "manage_edit-{$post_type}_sortable_columns", array( $this, 'register_sortable_columns' ) );

		add_action( 'pre_get_posts', array( $this, 'orderby_meta' ) );

		add_action( 'edit_form_after_title', array( $this, 'output_mapping_data' ) );

		if ( ! isset( $_GET['gc_standard_edit_links'] ) ) {
			add_filter( 'get_edit_post_link', array( $this, 'modify_mapping_post_edit_link' ), 10, 2 );
		}

		add_filter( 'post_row_actions', array( $this, 'remove_quick_edit' ), 10, 2 );
		add_action( "wp_async_save_post_{$post_type}", array( $this, 'clear_out_updated_at' ) );

		add_filter( 'wp_insert_post_empty_content', array( $this, 'trigger_pre_actions' ), 5, 2 );
	}

	public function clear_out_updated_at( $post_id ) {
		$types = array();
		$all_types = self::get_mapping_post_types();
		foreach ( $all_types as $type => $mapping_ids ) {
			if ( isset( $mapping_ids[ $post_id ] ) ) {
				$types[] = $type;
			}
		}

		$args = array(
			// Get all posts in post-types which have this mapping ID set...
			'post_type'   => $types,
			'post_status' => 'any',
			'fields'      => 'ids',
			'meta_query'  => array(
				// And limit to posts which have this mapping ID set...
				array(
					'key'   => '_gc_mapping_id',
					'value' => $post_id,
				),
				// And that also have the item mapped meta, with the updated_at value.
				array(
					'key'     => '_gc_mapped_meta',
					'value'   => 'updated_at',
					'compare' => 'LIKE',
				),
			),
		);

		$query = new WP_Query( $args );

		if ( ! $query->have_posts() ) {
			return;
		}

		foreach ( $query->posts as $post_id ) {
			$meta = \GatherContent\Importer\get_post_item_meta( $post_id );
			// Only update the meta if they have the updated_at value.
			if ( is_array( $meta ) ) {

				// Set the updated_at value to 0.
				$meta['updated_at'] = 0;
				\GatherContent\Importer\update_post_item_meta( $post_id, $meta );
			}
		}
	}

	/**
	 * Register the Template Mappings column headers.
	 *
	 * @since 3.0.0
	 *
	 * @param array $columns Array of registered columns for the mapping post-type.
	 */
	public function register_column_headers( $columns ) {
		$columns['account'] = __( 'Account slug', 'gathercontent-import' );
		$columns['project'] = __( 'Project id', 'gathercontent-import' );
		$columns['template'] = __( 'Template id', 'gathercontent-import' );

		return $columns;
	}

	/**
	 * Register the Template Mappings sortable columns.
	 *
	 * @since 3.0.0
	 *
	 * @param array $columns Array of registered columns for the mapping post-type.
	 */
	public function register_sortable_columns( $columns ) {
		$columns['account'] = '_gc_account';
		$columns['project'] = '_gc_project';
		$columns['template'] = '_gc_template';

		return $columns;
	}

	/**
	 * Make the Template Mapping sortable columns work.
	 *
	 * @since 3.0.0
	 *
	 * @param WP_Query $query
	 */
	public function orderby_meta( $query ) {
		if ( ! is_admin() ) {
			return;
		}

		$orderby = $query->get( 'orderby' );

		if ( ! in_array( $orderby, array( '_gc_account', '_gc_project', '_gc_template' ), 1 ) ) {
			return;
		}

		$query->set( 'meta_key', $orderby );
		$query->set( 'orderby', '_gc_account' === $orderby ? 'meta_value' : 'meta_value_num' );
	}

	/**
	 * The Template Mappings column display output.
	 *
	 * @since  3.0.0
	 *
	 * @param string $column  Column ID
	 * @param int    $post_id Mapping post id
	 */
	public function column_display( $column, $post_id ) {
		if ( ! in_array( $column, array( 'account', 'project', 'template' ), 1 ) ) {
			return;
		}

		$data = $this->column_post_data( $post_id );
		$url = $value = '';

		switch ( $column ) {
			case 'account':
				$value = $data[ $column ] ?: __( '&mdash;' );
				if ( $data['base_url'] && $data['account'] ) {
					$url = $data['base_url'];
				}
				break;
			case 'project':
				$value = $data[ $column ] ?: __( '&mdash;' );
				if ( $data['base_url'] && $value ) {
					$url = esc_url( $data['base_url'] .'projects/view/'. $value );
				}
				break;
			case 'template':
				$value = $data[ $column ] ?: __( '&mdash;' );
				if ( $data['base_url'] && $data['project'] && $value ) {
					$url = esc_url( $data['base_url'] .'templates/'. $data['project'] );
				}
				break;
		}

		if ( $value ) {
			if ( $url ) {
				echo '<a href="'. esc_url( $url ) .'" target="_blank">';
					print_r( $value );
				echo '</a>';
			} else {
				print_r( $value );
			}
		}
	}

	/**
	 * Collect meta data for mapping.
	 *
	 * @since  3.0.0
	 *
	 * @param  int  $post_id Mapping post id
	 *
	 * @return array         Array of meta dat.
	 */
	protected function column_post_data( $post_id ) {
		static $posts_data = array();

		if ( ! isset( $posts_data[ $post_id ] ) ) {
			$post_data = array(
				'account'  => get_post_meta( $post_id, '_gc_account', 1 ),
				'project'  => get_post_meta( $post_id, '_gc_project', 1 ),
				'template' => get_post_meta( $post_id, '_gc_template', 1 ),
				'base_url' => '',
			);

			if ( $post_data['account'] ) {
				$post_data['base_url'] = 'https://'. $post_data['account'] .'.gathercontent.com/';
			}

			$posts_data[ $post_id ] = $post_data;
		}

		return $posts_data[ $post_id ];
	}

	public function output_mapping_data( $post ) {
		if ( self::SLUG === $post->post_type ) {
			echo '<p class="postbox" style="padding: 1em;background: #f5f5f5;margin: -4px 0 0">';
			echo '<strong>' . __( 'Project ID:', 'gathercontent-import' ) . '</strong> '. get_post_meta( get_the_id(), '_gc_project', 1 );
			echo ',&nbsp;';
			echo '<strong>' . __( 'Template ID:', 'gathercontent-import' ) . '</strong> '. get_post_meta( get_the_id(), '_gc_template', 1 );

			if ( $account = get_post_meta( get_the_id(), '_gc_account', 1 ) ) {
				$account = 'https://'. $account .'.gathercontent.com/';
				echo ',&nbsp;';
				echo '<strong>' . __( 'Account:', 'gathercontent-import' ) . '</strong> <a href="'. esc_url( $account ) .'" target="_blank">'. esc_url( $account ) .'</a>';
			}

			echo '</p>';

			$content = $post->post_content;
			if ( defined( 'JSON_PRETTY_PRINT' ) ) {
				$pretty = json_encode( json_decode( $content ), JSON_PRETTY_PRINT );
				if ( $pretty && $pretty !== $content ) {
					$content = $pretty;
				}
			}

			echo '<pre><textarea name="content" id="content" rows="20" style="width:100%;">'. print_r( $content, true ) .'</textarea></pre>';
		}
	}

	public function modify_mapping_post_edit_link( $link, $post ) {
		$post_type = '';

		if ( isset( $post->ID ) ) {
			$post_id = $post->ID;
			$post_type = $post->post_type;
		} elseif ( is_numeric( $post ) ) {
			$post_id = $post;
			$post_type = get_post_type( $post_id );
		}

		if ( self::SLUG === $post_type ) {

			$project_id = $this->get_mapping_project( $post_id );
			$template_id = $this->get_mapping_template( $post_id );

			if ( $project_id && $template_id ) {
				$link = admin_url( sprintf(
					'admin.php?page=gathercontent-import-add-new-template&project=%s&template=%s&mapping=%s',
					$project_id,
					$template_id,
					$post_id
				) );
			}

		}

		return $link;
	}

	/**
	 * removes quick edit from custom post type list
	 *
	 * @since  3.0.0
	 *
	 * @param array $actions An array of row action links. Defaults are
	 *                         'Edit', 'Quick Edit', 'Restore, 'Trash',
	 *                         'Delete Permanently', 'Preview', and 'View'.
	 * @param WP_Post $post  The post object.
	 *
	 * @return array         Modified $actions.
	 */
	function remove_quick_edit( $actions, $post ) {
		if ( self::SLUG === $post->post_type ) {
			unset( $actions['inline hide-if-no-js'] );

			$actions['sync-items'] = sprintf(
				'<a href="%s" aria-label="%s">%s</a>',
				add_query_arg( 'sync-items', 1,  get_edit_post_link( $post->ID, 'raw' ) ),
				esc_attr( __( 'Review Items for Import', 'gathercontent-import' ) ),
				__( 'Review Items for Import', 'gathercontent-import' )
			);
		}

		return $actions;
	}

	public static function store_post_type_references( $post_data, $post = null, $update = null ) {
		if ( null !== $update ) {
			$post_id   = $post_data;
			$mapping   = Mapping_Post::get( $post );
			$post_data = (array) $post;
		} else {
			$post_id = $post_data['ID'];
			$mapping = Mapping_Post::get( $post_id );
		}

		if ( ! $mapping ) {
			return;
		}

		$all_types = self::get_mapping_post_types();

		$old_post_type = $mapping->data( 'post_type' );

		if ( $old_post_type && isset( $all_types[ $old_post_type ], $all_types[ $old_post_type ][ $mapping->ID ] ) ) {
			unset( $all_types[ $old_post_type ][ $mapping->ID ] );
			if ( empty( $all_types[ $old_post_type ] ) ) {
				unset( $all_types[ $old_post_type ] );
			}
		}

		$new_mapping = json_decode( $post_data['post_content'], 1 );
		if ( isset( $new_mapping['post_type'] ) && $new_mapping['post_type'] ) {
			$all_types[ $new_mapping['post_type'] ][ $mapping->ID ] = 1;
		}

		self::update_mapping_post_types( $all_types );
	}

	public static function create_mapping( $mapping_args, $post_data = array(), $wp_error = false ) {
		$mapping_args = wp_parse_args( $mapping_args, array(
			'title'          => '',
			'content'        => '',
			'account'        => null,
			'project'        => null,
			'template'       => null,
			'structure_uuid' => null,
		) );

		if ( ! empty( $mapping_args['content']['mapping'] ) ) {
			$mapping_args['content']['mapping'] = array_filter( $mapping_args['content']['mapping'], function( $opt ) {
				return ! empty( $opt['value'] ) ? $opt : false;
			} );
		}

		$post_data = wp_parse_args( $post_data, array(
			'post_content' => wp_json_encode( $mapping_args['content'] ),
			'post_title'   => $mapping_args['title'],
			'post_status'  => 'publish',
			'post_type'    => self::SLUG,
			'meta_input'   => array(
				'_gc_account'        => $mapping_args['account'],
				'_gc_project'        => $mapping_args['project'],
				'_gc_template'       => $mapping_args['template'],
				'_gc_structure_uuid' => $mapping_args['structure_uuid'],
			),
		) );

		return wp_insert_post( $post_data, $wp_error );
	}

	public function trigger_pre_actions( $ignore, $post_data ) {
		if ( self::SLUG === $post_data['post_type'] ) {
			if ( ! empty( $post_data['ID'] ) ) {
				do_action( 'gc_mapping_pre_post_update', $post_data );
			} else {
				do_action( 'gc_mapping_pre_post_create', $post_data );
			}

			add_action( 'save_post_' . self::SLUG, array( __CLASS__, 'store_post_type_references' ), 10, 3 );
		}

		return $ignore;
	}

	public static function get_mappings( $args = array() ) {
		$args['post_type'] = self::SLUG;

		return new WP_Query( $args );
	}

	public static function get_by_account_id( $account_id, $args = array() ) {
		$meta_query = array(
			array(
				'key'   => '_gc_account_id',
				'value' => $account_id,
			),
		);

		$args['meta_query'] = isset( $args['meta_query'] )
			? $args['meta_query'] + $meta_query
			: $meta_query;

		return self::get_mappings( $args );
	}

	public static function get_by_account( $account_slug, $args = array() ) {
		$meta_query = array(
			array(
				'key'   => '_gc_account',
				'value' => $account_slug,
			),
		);

		$args['meta_query'] = isset( $args['meta_query'] )
			? $args['meta_query'] + $meta_query
			: $meta_query;

		return self::get_mappings( $args );
	}

	public static function get_by_account_project( $account_id, $project_id, $args = array() ) {
		$meta_query = array(
			array(
				'key'   => '_gc_project',
				'value' => $project_id,
			),
		);

		$args['meta_query'] = isset( $args['meta_query'] )
			? $args['meta_query'] + $meta_query
			: $meta_query;

		return self::get_by_account( $account_id, $args );
	}

	public static function get_by_project( $project_id, $args = array() ) {
		$meta_query = array(
			array(
				'key'   => '_gc_project',
				'value' => $project_id,
			),
		);

		$args['meta_query'] = isset( $args['meta_query'] )
			? $args['meta_query'] + $meta_query
			: $meta_query;

		return self::get_mappings( $args );
	}

	public static function get_by_project_template( $project_id, $template_id, $args = array() ) {
		$meta_query = array(
			array(
				'key'   => '_gc_template',
				'value' => $template_id,
			),
		);

		$args['meta_query'] = isset( $args['meta_query'] )
			? $args['meta_query'] + $meta_query
			: $meta_query;

		$args = wp_parse_args( $args, array(
			'posts_per_page' => 1,
			'fields'         => 'ids',
		) );

		return self::get_by_project( $project_id, $args );
	}

	public function get_by_item_id( $item_id ) {
		$mapping_id = 0;

		$item = $this->api->get_item( $item_id );
		if ( ! $item || empty( $item->project_id ) || empty( $item->project_id ) ) {
			return $mapping_id;
		}

		$mapping = $this->get_by_project_template(
			absint( $item->project_id ),
			absint( $item->template_id )
		);

		if ( $mapping->have_posts() ) {
			$mapping_id = $mapping->posts[0];
		}

		return $mapping_id;
	}

	public function get_project_mappings( $project_id, $mapping_ids = array() ) {
		$args = array(
			'posts_per_page' => 500,
			'no_found_rows'  => true,
		);

		if ( ! empty( $mapping_ids ) ) {
			$args['post__in'] = $mapping_ids;
		}

		$gotten = $this->get_by_project( $project_id, $args );

		$objects = array();

		if ( $gotten->have_posts() ) {
			foreach ( $gotten->posts as $post ) {
				$objects[] = array(
					'id' => $post->ID,
					'name' => $post->post_title,
				);
			}
		}

		return $objects;
	}

	public function get_account_projects_with_mappings( $account_id, $mapping_ids = array() ) {
		$projects = $this->api->get_account_projects( $account_id );
		if ( is_wp_error( $projects ) ) {
			return $projects;
		}

		if ( empty( $projects ) ) {
			new WP_Error( 'gc_no_projects', esc_html__( 'No projects were found for this account.', 'gathercontent-importer' ) );
		}

		$all_projects = array();
		foreach ( $projects as $project ) {
			$all_projects[ $project->id ] = array(
				'id'   => $project->id,
				'name' => $project->name,
			);
		}

		$args = array(
			'posts_per_page' => 500,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		);

		if ( ! empty( $mapping_ids ) ) {
			$args['post__in'] = $mapping_ids;
		}

		$gotten = $this->get_by_account_id( $account_id, $args );


		$objects = array();

		if ( $gotten->have_posts() ) {
			$objects = $this->get_objects( $gotten, '_gc_project', $all_projects );
		}

		return $objects;
	}

	public function get_accounts_with_mappings() {
		$accounts = $this->api->get_accounts();
		if ( is_wp_error( $accounts ) ) {
			return $accounts;
		}

		if ( empty( $accounts ) ) {
			new WP_Error( 'gc_no_accounts', esc_html__( 'No accounts were found.', 'gathercontent-importer' ) );
		}

		$all_accounts = array();
		foreach ( $accounts as $key => $account ) {
			$all_accounts[ $account->slug ] = (array) $account;
		}

		$gotten = $this->get_mappings( array(
			'posts_per_page' => 500,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		) );

		$objects = array();

		if ( $gotten->have_posts() ) {
			$objects = $this->get_objects( $gotten, '_gc_account', $all_accounts );
		}

		return $objects;
	}

	protected function get_objects( $gotten, $meta_key, $all ) {
		$objects = array();
		foreach ( $gotten->posts as $post_id ) {

			$object_id = get_post_meta( $post_id, $meta_key, 1 );

			if ( ! $object_id || ! isset( $all[ $object_id ] ) ) {
				continue;
			}

			if ( ! isset( $objects[ $object_id ] ) ) {
				$objects[ $object_id ] = $all[ $object_id ] + array( 'mappings' => array( $post_id ) );
			} else {
				$objects[ $object_id ]['mappings'][] = $post_id;
			}

		}
		return $objects;
	}

	public static function get_mapping_post_types() {
		$all_types = get_option( 'gc_post_types', array() );
		return is_array( $all_types ) ? $all_types : array();
	}

	public static function update_mapping_post_types( $all_types = false ) {
		if ( ! is_array( $all_types ) || empty( $all_types ) ) {
			return delete_option( 'gc_post_types' );
		}

		return update_option( 'gc_post_types', $all_types );
	}

	public function get_mapping_template( $post_id ) {
		$mapping = Mapping_Post::get( $post_id );
		return $mapping ? $mapping->get_template() : false;
	}

	public function get_mapping_project( $post_id ) {
		$mapping = Mapping_Post::get( $post_id );
		return $mapping ? $mapping->get_project() : false;
	}

	public function get_mapping_account_id( $post_id ) {
		$mapping = Mapping_Post::get( $post_id );
		if ( ! $mapping ) {
			return false;
		}

		if ( $account_id = $mapping->get_account_id() ) {
			return $account_id;
		}

		$account_slug = $this->get_mapping_account_slug( $post_id );
		if ( ! $account_slug ) {
			return $account_id;
		}
		$accounts = $this->api->get_accounts();

		$all_accounts = array();
		foreach ( $accounts as $key => $account ) {
			if ( $account_slug === $account->slug ) {
				$account_id = $account->id;
			}
		}

		if ( $account_id ) {
			$mapping->update_meta( '_gc_account_id', $account_id );
		}

		return $account_id;
	}

	public function get_mapping_account_slug( $post_id ) {
		$mapping = Mapping_Post::get( $post_id );
		return $mapping ? $mapping->get_account_slug() : false;
	}

	public function get_items_to_pull( $post_id ) {
		$mapping = Mapping_Post::get( $post_id );
		return $mapping ? $mapping->get_items_to_pull() : false;
	}

	public function update_items_to_pull( $post_id, $items = array() ) {
		$mapping = Mapping_Post::get( $post_id );
		return $mapping ? $mapping->update_items_to_pull( $items ) : false;
	}

	public function get_pull_percent( $post_id ) {
		$mapping = Mapping_Post::get( $post_id );
		return $mapping ? $mapping->get_pull_percent() : 0;
	}

	public function get_mapping_object( $post ) {
		return Mapping_Post::get( $post );
	}

	public function get_mapping_data( $post ) {
		return Mapping_Post::get( $post )->data();
	}

	public function is_mapping_post( $post ) {
		try {
			return Mapping_Post::get_post( $post );
		} catch( \Exception $e ) {
			return false;
		}
	}

}
