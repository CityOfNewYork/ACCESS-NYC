<?php

namespace Gravity_Forms\Gravity_SMTP\Apps\Config;

use Gravity_Forms\Gravity_SMTP\Apps\App_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Apps\Endpoints\Get_Dashboard_Data_Endpoint;
use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Connectors\Endpoints\Save_Plugin_Settings_Endpoint;
use Gravity_Forms\Gravity_SMTP\Data_Store\Data_Store_Router;
use Gravity_Forms\Gravity_SMTP\Feature_Flags\Feature_Flag_Manager;
use Gravity_Forms\Gravity_SMTP\Gravity_SMTP;
use Gravity_Forms\Gravity_SMTP\Models\Event_Model;
use Gravity_Forms\Gravity_SMTP\Tracking\Tracking_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Utils\Booliesh;
use Gravity_Forms\Gravity_Tools\Config;
use Gravity_Forms\Gravity_Tools\Config_Data_Parser;
use Relay\Event;


class Dashboard_Config extends Config {

	const DEFAULT_DATE_RANGE_INTERVAL = 90;

	protected $script_to_localize = 'gravitysmtp_scripts_admin';
	protected $name               = 'gravitysmtp_admin_config';
	protected $overwrite          = false;

	private $start;
	private $end;
	private $period;
	private $retention_period;

	/**
	 * @var Event_Model
	 */
	private $model;

	public function __construct( Config_Data_Parser $parser ) {
		parent::__construct( $parser );
		/**
		 * @var Data_Store_Router $settings
		 */
		$settings               = Gravity_SMTP::$container->get( Connector_Service_Provider::DATA_STORE_ROUTER );
		$this->retention_period = $settings->get_plugin_setting( Save_Plugin_Settings_Endpoint::PARAM_EVENT_LOG_RETENTION, 30 );
		$this->model            = Gravity_SMTP::$container->get( Connector_Service_Provider::EVENT_MODEL );
	}

	public function should_enqueue() {
		$enabled = Feature_Flag_Manager::is_enabled( App_Service_Provider::FEATURE_FLAG_DASHBOARD );

		if ( ! $enabled ) {
			return false;
		}

		$page = filter_input( INPUT_GET, 'page' );

		if ( ! is_string( $page ) ) {
			return false;
		}

		$page = htmlspecialchars( $page );

		return $page === 'gravitysmtp-dashboard';
	}

	public function data() {
		$mod_string = sprintf( '-%d days', self::DEFAULT_DATE_RANGE_INTERVAL );
		// Default to Last 3 Months
		$this->start  = gmdate( 'Y-m-d 00:00:00', strtotime( $mod_string ) );

		// Get minimum start date based on data.
		$this->start  = $this->get_min_start_date( true );
		$this->end    = gmdate( 'Y-m-d 23:59:59', strtotime( "+1 day") );

		// Determine proper period to use.
		$this->period = $this->get_period_from_ranges();

		return array(
			'common' => array(
				'endpoints' => array(
					Get_Dashboard_Data_Endpoint::ACTION_NAME => array(
						'action' => array(
							'value'   => Get_Dashboard_Data_Endpoint::ACTION_NAME,
							'default' => 'mock_endpoint',
						),
						'nonce'  => array(
							'value'   => wp_create_nonce( Get_Dashboard_Data_Endpoint::ACTION_NAME ),
							'default' => 'nonce',
						),
					),
				)
			),
			'components' => array(
				'dashboard' => array(
					'i18n' => $this->i18n_values(),
					'data' => $this->data_values(),
				),
			)
		);
	}

	public function ajax_data( $start, $end, $period ) {
		$this->start  = $start;
		$this->end    = $end;

		if ( is_numeric( $period ) ) {
			switch( $period ) {
				case 0:
				default:
					$period = $this->get_period_from_ranges();
					break;
				case 1:
					$period = 'hour';
					break;
				case 7:
					$period = 'day';
					break;
				case 30:
				case 90:
				case 180:
					$period = 'day';
					break;
				case 365:
					$period = 'month';
					break;
			}
		}

		$this->period = $period;

		return $this->data_values();
	}

	protected function get_period_from_ranges() {
		$start = date_create( $this->start );
		$end   = date_create( $this->end );

		$interval = date_diff( $start, $end );
		$diff     = $interval->format( '%a' );

		if ( $diff <= 1 ) {
			return 'hour';
		}

		if ( $diff < 180 ) {
			return 'day';
		}

		return 'month';
	}

