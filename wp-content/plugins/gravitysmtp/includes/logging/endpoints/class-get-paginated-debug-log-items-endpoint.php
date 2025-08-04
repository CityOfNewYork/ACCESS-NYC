<?php

namespace Gravity_Forms\Gravity_SMTP\Logging\Endpoints;

use Gravity_Forms\Gravity_SMTP\Models\Debug_Log_Model;
use Gravity_Forms\Gravity_SMTP\Users\Roles;
use Gravity_Forms\Gravity_Tools\Endpoints\Endpoint;

class Get_Paginated_Debug_Log_Items_Endpoint extends Endpoint {

	const PARAM_PER_PAGE       = 'per_page';
	const PARAM_REQUESTED_PAGE = 'requested_page';
	const PARAM_MAX_DATE       = 'max_date';
	const PARAM_SEARCH_TERM    = 'search_term';
	const PARAM_SEARCH_TYPE    = 'search_type';
	const PARAM_PRIORITY       = 'priority';

	const ACTION_NAME = 'get_paginated_debug_log_items';

	/**
	 * @var Debug_Log_Model
	 */
	protected $events;

	public function __construct( Debug_Log_Model $event_model ) {
		$this->events = $event_model;
	}

	protected function get_nonce_name() {
		return self::ACTION_NAME;
	}

	public function handle() {
		if ( ! $this->validate() ) {
			wp_send_json_error( __( 'Missing required parameters.', 'gravitysmtp' ), 400 );
		}

		$per_page       = filter_input( INPUT_POST, 'per_page', FILTER_SANITIZE_NUMBER_INT );
		$requested_page = filter_input( INPUT_POST, 'requested_page', FILTER_SANITIZE_NUMBER_INT );
		$max_date       = filter_input( INPUT_POST, 'max_date' );
		$search_term    = filter_input( INPUT_POST, 'search_term' );
		$search_type    = filter_input( INPUT_POST, 'search_type' );
		$priority       = filter_input( INPUT_POST, 'priority' );

		if ( ! empty( $max_date ) ) {
			$max_date = htmlspecialchars( $max_date );
		}

		if ( ! empty( $search_term ) ) {
			$search_term = htmlspecialchars( $search_term );
		}

		if ( ! empty( $search_type ) ) {
			$search_type = htmlspecialchars( $search_type );
		}

		if ( ! empty( $priority ) ) {
			$priority = htmlspecialchars( $priority );
		}

		$requested_page = intval( $requested_page );
		$offset         = ( $requested_page - 1 ) * $per_page;

		if ( ! $max_date ) {
			$max_date = date( 'Y-m-d H:i:s', time() );
		}

		if ( empty( $per_page ) ) {
			$per_page = 20;
		}

		$rows            = $this->events->paginate( $requested_page, $per_page, $max_date, $search_term, $search_type, $priority );
		$count           = $this->events->count( $search_term, $search_type, $priority );

		$data = array(
			'rows'      => $this->get_formatted_data_rows( $rows ),
			'total'     => $count,
			'row_count' => count( $rows ),
		);

		wp_send_json_success( $data );
	}

	private function get_formatted_data_rows( $data ) {
		return $this->events->lines_as_data_grid( $data );
	}

	private function get_grid_actions( $event_id ) {
		$actions = array(
			'component'  => 'Box',
			'components' => array(
				array(
					'component' => 'Button',
					'props'     => array(
						'action'           => 'view',
						'customAttributes' => array(
							'title' => esc_html__( 'View email log', 'gravitysmtp' ),
						),
						'customClasses'    => array( 'gravitysmtp-data-grid__action' ),
						'icon'             => 'eye',
						'iconPrefix'       => 'gravitysmtp-admin-icon',
						'spacing'          => [ 0, 2, 0, 0 ],
						'size'             => 'size-height-s',
						'type'             => 'icon-white',
						'data'             => array(
							'event_id' => $event_id,
						),
						'disabled'         => ! current_user_can( Roles::VIEW_EMAIL_LOG_DETAILS ),
					),
				),
				array(
					'component' => 'Button',
					'props'     => array(
						'action'           => 'preview',
						'customAttributes' => array(
							'title' => esc_html__( 'View email', 'gravitysmtp' ),
						),
						'customClasses'    => array( 'gravitysmtp-data-grid__action' ),
						'icon'             => 'mail',
						'iconPrefix'       => 'gravitysmtp-admin-icon',
						'spacing'          => [ 0, 2, 0, 0 ],
						'size'             => 'size-height-s',
						'type'             => 'icon-white',
						'data'             => array(
							'event_id' => $event_id,
						),
						'disabled'         => ! current_user_can( Roles::VIEW_EMAIL_LOG_PREVIEW ),
					),
				),
				array(
					'component' => 'Button',
					'props'     => array(
						'action'           => 'delete',
						'customAttributes' => array(
							'title' => esc_html__( 'Delete email log', 'gravitysmtp' ),
						),
						'customClasses'    => array( 'gravitysmtp-data-grid__action' ),
						'icon'             => 'trash',
						'iconPrefix'       => 'gravitysmtp-admin-icon',
						'size'             => 'size-height-s',
						'type'             => 'icon-white',
						'data'             => array(
							'event_id' => $event_id,
						),
						'disabled'         => ! current_user_can( Roles::DELETE_EMAIL_LOG ),
					),
				),
			),
		);

		return apply_filters( 'gravitysmtp_debug_log_actions', $actions );
	}

}
