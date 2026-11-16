<?php
/**
 * Manages events import for The Events Calendar.
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
 * Events_Calendar_Destination_Event class.
 */
class Events_Calendar_Destination_Event extends Abstract_Destination {
	use Format_Data_Destination;

	/**
	 * Destination slug.
	 *
	 * @var string
	 */
	protected $slug = 'events-calendar';


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
	 * Removes filters on `pre_get_posts` when processing `tribe_events`,
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
		if ( 'post' === $importer->get_module()->get_slug() && 'tribe_events' === $importer->get_post_type() ) {
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
		$keys[] = '_EventOrigin';
		$keys[] = '_air_wp_sync_hash';
		$keys[] = '_air_wp_sync_updated_at';
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
		$custom_format_strategies = array(
			'_EventStartDate'        => array( 'strategies' => array( 'format_to_datetime' ) ),
			'_EventEndDate'          => array( 'strategies' => array( 'format_to_datetime' ) ),
			'_EventOrganizerID'      => array( 'strategies' => array( 'format_event_organiser_id' ) ),
			'_EventTimezone'         => array( 'strategies' => array( 'format_to_timezone' ) ),
			'_EventHideFromUpcoming' => array(
				'strategies'  => array( 'format_to_boolean' ),
				'post_format' => function ( $value ) {
					return ! empty( $value ) ? 'yes' : false;
				},
			),
			'_EventVenueID'          => array( 'strategies' => array( 'format_to_integer' ) ),
			'_EventCurrencyPosition' => array(
				'strategies'  => array( 'format_to_string' ),
				'post_format' => function ( $value ) {
					return 'prefix' === $value ? 'prefix' : 'suffix';
				},
			),
			'_tribe_events_status'   => array(
				'strategies'  => array( 'format_to_string' ),
				'post_format' => function ( $value ) {
					$statuses = (array) apply_filters( 'tec_event_statuses', array(), '' );
					$slugs    = ! empty( $statuses ) ? wp_list_pluck( $statuses, 'value' ) : array( 'scheduled', 'postponed', 'canceled' );
					if ( ! in_array( $value, $slugs, true ) ) {
						$value = 'scheduled';
					}
					return $value;
				},
			),
		);

		$context = new Import_Context();
		$context
			->set_importer( $importer )
			->set_existing_object_id( $post_id );

		$meta_values = $this->format_data( array(), $context, $fields, $custom_format_strategies );

		// Add UTC counterpart for _EventStartDate & _EventEndDate then format _EventStartDate & _EventEndDate.
		$timezone_string = $meta_values['_EventTimezone'] ?? wp_timezone_string();
		foreach ( array( '_EventStartDate', '_EventEndDate' ) as $date_field ) {
			if ( isset( $meta_values[ $date_field ] ) && is_a( $meta_values[ $date_field ], '\DateTimeImmutable' ) ) {
				$meta_value = $meta_values[ $date_field ];

				$meta_value_utc               = $meta_value->setTimezone( new \DateTimeZone( 'UTC' ) );
				$utc_meta_key                 = '_EventStartDate' === $date_field ? '_EventStartDateUTC' : '_EventEndDateUTC';
				$meta_values[ $utc_meta_key ] = $meta_value_utc->format( 'Y-m-d H:i:s' );

				$meta_value                 = $meta_value->setTimezone( new \DateTimeZone( $timezone_string ) );
				$meta_values[ $date_field ] = $meta_value->format( 'Y-m-d H:i:s' );
			}
		}

		// Trigger update in the events custom database.
		if ( 'post' === $importer->get_module()->get_slug() && 'tribe_event' === $importer->get_post_type() ) {
			// Update timezone.
			$meta_values['_EventTimezone'] = $timezone_string;
			$meta_values['_EventOrigin']   = 'air_wp_sync';
		}

		foreach ( $meta_values as $meta_key => $meta_value ) {
			if ( '_EventOrganizerID' === $meta_key ) {
				delete_post_meta( $post_id, '_EventOrganizerID' );
				foreach ( $meta_value as $id ) {
					add_post_meta( $post_id, '_EventOrganizerID', $id, false );
				}
			} else {
				update_post_meta( $post_id, $meta_key, $meta_value );
			}
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
		if ( is_post_type_viewable( $post_type ) && 'tribe_events' === $post_type ) {
			$destination_features = array(
				'_EventOrigin',
				'_EventStartDate',
				'_EventStartDateUTC',
				'_tribe_events_status',
				'_tribe_events_status_reason', // Not used directly.
				'_EventEndDate',
				'_EventEndDateUTC',
				'_EventAllDay',
				'_EventShowMapLink',
				'_EventShowMap',
				'_EventVenueID',
				'_EventOrganizerID',
				'_EventDuration', // Not used directly.
				'_EventCurrencySymbol',
				'_EventCurrencyCode',
				'_EventCurrencyPosition',
				'_EventCost',
				'_EventURL',
				'_EventTimezone',
				'_EventTimezoneAbbr', // Not used directly.
				'_EventHideFromUpcoming',
				'_tribe_featured',
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
				'value'             => '_EventStartDate',
				'label'             => __( 'Event Start Date', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => $this->formatter->get_datetime_supported_sources(),
			),
			array(
				'value'             => '_EventEndDate',
				'label'             => __( 'Event End Date', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => $this->formatter->get_datetime_supported_sources(),
			),
			array(
				'value'             => '_EventAllDay',
				'label'             => __( 'Event All Day', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => $this->formatter->get_boolean_supported_sources(),
			),
			array(
				'value'             => '_EventTimezone',
				'label'             => __( 'Event Timezone', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => array_merge( $this->formatter->get_single_line_text_supported_sources(), $this->formatter->get_single_select_supported_sources() ),
			),
			array(
				'value'             => '_tribe_events_status',
				'label'             => __( 'Event Status', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => array_merge( $this->formatter->get_single_line_text_supported_sources(), $this->formatter->get_single_select_supported_sources() ),
			),
			array(
				'value'             => '_EventVenueID',
				'label'             => __( 'Event Venue ID', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => array_merge(
					$this->formatter->get_single_line_text_supported_sources(),
					$this->formatter->get_single_select_supported_sources(),
					$this->formatter->get_number_supported_sources()
				),
			),
			array(
				'value'             => '_EventShowMap',
				'label'             => __( 'Show Map', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => $this->formatter->get_boolean_supported_sources(),
			),
			array(
				'value'             => '_EventShowMapLink',
				'label'             => __( 'Show map link', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => $this->formatter->get_boolean_supported_sources(),
			),
			array(
				'value'             => '_EventOrganizerID',
				'label'             => __( 'Event Organizer ID', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => array_merge(
					$this->formatter->get_single_line_text_supported_sources(),
					$this->formatter->get_single_select_supported_sources(),
					$this->formatter->get_number_supported_sources(),
					$this->formatter->get_multiple_select_supported_sources()
				),
			),
			array(
				'value'             => '_EventURL',
				'label'             => __( 'Event URL', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => array_merge(
					$this->formatter->get_single_line_text_supported_sources(),
					$this->formatter->get_url_supported_sources()
				),
			),
			array(
				'value'             => '_EventCurrencySymbol',
				'label'             => __( 'Currency Symbol', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => array_merge( $this->formatter->get_single_line_text_supported_sources(), $this->formatter->get_single_select_supported_sources() ),
			),
			array(
				'value'             => '_EventCurrencyPosition',
				'label'             => __( 'Currency Position', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => array_merge( $this->formatter->get_single_line_text_supported_sources(), $this->formatter->get_single_select_supported_sources() ),
			),
			array(
				'value'             => '_EventCurrencyCode',
				'label'             => __( 'Currency Code', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => array_merge( $this->formatter->get_single_line_text_supported_sources(), $this->formatter->get_single_select_supported_sources() ),
			),
			array(
				'value'             => '_EventCost',
				'label'             => __( 'Event cost', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => array_merge(
					$this->formatter->get_single_line_text_supported_sources(),
					$this->formatter->get_number_supported_sources(),
					$this->formatter->get_currency_supported_sources()
				),
			),
			array(
				'value'             => '_tribe_featured',
				'label'             => __( 'Featured Event', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => $this->formatter->get_boolean_supported_sources(),
			),
			array(
				'value'             => '_EventHideFromUpcoming',
				'label'             => __( 'Hide From Event Listings', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => array_merge(
					$this->formatter->get_boolean_supported_sources(),
					$this->formatter->get_single_line_text_supported_sources(),
					$this->formatter->get_single_select_supported_sources()
				),
			),
		);
	}
}