	protected function i18n_values() {
		return array(
			'totals'   => array(
				'headings' => array(
					'emails'      => __( 'Processed', 'gravitysmtp' ),
					'sent'        => __( 'Sent', 'gravitysmtp' ),
					'percentOpen' => __( 'Opened', 'gravitysmtp' ),
					'failed'      => __( 'Failed', 'gravitysmtp' ),
				),
			),
			'stats' => array(
				'heading'          => __( 'Email Overview', 'gravitystmp' ),
				'no_data_heading'  => __( 'This is where your email statistics will appear.', 'gravitysmtp' ),
				'no_data_message'  => __( 'No data for the selected date range.', 'gravitysmtp' ),
				'checkboxes'       => array(
					'sent'    => __( 'Sent', 'gravitysmtp' ),
					'failed'  => __( 'Failed', 'gravitysmtp' ),
				),
				'date_range_label' => __( 'Date Range', 'gravitysmtp' ),
				'calendar_label'   => __( 'Custom Date', 'gravitysmtp' ),
			),
			'rankings' => array(
				'headings' => array(
					'your_integrations' => __( 'Your Integrations', 'gravitysmtp' ),
					'sources'           => __( 'Top Sending Sources', 'gravitysmtp' ),
					'recipients'        => __( 'Top Email Recipients', 'gravitysmtp' ),
					'quick_links'       => __( 'Quick Links', 'gravitysmtp' ),
				),
				'email'    => __( '%1$s Email', 'gravitysmtp' ),
				'emails'   => __( '%1$s Emails', 'gravitysmtp' ),
				'tags'     => array(
					'primary'        => __( 'Primary', 'gravitysmtp' ),
					'backup'         => __( 'Backup', 'gravitysmtp' ),
					'connected'      => __( 'Connected', 'gravitysmtp' ),
					'configured'     => __( 'Configured', 'gravitysmtp' ),
					'not_configured' => __( 'Not Configured', 'gravitysmtp' ),
				),
			),
		);
	}

	protected function data_values() {
		$container         = Gravity_SMTP::container();
		$plugin_data_store = $container->get( Connector_Service_Provider::DATA_STORE_ROUTER );

		$totals                = $this->get_email_totals();
		$open_tracking_enabled = $plugin_data_store->get_plugin_setting( Tracking_Service_Provider::SETTING_OPEN_TRACKING, 'false' );
		$open_tracking_enabled = Booliesh::get( $open_tracking_enabled );

		return array(
			'totals'                => $totals,
			'chart'                 => $this->get_chart_data(),
			'open_tracking_enabled' => Feature_Flag_Manager::is_enabled( 'email_open_tracking' ) && $open_tracking_enabled,
			'date_ranges'           => array(
				'options'       => $this->get_date_options(),
				'initial_value' => $this->get_initial_range_value_from_data( true ),
				'min_start'     => $this->model->get_earliest_event_date(),
				'max_end'       => get_date_from_gmt( $this->end ),
			),
			'integrations_url'      => admin_url( 'admin.php?page=gravitysmtp-settings&tab=integrations&integration=%1$s' ),
			'source_icons_url'      => trailingslashit( GF_GRAVITY_SMTP_PLUGIN_URL ) . 'assets/images/plugin-icons/',
			'rankings'              => array(
				'your_integrations' => $this->get_your_integrations_info(),
				'sources'           => $this->get_top_sending_sources(),
				'recipients'        => $this->get_top_email_recipients(),
				'quick_links'       => $this->get_quick_links(),
			),
		);
	}

	protected function get_initial_range_value_from_data( $respect_default = false ) {
		$diff    = $this->get_max_interval_diff();
		$options = array( 1, 7, 30, 90, 180, 365 );
		$value   = 365;

		foreach ( $options as $option ) {
			if ( $diff <= $option ) {
				$value = $option;
				break;
			}
		}

		if ( $respect_default && $value > self::DEFAULT_DATE_RANGE_INTERVAL ) {
			return self::DEFAULT_DATE_RANGE_INTERVAL;
		}

		return $value;
	}

	protected function get_date_options() {
		$diff = $this->get_max_interval_diff();

		$options = array(
			array(
				'label' => __( 'Last Day', 'gravitysmtp' ),
				'value' => 1,
			),
			array(
				'label' => __( 'Last 7 Days', 'gravitysmtp' ),
				'value' => 7,
			),
			array(
				'label' => __( 'Last 30 Days', 'gravitysmtp' ),
				'value' => 30,
			),
			array(
				'label' => __( 'Last 90 Days', 'gravitysmtp' ),
				'value' => 90,
			),
			array(
				'label' => __( 'Last 180 Days', 'gravitysmtp' ),
				'value' => 180,
			),
			array(
				'label' => __( 'Last 365 Days', 'gravitysmtp' ),
				'value' => 365,
			),
		);

		if ( $diff <= 1 ) {
			$options = array(
				reset( $options ),
			);

			return $options;
		}

		$parsed_options = array();

		foreach ( $options as $key => $option ) {
			$value = $option['value'];
			$next  = isset( $options[ $key + 1 ] ) ? $options[ $key + 1 ]['value'] : - 1;

			if ( $diff >= $value ) {
				$parsed_options[] = $option;
			} else {
				continue;
			}

			if ( $diff < $next ) {
				$parsed_options[] = $options[ $key + 1 ];
			}
		}

		return array_values( $parsed_options );
	}

