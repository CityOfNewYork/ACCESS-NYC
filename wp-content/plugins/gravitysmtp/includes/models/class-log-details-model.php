<?php

namespace Gravity_Forms\Gravity_SMTP\Models;

use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Connectors\Endpoints\Save_Plugin_Settings_Endpoint;
use Gravity_Forms\Gravity_SMTP\Data_Store\Plugin_Opts_Data_Store;
use Gravity_Forms\Gravity_SMTP\Enums\Status_Enum;
use Gravity_Forms\Gravity_SMTP\Feature_Flags\Feature_Flag_Manager;
use Gravity_Forms\Gravity_SMTP\Gravity_SMTP;
use Gravity_Forms\Gravity_SMTP\Tracking\Tracking_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Utils\Booliesh;
use Gravity_Forms\Gravity_SMTP\Utils\Recipient_Collection;
use Gravity_Forms\Gravity_Tools\Utils\Utils_Service_Provider;

class Log_Details_Model {

	protected $table_name        = 'gravitysmtp_event_logs';
	protected $events_table_name = 'gravitysmtp_events';

	/**
	 * @var Plugin_Opts_Data_Store
	 */
	protected $opts;

	public function __construct( $plugin_opts ) {
		$this->opts = $plugin_opts;
	}

	protected function get_table_name() {
		global $wpdb;

		return $wpdb->prefix . $this->table_name;
	}

	protected function get_events_table_name() {
		global $wpdb;

		return $wpdb->prefix . $this->events_table_name;
	}

	public function create( $event_id, $action_name, $log_value ) {
		if ( ! $this->is_logging_enabled() ) {
			return 0;
		}

		global $wpdb;

		$log_value = is_string( $log_value ) ? $log_value : json_encode( $log_value );

		$wpdb->insert(
			$this->get_table_name(),
			array(
				'event_id'     => $event_id,
				'action_name'  => $action_name,
				'log_value'    => $log_value,
				'date_created' => current_time( 'mysql', true ),
				'date_updated' => current_time( 'mysql', true ),
			)
		);

		return $wpdb->insert_id;
	}

	public function all() {
		global $wpdb;

		$sql = 'SELECT * FROM %1$s;';

		$results = $wpdb->get_results( $wpdb->prepare( $sql, $this->get_table_name() ), ARRAY_A );

		return $results;
	}

	public function by_id( $id ) {
		global $wpdb;

		$table_name = $this->get_table_name();
		$sql        = "SELECT * FROM $table_name WHERE event_id = %d";
		$results    = $wpdb->get_results( $wpdb->prepare( $sql, $id ), ARRAY_A );

		return $results;
	}

	public function delete( $id ) {
		global $wpdb;

		$wpdb->delete( $this->get_table_name(), array( 'id' => $id ) );
	}

	public function delete_by_event_id( $id ) {
		global $wpdb;

		$wpdb->delete( $this->get_table_name(), array( 'event_id' => $id ) );
	}

	public function delete_all() {
		global $wpdb;
		$table_name = $this->get_table_name();

		$wpdb->query( "TRUNCATE TABLE $table_name" );
	}

	public function get_next_id( $current_id ) {
		global $wpdb;

		$table_name = $this->get_events_table_name();
		$sql        = "SELECT id FROM $table_name WHERE id > %d ORDER BY id ASC LIMIT 1";
		$next_id    = $wpdb->get_var( $wpdb->prepare( $sql, $current_id ) );

		return $next_id !== null ? intval( $next_id ) : null;
	}

	public function get_prev_id( $current_id ) {
		global $wpdb;

		$table_name = $this->get_events_table_name();
		$sql        = "SELECT id FROM $table_name WHERE id < %d ORDER BY id DESC LIMIT 1";
		$prev_id    = $wpdb->get_var( $wpdb->prepare( $sql, $current_id ) );

		return $prev_id !== null ? intval( $prev_id ) : null;
	}

	private function is_logging_enabled() {
		$logging_enabled = $this->opts->get( Save_Plugin_Settings_Endpoint::PARAM_EVENT_LOG_ENABLED, 'config', 'true' );

		if ( empty( $logging_enabled ) ) {
			$logging_enabled = true;
		} else {
			$logging_enabled = $logging_enabled !== 'false';
		}

		return $logging_enabled;
	}

