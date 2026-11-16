<?php
/**
 * Manages organizers import for The Events Calendar.
 *
 * @package wpconnect/x-wp-sync-pro-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Pro_Core\Destinations\Events_Calendar;

use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Destinations\Abstract_Destination;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Destinations\Format_Data_Destination;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Formatters\Abstract_Formatter;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Importers\Abstract_Importer;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Models\Import_Context;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Util\Helper;

/**
 * Events_Calendar_Destination_Organizer class.
 */
class Events_Calendar_Destination_Organizer extends Abstract_Destination {
	use Format_Data_Destination;

	/**
	 * Destination slug.
	 *
	 * @var string
	 */
	protected $slug = 'events-calendar-organizer';


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

		add_filter( 'tec_events_custom_tables_v1_tracked_meta_keys', array( $this, 'register_tracked_meta_keys' ), 10, 2 );
		add_action( 'airwpsync_process_records', array( $this, 'remove_pre_get_posts_filters' ), 5, 3 );
		add_action( 'airwpsync/import_record_after', array( $this, 'add_metas' ), 10, 4 );
		add_filter( 'airwpsync/features_by_post_type', array( $this, 'add_features_by_post_type' ), 10, 2 );
	}

	/**
	 * Removes filters on `pre_get_posts` when processing `tribe_organizer`,
	 * as The Events Calendar adds filters that prevent our plugin from finding existing posts.
	 *
	 * @param int    $importer_id Importer post id.
	 * @param string $run_id Importer run id.
	 * @param string $item_id Records chunk id.
	 */
	public function remove_pre_get_posts_filters( $importer_id, $run_id, $item_id ) {
		// Get importer instance from id.
		$importer = Helper::get_importer_by_id( $importer_id );
		if ( ! $importer ) {
			return;
		}
		// Remove all filters on `pre_get_posts`.
		if ( 'post' === $importer->get_module()->get_slug() && 'tribe_organizer' === $importer->get_post_type() ) {
			remove_all_filters( 'pre_get_posts' );
		}
	}

	/**
	 * Registers additional meta keys to track for The Events Calendar to trigger custom table updates
	 *
	 * @param array    $keys The list of tracked meta keys.
	 * @param int|null $id  The ID of the object (could not be an Event post!)
	 *                      the filters are being applied for, or `null` if the tracked keys
	 *                      should not be specific to an Event.
	 * @return  array $keys Additional keys to track.
	 */
	public function register_tracked_meta_keys( $keys, $id ) {
		$keys[] = '_OrganizerOrigin';
		$keys[] = '_x_wp_sync_core_hash';
		$keys[] = '_x_wp_sync_core_updated_at';
		return $keys;
	}

	/**
	 * Handle post meta importing.
	 *
	 * @param Abstract_Importer $importer  Importer.
	 * @param array             $fields    Fields.
	 * @param \stdClass         $record    The remote object.
	 * @param int               $post_id   The post id.
	 */
	public function add_metas( $importer, $fields, $record, $post_id ) {
		$context = new Import_Context();
		$context
			->set_importer( $importer )
			->set_existing_object_id( $post_id );

		$meta_values = $this->format_data( array(), $context, $fields, array() );

		// Trigger update in the custom database.
		if ( 'post' === $importer->get_module()->get_slug() && 'tribe_venue' === $importer->get_post_type() ) {
			$meta_values['_OrganizerOrigin'] = 'air_wp_sync';
		}

		foreach ( $meta_values as $meta_key => $meta_value ) {
			update_post_meta( $post_id, $meta_key, $meta_value );
		}
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_group() {
		return array(
			'label' => __( 'The Events Calendar', 'air-wp-sync-pro-plus' ),
			'slug'  => $this->slug,
		);
	}

	/**
	 * Add field features for each post types
	 *
	 * @param array  $features Features list.
	 * @param string $post_type Post type.
	 *
	 * @return string[]
	 */
	public function add_features_by_post_type( $features, $post_type ) {
		$destination_features = array();
		if ( 'tribe_organizer' === $post_type ) {
			$destination_features = array(
				'_OrganizerOrigin',
				'_OrganizerOrganizerID',
				'_OrganizerPhone',
				'_OrganizerWebsite',
				'_OrganizerEmail',
			);
		}
		$features[ $this->slug ] = $destination_features;
		return $features;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_mapping_fields() {
		return array(
			array(
				'value'             => '_OrganizerPhone',
				'label'             => __( 'Organizer Phone', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => array_merge(
					$this->formatter->get_single_line_text_supported_sources(),
					$this->formatter->get_single_select_supported_sources(),
					$this->formatter->get_phone_number_supported_sources()
				),
			),
			array(
				'value'             => '_OrganizerWebsite',
				'label'             => __( 'Organizer Website', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => array_merge(
					$this->formatter->get_single_line_text_supported_sources(),
					$this->formatter->get_single_select_supported_sources(),
					$this->formatter->get_url_supported_sources()
				),
			),
			array(
				'value'             => '_OrganizerEmail',
				'label'             => __( 'Organizer Email', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => array_merge(
					$this->formatter->get_single_line_text_supported_sources(),
					$this->formatter->get_single_select_supported_sources(),
					$this->formatter->get_email_supported_sources()
				),
			),
		);
	}
}