	private function get_max_interval_diff() {
		$earliest_date = date_create( $this->model->get_earliest_event_date() );
		$today         = date_create( date( 'Y:m:d 23:59:59' ) );
		$interval      = date_diff( $earliest_date, $today );

		return (int) $interval->format( '%a' );
	}

	protected function get_chart_data() {
		$data = $this->model->get_chart_data( $this->start, $this->end );

		list( $format ) = $this->get_date_format_and_interval();

		$sorted = array_reduce(
			$data,
			function( $carry, $item ) use ( $format ) {
				$key = get_date_from_gmt( $item['date_created'], $format );
				if ( ! array_key_exists( $key , $carry ) ) {
					$carry[ $key ] = array(
						'sent'   => 0,
						'failed' => 0,
					);
				}
				if ( $item['status'] === 'sent' ) {
					$carry[ $key ]['sent'] += 1;
				} elseif ( $item['status'] === 'failed' ) {
					$carry[ $key ]['failed'] += 1;
				}

				return $carry;
			},
			array()
		);

//		$sorted = array();
//
//		foreach( $data as $datum ) {
//			if ( ! array_key_exists( $datum['date_created'], $sorted ) ) {
//				$sorted[ $datum['date_created'] ] = array(
//					'sent'   => 0,
//					'failed' => 0,
//				);
//			}
//
//			if ( $datum['status'] !== 'sent' && $datum['status'] !== 'failed' ) {
//				continue;
//			}
//
//			$sorted[ $datum['date_created'] ][ $datum['status'] ] += $datum['total'];
//		}

		$sorted = $this->pad_with_empty_values( $sorted );

		$chart_data = array();

		foreach ( $sorted as $date => $values ) {

			$chart_data[] = array(
				'xAxisKey' => $date,
				'sent'     => $values['sent'],
				'failed'   => $values['failed'],
			);
		}

		return array(
			'config'     => array(
				'start'  => $this->get_min_start_date(),
				'end'    => get_date_from_gmt( $this->end ),
				'period' => $this->period
			),
			'values' => $chart_data,
			'datasets'   => array(
				array(
					'label'          => __( 'Sent', 'gravitysmtp' ),
					'dataKey'        => 'sent',
					'color'          => '#82ca9d',
					'defaultChecked' => true
				),
				array(
					'label'          => __( 'Failed', 'gravitysmtp' ),
					'dataKey'        => 'failed',
					'color'          => '#ff6b6b',
					'defaultChecked' => true
				),
			),
		);
	}

	protected function pad_with_empty_values( $data ) {
		// Don't pad empty results.
		if ( empty( $data ) ) {
			return $data;
		}

		list( $format, $interval ) = $this->get_date_format_and_interval();

		$period = new \DatePeriod(
			new \DateTime( get_date_from_gmt( $this->start ) ),
			new \DateInterval( $interval ),
			new \DateTime( get_date_from_gmt( $this->end ) )
		);

		$sorted_values = array();

		foreach( $period as $key => $value ) {
			$date = $value->format( $format );
			if ( ! array_key_exists( $date, $data ) ) {
				$sorted_values[ $date ] = array(
					'sent' => 0,
					'failed' => 0,
				);
			} else {
				$sorted_values[ $date ] = $data[ $date ];
			}
		}

		return $sorted_values;
	}

	protected function get_date_format_and_interval() {
		switch( $this->period ) {
			case 'day':
			default:
				$format = 'M d';
				$interval = 'P1D';
				break;
			case 'month':
				$format = 'M Y';
				$interval = 'P1M';
				break;
			case 'hour':
				$format = 'H:00 M d';
				$interval = 'PT1H';
				break;
		}

		return array( $format, $interval );
	}

	protected function get_email_totals() {
		$stats        = $this->model->get_event_stats( $this->start, $this->end );
		$opens        = $this->model->get_opens_for_period( $this->start, $this->end );
		$total        = array_sum( $stats );
		$open_decimal = $total > 0 ? round( $opens / $total, 2 ) : 0;

		return array(
			'emails'      => $total,
			'sent'        => isset( $stats['sent'] ) ? $stats['sent'] : 0,
			'percentOpen' => (int) ( $open_decimal * 100 ),
			'failed'      => isset( $stats['failed'] ) ? $stats['failed'] : 0,
		);
	}

