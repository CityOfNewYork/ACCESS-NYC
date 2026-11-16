<?php
/**
 * Post Destination.
 *
 * @package wpconnect/x-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Modules\Post\Destinations;

use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Destinations\Abstract_Destination;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Models\Import_Context;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Modules\Post\Post_Importer;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Destinations\Format_Data_Destination;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Formatters\Abstract_Formatter;

/**
 * Class Post_Destination.
 */
class Post_Destination extends Abstract_Destination {
	use Format_Data_Destination;

		/**
		 * Destination slug.
		 *
		 * @var string
		 */
	protected $slug = 'post';

	/**
	 * Module slug.
	 *
	 * @var string
	 */
	protected $module = 'post';

	/**
	 * Formatter.
	 *
	 * @var Abstract_Formatter
	 */
	protected $formatter;

	/**
	 * Constructor
	 *
	 * @param Abstract_Formatter $formatter Formatter.
	 */
	public function __construct( $formatter ) {
		parent::__construct();

		$this->formatter = $formatter;

		add_filter( 'airwpsync/import_post_data', array( $this, 'add_to_post_data' ), 20, 3 );
		add_filter( 'airwpsync/features_by_post_type', array( $this, 'add_features_by_post_type' ), 10, 2 );
	}

	/**
	 * Handle post data importing
	 *
	 * @param array         $post_data Post data.
	 * @param Post_Importer $importer Post importer.
	 * @param array         $fields Fields.
	 *
	 * @return array
	 */
	public function add_to_post_data( $post_data, $importer, $fields ) {
		$custom_format_strategies = array(
			'post_title'  => array( 'strategies' => array( 'format_to_string', 'format_to_plain_text' ) ),
			'post_status' => array( 'strategies' => array( 'format_to_string', 'format_label_to_post_status', 'format_to_post_status' ) ),
			'post_date'   => array( 'strategies' => array( 'format_to_datetime', 'format_datetime_to_iso8601' ) ),
			'post_parent' => array( 'strategies' => array( 'format_to_integer' ) ),
			'post_author' => array( 'strategies' => array( 'format_email_to_user_id' ) ),
		);

		$context = new Import_Context();
		$context
			->set_importer( $importer );

		$post_data = $this->format_data( $post_data, $context, $fields, $custom_format_strategies );

		if ( empty( $post_data['post_parent'] ) ) {
			$post_data['post_parent'] = 0;
		}

		return $post_data;
	}

	/**
	 * Add field features for each post types
	 *
	 * @param array  $features Features.
	 * @param string $post_type Post type.
	 *
	 * @return array
	 */
	public function add_features_by_post_type( $features, $post_type ) {
		$destination_features = array(
			'post_name',
			'post_date',
			'post_status',
		);

		if ( is_post_type_hierarchical( $post_type ) ) {
			$destination_features[] = 'post_parent';
		}

		if ( post_type_supports( $post_type, 'title' ) ) {
			$destination_features[] = 'post_title';
		}

		if ( post_type_supports( $post_type, 'editor' ) ) {
			$destination_features[] = 'post_content';
		}

		if ( post_type_supports( $post_type, 'excerpt' ) ) {
			$destination_features[] = 'post_excerpt';
		}

		if ( post_type_supports( $post_type, 'author' ) ) {
			$destination_features[] = 'post_author';
		}

		$features[ $this->slug ] = $destination_features;
		return $features;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_group() {
		return array(
			'label' => __( 'Post', 'air-wp-sync-pro-plus' ),
			'slug'  => 'post',
		);
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_mapping_fields() {
		return array(
			array(
				'value'             => 'post_title',
				'label'             => __( 'Title', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => $this->formatter->get_string_supported_sources(),
			),
			array(
				'value'             => 'post_content',
				'label'             => __( 'Content', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => $this->formatter->get_string_supported_sources(),
			),
			array(
				'value'             => 'post_excerpt',
				'label'             => __( 'Excerpt', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => $this->formatter->get_string_supported_sources(),
			),
			array(
				'value'             => 'post_name',
				'label'             => __( 'Slug', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => $this->formatter->get_string_supported_sources(),
			),
			array(
				'value'             => 'post_author',
				'label'             => __( 'Author', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => $this->formatter->get_string_supported_sources(),
			),
			array(
				'value'             => 'post_status',
				'label'             => __( 'Status', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => $this->formatter->get_string_supported_sources(),
			),
			array(
				'value'             => 'post_date',
				'label'             => __( 'Publication Date', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => $this->formatter->get_datetime_supported_sources(),
			),
			array(
				'value'             => 'post_parent',
				'label'             => __( 'Post parent (ID)', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => $this->formatter->get_number_supported_sources(),
			),
		);
	}
}
