<?php
/**
 * Post Module.
 *
 * @package wpconnect/x-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Modules\Post;

use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Admin\Metaboxes\Metabox_Imported_Content_Connection_Info;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Importers\Abstract_Importer;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Models\Imported_Content_Importer_Metas;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Models\Plugin_Context;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Models\Plugin_Strings;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Modules\Abstract_Module;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Modules\Post\Destinations\Post_Destination;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Modules\Post\Destinations\Post_Meta_Destination;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Modules\Post\Destinations\Taxonomy_Destination;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Modules\Post\Formatters\Format_Label_To_Post_Status_Strategy;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Modules\Post\Formatters\Format_Records_Id_To_Posts_Id_Strategy;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Modules\Post\Formatters\Format_To_Post_Status_Strategy;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Util\Helper;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Util\Services;

/**
 * Class Post_Module.
 */
class Post_Module extends Abstract_Module {
	/**
	 * Module slug
	 *
	 * @var string
	 */
	protected $slug = 'post';

	/**
	 * Module name
	 *
	 * @var string
	 */
	protected $name = 'Post';

	/**
	 * Plugin’s strings.
	 *
	 * @var Plugin_Strings
	 */
	private $plugin_strings;

	/**
	 * Constructor
	 *
	 * @param Plugin_Context $plugin_context Plugin’s context.
	 * @param Plugin_Strings $plugin_strings Plugin’s specific strings.
	 */
	public function __construct( $plugin_context, $plugin_strings ) {
		$this->name = __( 'Post', 'air-wp-sync-pro-plus' );
		parent::__construct( $plugin_context );

		$this->plugin_strings = $plugin_strings;

		add_action( 'admin_enqueue_scripts', array( $this, 'register_styles_scripts' ) );
		add_filter( 'airwpsync/get_l10n_strings', array( $this, 'add_l10n_strings' ) );
		add_action( 'airwpsync/register_destination', array( $this, 'register_destinations' ) );
		add_action( 'airwpsync/connections_list_type_column', array( $this, 'connections_list_type_column' ) );

		Services::get_instance()->get( 'formatter' )
				->register_strategy( new Format_Label_To_Post_Status_Strategy() )
				->register_strategy( new Format_To_Post_Status_Strategy() )
				->register_strategy( new Format_Records_Id_To_Posts_Id_Strategy() );
	}

	/**
	 * Displays "Importing as ..." post type in admin connection list table.
	 *
	 * @param Abstract_Importer $importer Importer.
	 *
	 * @return void
	 */
	public function connections_list_type_column( $importer ) {
		if ( $importer->get_module() === $this ) {
			$post_type        = $importer->get_post_type();
			$post_type_object = get_post_type_object( $post_type );
			if ( $post_type_object ) {
				echo '<br>';
				echo esc_html__( 'Importing as: ', 'air-wp-sync-pro-plus' );
				$menu_icon = $post_type_object->menu_icon ? $post_type_object->menu_icon : 'dashicons-admin-post';
				echo '<span class="dashicons ' . esc_attr( $menu_icon ) . '"></span> ';
				echo esc_html( $post_type_object->labels->name );
			}
		}
	}

	/**
	 * Register admin styles and scripts
	 */
	public function register_styles_scripts() {
		$screen = get_current_screen();
		if ( is_object( $screen ) && 'airwpsync-connection' === $screen->id ) {
			wp_enqueue_script( 'air-wp-sync-pro-plus-post-hooks', plugins_url( 'vendor_prefixed/wpconnect/x-wp-sync-core/src/Modules/Post/assets/js/hooks.js', $this->plugin_context->get_plugin_file() ), array( 'air-wp-sync-pro-plus-admin-connection' ), $this->plugin_context->get_plugin_version(), false );
		}
	}