	protected function get_top_sending_sources() {
		$sources = $this->model->get_top_sending_sources( $this->start, $this->end );

		// Some old entries are missing the source param and return the `headers` instead.
		$sources = array_filter( $sources, function ( $data ) {
			return $data['source'] !== 'headers';
		} );

		return array_values( $sources );
	}

	protected function get_top_email_recipients() {
		$recipients = $this->model->get_top_recipients( $this->start, $this->end );

		array_walk( $recipients, function ( &$item ) {
			$item['hash'] = hash( 'sha256', $item['recipients'] );
		} );

		$recipients = array_filter( $recipients, function ( $data ) {
			return $data['recipients'] !== 'headers' && ! empty( $data['recipients'] );
		} );

		return array_values( $recipients );
	}

	protected function get_your_integrations_info() {
		$connector_data = Gravity_SMTP::$container->get( Connector_Service_Provider::CONNECTOR_DATA_MAP );
		$return         = array();

		$connector_data = array_filter( $connector_data, function ( $info ) {
			return $info['data']['enabled'] && $info['data']['activated'];
		} );

		foreach ( $connector_data as $connector => $info ) {
			$data     = $info['data'];
			$return[] = array(
				'is_primary' => $data['is_primary'],
				'is_backup'  => $data['is_backup'],
				'configured' => $data['configured'],
				'name'       => $connector,
				'label'      => $info['title'],
				'logo'       => $info['logo'],
			);
		}

		usort( $return, function ( $a, $b ) {
			if ( $a['is_primary'] && $b['is_primary'] ) {
				return 0;
			}

			if ( $a['is_backup'] && $b['is_backup'] ) {
				return 0;
			}

			if ( $a['is_primary'] && $b['is_backup'] ) {
				return - 1;
			}

			if ( $a['is_backup'] && $b['is_primary'] ) {
				return 1;
			}

			if ( $a['is_primary'] ) {
				return - 1;
			}

			if ( $a['is_backup'] ) {
				return - 1;
			}

			return 1;
		} );

		return $return;
	}

	protected function get_quick_links() {
		// @todo - get actual values.
		return array(
			array(
				'label' => __( 'Getting Started', 'gravitysmtp' ),
				'href'  => 'https://docs.gravitysmtp.com/category/getting-started/',
			),
			array(
				'label' => __( 'Troubleshooting Gravity SMTP', 'gravitysmtp' ),
				'href'  => 'https://docs.gravitysmtp.com/troubleshooting-gravity-smtp/',
			),
			array(
				'label' => __( 'Email Delivery Best Practices', 'gravitysmtp' ),
				'href'  => 'https://docs.gravitysmtp.com/email-delivery-best-practices/',
			),
			array(
				'label' => __( 'An Overview of Gravity SMTP', 'gravitysmtp' ),
				'href'  => 'https://docs.gravitysmtp.com/using-gravity-smtp/',
			),
			array(
				'label' => __( 'Integrations', 'gravitysmtp' ),
				'href'  => 'https://docs.gravitysmtp.com/category/integrations/',
			),
			array(
				'label' => __( 'Frequently Asked Questions', 'gravitysmtp' ),
				'href'  => 'https://docs.gravitysmtp.com/frequently-asked-questions/',
			),
			array(
				'label' => __( 'Gravity SMTP Changelog', 'gravitysmtp' ),
				'href'  => 'https://docs.gravitysmtp.com/gravity-smtp-changelog/',
			),
			array(
				'label' => __( 'Open Support Ticket', 'gravitysmtp' ),
				'href'  => 'https://www.gravityforms.com/open-support-ticket/',
			),
		);
	}

	private function get_min_start_date( $restrict_to_range = false ) {
		$earliest_date    = $this->model->get_earliest_event_date();
		$passed_start     = strtotime( $this->start );
		$calculated_start = strtotime( $earliest_date );

		// Passed start is after the minimum retention period start, return it.
		if ( $calculated_start <= $passed_start ) {
			return get_date_from_gmt( $this->start );
		}

		if ( ! $restrict_to_range ) {
			return $earliest_date;
		}

		// Passed start is *before* minimum retention period start, return retention period start.
		$range_diff = $this->get_initial_range_value_from_data();
		$mod_string = sprintf( '-%d days', ( $range_diff - 1 ) );

		return gmdate( 'Y-m-d 00:00:00', strtotime( $mod_string ) );
	}

}
