<?php

namespace Gravity_Forms\Gravity_SMTP\Apps\Endpoints;

use Gravity_Forms\Gravity_SMTP\Apps\Config\Dashboard_Config;
use Gravity_Forms\Gravity_Tools\Endpoints\Endpoint;
use Gravity_Forms\Gravity_SMTP\Data_Store\Plugin_Opts_Data_Store;

class Get_Dashboard_Data_Endpoint extends Endpoint {

	const ACTION_NAME = 'get_dashboard_data';

	const PARAM_START_DATE = 'start_date';
	const PARAM_END_DATE   = 'end_date';
	const PARAM_DATE_RANGE = 'date_range';

	/**
	 * @var Dashboard_Config;
	 */
	protected $dasbhoard_config;

	public function __construct( $dashboard_config ) {
		$this->dasbhoard_config = $dashboard_config;
	}

	protected function get_nonce_name() {
		return self::ACTION_NAME;
	}

	public function handle() {
		if ( ! $this->validate() ) {
			wp_send_json_error( __( 'Missing required parameters.', 'gravitysmtp' ), 400 );
		}

		$date_range = FILTER_INPUT( INPUT_POST, self::PARAM_DATE_RANGE );
		$start      = FILTER_INPUT( INPUT_POST, self::PARAM_START_DATE );
		$end        = FILTER_INPUT( INPUT_POST, self::PARAM_END_DATE );

		if ( ! empty( $date_range ) && ! empty( $start ) ) {
			wp_send_json_error( __( 'Send either a date range value or start/end dates; do not send both.', 'gravitysmtp' ), 400 );
		}

		if ( ! empty( $date_range ) ) {
			$data = $this->get_data_for_range( $date_range );
			wp_send_json_success( $data );
		}

		if ( empty( $start ) || empty( $end ) ) {
			wp_send_json_error( __( 'Must provide both start and end dates.', 'gravitysmtp' ), 400 );
		}

		$period = 0;

		$start = get_gmt_from_date( $start );
		$end   = get_gmt_from_date( $end );

		$data = $this->dasbhoard_config->ajax_data( $start, $end, $period );

		wp_send_json_success( $data );
	}

	private function get_data_for_range( $date_range ) {
		$mod_string = sprintf( "-%d days", ( $date_range - 1 ) );
		$start      = get_gmt_from_date( gmdate( 'Y-m-d 00:00:00', strtotime( $mod_string ) ) );
		$end        = get_gmt_from_date( gmdate( 'Y-m-d 23:59:59' ) );
		$period     = $date_range;

		return $this->dasbhoard_config->ajax_data( $start, $end, $period );
	}
}