	/**
	 * Add module l10n strings
	 *
	 * @param array $l10n_strings Localization strings.
	 *
	 * @return array
	 */
	public function add_l10n_strings( $l10n_strings ) {
		return array_merge(
			$l10n_strings,
			array(
				'deleteActionConfirmation'   => __( 'You have a Custom Post Type declared using this connection. Are you sure to delete it?', 'air-wp-sync-pro-plus' ),
				'slugErrorMessage'           => __( 'Only lowercase alphanumeric characters, dashes, and underscores are allowed.', 'air-wp-sync-pro-plus' ),
				'allowedCptSlugErrorMessage' => __( 'This slug is already in use, please choose another.', 'air-wp-sync-pro-plus' ),
				'slugLengthErrorMessage'     => __( 'This slug exceeds the 20-character limit. Please use a shorter one.', 'air-wp-sync-pro-plus' ),
			)
		);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param \WP_Post $post Post object holding the importer config.
	 *
	 * @return void
	 */
	public function render_settings( $post ) {
		$importer     = Helper::get_importer_by_id( $post->ID );
		$post_types   = array_filter(
			Post_Helpers::get_post_types(),
			function ( $post_type ) use ( $importer ) {
				return ! $importer || ( $importer->config()->get( 'post_type' ) !== 'custom' || $importer->config()->get( 'post_type_name' ) !== $post_type['value'] );
			}
		);
		$post_stati   = Post_Helpers::get_post_stati();
		$post_authors = Post_Helpers::get_post_authors();
		$view         = include_once __DIR__ . '/views/settings.php';
		$view( $post_types, $post_authors, $post_stati, $this->plugin_strings );
	}


	/**
	 * {@inheritDoc}
	 *
	 * @param \WP_Post $post Post object holding the importer config.
	 *
	 * @return Abstract_Importer
	 */
	public function get_importer_instance( $post ) {
		$services = Services::get_instance();
		return new Post_Importer( $post, $this, $services->get( 'importer_remote_data_provider_factory' ), $services->get( 'logger' ) );
	}

	/**
	 * Register destinations
	 */
	public function register_destinations() {
		$formatter = Services::get_instance()->get( 'formatter' );
		new Post_Destination( $formatter );
		new Post_Meta_Destination( $formatter );
		new Taxonomy_Destination( $formatter );
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_mapping_options() {
		return apply_filters( 'airwpsync/get_wp_fields', array(), $this->slug );
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_extra_config() {
		return array(
			'reservedCptSlugs'   => $this->get_reserved_cpt_slugs(),
			'featuresByPostType' => $this->get_features_by_post_type(),
		);
	}

	/**
	 * Get reserved CPT slugs
	 *
	 * @return array
	 */
	protected function get_reserved_cpt_slugs() {
		return array_values( get_post_types() );
	}

	/**
	 * Get available features by post type
	 *
	 * @return array
	 */
	protected function get_features_by_post_type() {
		$features = array();
		foreach ( Post_Helpers::get_post_types() as $post_type ) {
			/**
			 * Filters features by post type.
			 *
			 * @param array $features Features.
			 * @param string $post_type Post type.
			 */
			$features[ $post_type['value'] ] = apply_filters(
				'airwpsync/features_by_post_type',
				array(),
				$post_type['value']
			);
		}
		// Default features for custom post type.
		$features['custom'] = array(
			'post' => array(
				'post_name',
				'post_date',
				'post_title',
				'post_excerpt',
				'post_content',
				'post_author',
				'post_status',
			),
			'meta' => array(
				'_thumbnail_id',
				'custom_field',
			),
		);
		return $features;
	}

	/**
	 * Register box to display connection info on imported content.
	 *
	 * @return void
	 */
	public function register_imported_content_connection_info_box() {
		$screen_ids_registered = array();
		foreach ( Helper::get_importers() as $importer ) {
			if ( $importer->get_module() === $this ) {
				$post_type = $importer->get_post_type();
				if ( in_array( $post_type, $screen_ids_registered, true ) ) {
					continue;
				}

				new Metabox_Imported_Content_Connection_Info(
					$post_type,
					function ( $post ) {
						try {
							return new Imported_Content_Importer_Metas(
								get_post_meta( $post->ID, '_air_wp_sync_importer_id', true ),
								get_post_meta( $post->ID, '_air_wp_sync_record_id', true ),
								get_post_meta( $post->ID, '_air_wp_sync_updated_at', true ),
								function ( $post_id ) {
									return delete_post_meta( $post_id, '_air_wp_sync_importer_id' )
											&& delete_post_meta( $post_id, '_air_wp_sync_record_id' )
											&& delete_post_meta( $post_id, '_air_wp_sync_updated_at' );
								}
							);
						} catch ( \Throwable $exception ) {
							// Certainly not a content imported from a connection.
							return false;
						}
					}
				);

				$screen_ids_registered[] = $post_type;
			}
		}
	}
}