	public function full_details( $id ) {
		$container = Gravity_SMTP::container();

		$plugin_data_store = $container->get( Connector_Service_Provider::DATA_STORE_ROUTER );

		$open_tracking_enabled = $plugin_data_store->get_plugin_setting( Tracking_Service_Provider::SETTING_OPEN_TRACKING, 'false' );
		$open_tracking_enabled = Booliesh::get( $open_tracking_enabled );

		$emails   = $container->get( Connector_Service_Provider::EVENT_MODEL );
		$data     = $this->by_id( $id );
		$email    = $emails->find( array( array( 'id', '=', $id ) ) );
		$log_rows = array();

		if ( empty( $email[0] ) ) {
			return array();
		}

		$row     = $email[0];
		$extra   = strpos( $row['extra'], '{' ) === 0 ? json_decode( $row['extra'], true ) : unserialize( $row['extra'] );

		foreach ( $data as $val ) {
			$log_rows[] = $val['log_value'];
		}

		$map    = $container->get( Connector_Service_Provider::NAME_MAP );
		$parser = $container->get( Utils_Service_Provider::RECIPIENT_PARSER );

		$params = isset( $extra['params'] ) ? $extra['params'] : array();
		$to     = $parser->parse( $extra['to'] );
		$to     = $to->as_string( true );

		$clean_from_email = $this->extract_email( $extra['from'] );

		$cc = array();
		$bcc = array();

		if ( ! empty( $extra['headers']['cc'] ) ) {
			// @var Recipient_Collection $cc_values
			$cc_values = $extra['headers']['cc'];

			foreach( $cc_values->as_array() as $cc_value ) {
				$cc[] = $cc_value['email']; 
			}
		}

		if ( ! empty( $extra['headers']['bcc'] ) ) {
			$bcc_values = $extra['headers']['bcc'];

			foreach( $bcc_values->as_array() as $bcc_value ) {
				$bcc[] = $bcc_value['email'];
			}
		}

		$details = array(
			'date'                  => $this->convert_dates_to_timezone( $row['date_updated'] ),
			'from'                  => $extra['from'],
			'fromHash'              => ! empty( $clean_from_email ) ? hash( 'sha256', strtolower( trim( $clean_from_email ) ) ) : '',
			'to'                    => $to,
			'subject'               => $row['subject'],
			'is_html'               => $row['message'] !== strip_tags( $row['message'] ),
			'technical_information' => array(
				'log'     => $log_rows,
				'headers' => $params,
			),
			'status'                => array(
				'label'  => ucfirst( $row['status'] ),
				'status' => Status_Enum::indicator( $row['status'] ),
				'hasDot' => false,
			),
			'service'               => empty( $map[ $row['service'] ] ) ? $row['service'] : $map[ $row['service'] ],
			'has_attachment'        => isset( $extra['attachments'] ) && is_array( $extra['attachments'] ) ? count( $extra['attachments'] ) : 0,
			'attachments'           => isset( $extra['attachments'] ) ? $this->get_attachments( $extra['attachments'] ) : array(),
			'log_id'                => $row['id'],
			'next_id'               => $this->get_next_id( $row['id'] ),
			'prev_id'               => $this->get_prev_id( $row['id'] ),
			'clicked'               => isset( $row['clicked'] ) ? $row['clicked'] : __( 'No', 'gravitysmtp' ),
			'source'                => isset( $row['source'] ) ? $row['source'] : __( 'N/A', 'gravitysmtp' ),
			'can_resend'            => $row['can_resend'],
		);

		if ( Feature_Flag_Manager::is_enabled( 'email_open_tracking' ) && $open_tracking_enabled ) {
			$details[ 'opened' ] = isset( $row['opened'] ) ? $row['opened'] : __( 'No', 'gravitysmtp' );
		}

		if ( ! empty( $cc ) ) {
			$details['cc'] = implode( ', ', $cc );
		}

		if ( ! empty( $bcc ) ) {
			$details['bcc'] = implode( ', ', $bcc );
		}

		return $details;
	}

	private function extract_email( $string ) {
		$regex = '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}/';
		preg_match( $regex, $string, $matches );

		return $matches ? $matches[0] : null;
	}

	private function convert_dates_to_timezone( $date ) {
		$gmt_time   = new \DateTimeZone( 'UTC' );
		$local_time = new \DateTimeZone( wp_timezone_string() );
		$datetime   = new \DateTime( $date, $gmt_time );
		$datetime->setTimezone( $local_time );

		return $datetime->format( 'F d, Y \a\t h:ia' );
	}

	private function get_attachments( $attachments ) {
		if ( ! is_array( $attachments ) ) {
			return array();
		}
		$uploads_dir = wp_upload_dir();
		$attachments_arr = array();
    
		$base_dir = $uploads_dir['basedir'];
		$base_url = $uploads_dir['baseurl'];
		foreach ( $attachments as $custom_name => $attachment ) {
			$attachments_arr[] = array(
				'file_name'      => esc_html( is_numeric( $custom_name ) ? basename( $attachment ) : $custom_name ),
				'file_path'      => esc_html( str_replace( $base_dir, $base_url, $attachment ) ),
				'file_extension' => esc_html( pathinfo( $attachment, PATHINFO_EXTENSION ) ),
			);
		}

		return $attachments_arr;
	}

}
