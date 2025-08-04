<?php

namespace Gravity_Forms\Gravity_SMTP\Apps\Config;

use Gravity_Forms\Gravity_SMTP\Apps\App_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Connectors\Endpoints\Save_Plugin_Settings_Endpoint;
use Gravity_Forms\Gravity_SMTP\Data_Store\Data_Store_Router;
use Gravity_Forms\Gravity_SMTP\Enums\Integration_Enum;
use Gravity_Forms\Gravity_SMTP\Enums\Status_Enum;
use Gravity_Forms\Gravity_SMTP\Feature_Flags\Feature_Flag_Manager;
use Gravity_Forms\Gravity_SMTP\Gravity_SMTP;
use Gravity_Forms\Gravity_SMTP\Tracking\Tracking_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Users\Roles;
use Gravity_Forms\Gravity_Tools\Config;
use Gravity_Forms\Gravity_SMTP\Utils\Booliesh;
use Gravity_Forms\Gravity_Tools\Utils\Utils_Service_Provider;

class Email_Log_Config extends Config {

	const SOURCE_LIST_ITEMS_TRANSIENT = 'gravitysmtp_source_cache';

	protected $script_to_localize = 'gravitysmtp_scripts_admin';
	protected $name               = 'gravitysmtp_admin_config';
	protected $overwrite          = false;

	public function should_enqueue() {
		if ( ! is_admin() ) {
			return false;
		}

		$page = filter_input( INPUT_GET, 'page' );

		if ( ! is_string( $page ) ) {
			return false;
		}

		$page     = htmlspecialchars( $page );
		$event_id = filter_input( INPUT_GET, 'event_id', FILTER_SANITIZE_NUMBER_INT );

		if ( $page !== 'gravitysmtp-activity-log' || ! empty( $event_id ) ) {
			return false;
		}

		return true;
	}

	public function get_grid_actions( $row ) {
		$actions = array(
			'component'  => 'Box',
			'components' => array(
				array(
					'component' => 'Button',
					'props'     => array(
						'action'        => 'view',
						'customAttributes' => array(
							'title' => esc_html__( 'View email log', 'gravitysmtp' ),
						),
						'customClasses' => array( 'gravitysmtp-data-grid__action' ),
						'icon'          => 'eye',
						'iconPrefix'    => 'gravitysmtp-admin-icon',
						'spacing'       => [ 0, 2, 0, 0 ],
						'size'          => 'size-height-s',
						'type'          => 'icon-white',
						'data'          => array(
							'event_id' => $row['id'],
						),
						'disabled' => ! current_user_can( Roles::VIEW_EMAIL_LOG_DETAILS ),
					),
				),
				array(
					'component' => 'Button',
					'props'     => array(
						'action'        => 'preview',
						'customAttributes' => array(
							'title' => esc_html__( 'View email', 'gravitysmtp' ),
						),
						'customClasses' => array( 'gravitysmtp-data-grid__action' ),
						'icon'          => 'mail',
						'iconPrefix'    => 'gravitysmtp-admin-icon',
						'spacing'       => [ 0, 2, 0, 0 ],
						'size'          => 'size-height-s',
						'type'          => 'icon-white',
						'data'          => array(
							'event_id' => $row['id'],
						),
						'disabled' => ! current_user_can( Roles::VIEW_EMAIL_LOG_PREVIEW ),
					),
				),
				array(
					'component' => 'Button',
					'props'     => array(
						'action'        => 'resend',
						'customAttributes' => array(
							'title' => esc_html__( 'Resend email', 'gravitysmtp' ),
						),
						'customClasses' => array( 'gravitysmtp-data-grid__action' ),
						'icon'          => 'paper-plane',
						'iconPrefix'    => 'gravitysmtp-admin-icon',
						'spacing'       => [ 0, 2, 0, 0 ],
						'size'          => 'size-height-s',
						'type'          => 'icon-white',
						'data'          => array(
							'event_id' => $row['id'],
						),
						'disabled' => ! current_user_can( Roles::VIEW_EMAIL_LOG_PREVIEW ) || ! $row['can_resend'], // @todo: Add resend permission?
					),
				),
				array(
					'component' => 'Button',
					'props'     => array(
						'action'        => 'delete',
						'customAttributes' => array(
							'title' => esc_html__( 'Delete email log', 'gravitysmtp' ),
						),
						'customClasses' => array( 'gravitysmtp-data-grid__action' ),
						'icon'          => 'trash',
						'iconPrefix'    => 'gravitysmtp-admin-icon',
						'size'          => 'size-height-s',
						'type'          => 'icon-white',
						'data'          => array(
							'event_id' => $row['id'],
						),
						'disabled' => ! current_user_can( Roles::DELETE_EMAIL_LOG ),
					),
				),
			),
		);

		return apply_filters( 'gravitysmtp_email_log_actions', $actions );
	}

	public function get_demo_data_rows() {
		$grid_actions = $this->get_grid_actions( array( 'id' => null, 'can_resend' => true ) );

		return array(
			array(
				'subject' => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'New WordPress User Registration',
						'size'    => 'text-sm',
						'weight'  => 'medium',
					),
				),
				'status'  => array(
					'component' => 'StatusIndicator',
					'props'     => array(
						'label' => 'Sent',
					),
				),
				'from'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'email@rocketgenius.com',
						'size'    => 'text-sm',
					),
				),
				'to'      => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'someone@outthere.com',
						'size'    => 'text-sm',
					),
				),
//				'clicked' => array(
//					'component' => 'Text',
//					'props'     => array(
//						'content' => 'no',
//						'size'    => 'text-sm',
//					),
//				),
				'date'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'February 23, 2023 at 1:50pm',
						'size'    => 'text-sm',
					),
				),
				'source'  => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'WooCommerce',
						'size'    => 'text-sm',
					),
				),
				'opened'  => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'yes',
						'size'    => 'text-sm',
					),
				),
				'actions' => $grid_actions,
			),
			array(
				'subject' => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'Thanks for contacting us',
						'size'    => 'text-sm',
						'weight'  => 'medium',
					),
				),
				'status'  => array(
					'component' => 'StatusIndicator',
					'props'     => array(
						'label'  => 'Pending',
						'status' => 'inactive',
					),
				),
				'from'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'sam@rocketgenius.com',
						'size'    => 'text-sm',
					),
				),
				'to'      => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'someone@elseoutthere.com',
						'size'    => 'text-sm',
					),
				),
//				'clicked' => array(
//					'component' => 'Text',
//					'props'     => array(
//						'content' => 'no',
//						'size'    => 'text-sm',
//					),
//				),
				'date'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'February 23, 2023 at 1:50pm',
						'size'    => 'text-sm',
					),
				),
				'source'  => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'WooCommerce',
						'size'    => 'text-sm',
					),
				),
				'opened'  => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'yes',
						'size'    => 'text-sm',
					),
				),
				'actions' => $grid_actions,
			),
			array(
				'subject' => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'Thanks for contacting us',
						'size'    => 'text-sm',
						'weight'  => 'medium',
					),
				),
				'status'  => array(
					'component' => 'StatusIndicator',
					'props'     => array(
						'label' => 'Sent',
					),
				),
				'from'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'aaron@rocketgenius.com',
						'size'    => 'text-sm',
					),
				),
				'to'      => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'someone@elseoutthere.com',
						'size'    => 'text-sm',
					),
				),
//				'opened'  => array(
//					'component' => 'Text',
//					'props'     => array(
//						'content' => 'yes',
//						'size'    => 'text-sm',
//					),
//				),
//				'clicked' => array(
//					'component' => 'Text',
//					'props'     => array(
//						'content' => 'no',
//						'size'    => 'text-sm',
//					),
//				),
				'date'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'May 23, 2023 at 1:50pm',
						'size'    => 'text-sm',
					),
				),
				'source'  => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'WooCommerce',
						'size'    => 'text-sm',
					),
				),
				'actions' => $grid_actions,
			),
			array(
				'subject' => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'Thanks for contacting us',
						'size'    => 'text-sm',
						'weight'  => 'medium',
					),
				),
				'status'  => array(
					'component' => 'StatusIndicator',
					'props'     => array(
						'label' => 'Sent',
					),
				),
				'from'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'aaron@rocketgenius.com',
						'size'    => 'text-sm',
					),
				),
				'to'      => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'someone@elseoutthere.com',
						'size'    => 'text-sm',
					),
				),
//				'opened'  => array(
//					'component' => 'Text',
//					'props'     => array(
//						'content' => 'yes',
//						'size'    => 'text-sm',
//					),
//				),
//				'clicked' => array(
//					'component' => 'Text',
//					'props'     => array(
//						'content' => 'no',
//						'size'    => 'text-sm',
//					),
//				),
				'date'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'August 13, 2023 at 3:50am',
						'size'    => 'text-sm',
					),
				),
				'source'  => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'WordPress',
						'size'    => 'text-sm',
					),
				),
				'actions' => $grid_actions,
			),
			array(
				'subject' => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'New WordPress User Registration',
						'size'    => 'text-sm',
						'weight'  => 'medium',
					),
				),
				'status'  => array(
					'component' => 'StatusIndicator',
					'props'     => array(
						'label' => 'Sent',
					),
				),
				'from'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'email@rocketgenius.com',
						'size'    => 'text-sm',
					),
				),
				'to'      => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'someone@outthere.com',
						'size'    => 'text-sm',
					),
				),
//				'opened'  => array(
//					'component' => 'Text',
//					'props'     => array(
//						'content' => 'yes',
//						'size'    => 'text-sm',
//					),
//				),
//				'clicked' => array(
//					'component' => 'Text',
//					'props'     => array(
//						'content' => 'no',
//						'size'    => 'text-sm',
//					),
//				),
				'date'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'February 23, 2023 at 1:50pm',
						'size'    => 'text-sm',
					),
				),
				'source'  => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'WooCommerce',
						'size'    => 'text-sm',
					),
				),
				'actions' => $grid_actions,
			),
			array(
				'subject' => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'Thanks for contacting us',
						'size'    => 'text-sm',
						'weight'  => 'medium',
					),
				),
				'status'  => array(
					'component' => 'StatusIndicator',
					'props'     => array(
						'label'  => 'Pending',
						'status' => 'inactive',
					),
				),
				'from'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'sam@rocketgenius.com',
						'size'    => 'text-sm',
					),
				),
				'to'      => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'someone@elseoutthere.com',
						'size'    => 'text-sm',
					),
				),
//				'opened'  => array(
//					'component' => 'Text',
//					'props'     => array(
//						'content' => 'yes',
//						'size'    => 'text-sm',
//					),
//				),
//				'clicked' => array(
//					'component' => 'Text',
//					'props'     => array(
//						'content' => 'no',
//						'size'    => 'text-sm',
//					),
//				),
				'date'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'February 23, 2023 at 1:50pm',
						'size'    => 'text-sm',
					),
				),
				'source'  => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'WooCommerce',
						'size'    => 'text-sm',
					),
				),
				'actions' => $grid_actions,
			),
			array(
				'subject' => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'Thanks for contacting us',
						'size'    => 'text-sm',
						'weight'  => 'medium',
					),
				),
				'status'  => array(
					'component' => 'StatusIndicator',
					'props'     => array(
						'label' => 'Sent',
					),
				),
				'from'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'aaron@rocketgenius.com',
						'size'    => 'text-sm',
					),
				),
				'to'      => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'someone@elseoutthere.com',
						'size'    => 'text-sm',
					),
				),
//				'opened'  => array(
//					'component' => 'Text',
//					'props'     => array(
//						'content' => 'yes',
//						'size'    => 'text-sm',
//					),
//				),
//				'clicked' => array(
//					'component' => 'Text',
//					'props'     => array(
//						'content' => 'no',
//						'size'    => 'text-sm',
//					),
//				),
				'date'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'May 23, 2023 at 1:50pm',
						'size'    => 'text-sm',
					),
				),
				'source'  => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'WooCommerce',
						'size'    => 'text-sm',
					),
				),
				'actions' => $grid_actions,
			),
			array(
				'subject' => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'Thanks for contacting us',
						'size'    => 'text-sm',
						'weight'  => 'medium',
					),
				),
				'status'  => array(
					'component' => 'StatusIndicator',
					'props'     => array(
						'label' => 'Sent',
					),
				),
				'from'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'aaron@rocketgenius.com',
						'size'    => 'text-sm',
					),
				),
				'to'      => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'someone@elseoutthere.com',
						'size'    => 'text-sm',
					),
				),
//				'opened'  => array(
//					'component' => 'Text',
//					'props'     => array(
//						'content' => 'yes',
//						'size'    => 'text-sm',
//					),
//				),
//				'clicked' => array(
//					'component' => 'Text',
//					'props'     => array(
//						'content' => 'no',
//						'size'    => 'text-sm',
//					),
//				),
				'date'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'August 13, 2023 at 3:50am',
						'size'    => 'text-sm',
					),
				),
				'source'  => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'WordPress',
						'size'    => 'text-sm',
					),
				),
				'actions' => $grid_actions,
			),
			array(
				'subject' => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'New WordPress User Registration',
						'size'    => 'text-sm',
						'weight'  => 'medium',
					),
				),
				'status'  => array(
					'component' => 'StatusIndicator',
					'props'     => array(
						'label' => 'Sent',
					),
				),
				'from'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'email@rocketgenius.com',
						'size'    => 'text-sm',
					),
				),
				'to'      => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'someone@outthere.com',
						'size'    => 'text-sm',
					),
				),
//				'opened'  => array(
//					'component' => 'Text',
//					'props'     => array(
//						'content' => 'yes',
//						'size'    => 'text-sm',
//					),
//				),
//				'clicked' => array(
//					'component' => 'Text',
//					'props'     => array(
//						'content' => 'no',
//						'size'    => 'text-sm',
//					),
//				),
				'date'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'February 23, 2023 at 1:50pm',
						'size'    => 'text-sm',
					),
				),
				'source'  => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'WooCommerce',
						'size'    => 'text-sm',
					),
				),
				'actions' => $grid_actions,
			),
			array(
				'subject' => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'Thanks for contacting us',
						'size'    => 'text-sm',
						'weight'  => 'medium',
					),
				),
				'status'  => array(
					'component' => 'StatusIndicator',
					'props'     => array(
						'label'  => 'Pending',
						'status' => 'inactive',
					),
				),
				'from'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'sam@rocketgenius.com',
						'size'    => 'text-sm',
					),
				),
				'to'      => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'someone@elseoutthere.com',
						'size'    => 'text-sm',
					),
				),
//				'opened'  => array(
//					'component' => 'Text',
//					'props'     => array(
//						'content' => 'yes',
//						'size'    => 'text-sm',
//					),
//				),
//				'clicked' => array(
//					'component' => 'Text',
//					'props'     => array(
//						'content' => 'no',
//						'size'    => 'text-sm',
//					),
//				),
				'date'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'February 23, 2023 at 1:50pm',
						'size'    => 'text-sm',
					),
				),
				'source'  => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'WooCommerce',
						'size'    => 'text-sm',
					),
				),
				'actions' => $grid_actions,
			),
			array(
				'subject' => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'Thanks for contacting us',
						'size'    => 'text-sm',
						'weight'  => 'medium',
					),
				),
				'status'  => array(
					'component' => 'StatusIndicator',
					'props'     => array(
						'label' => 'Sent',
					),
				),
				'from'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'aaron@rocketgenius.com',
						'size'    => 'text-sm',
					),
				),
				'to'      => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'someone@elseoutthere.com',
						'size'    => 'text-sm',
					),
				),
//				'opened'  => array(
//					'component' => 'Text',
//					'props'     => array(
//						'content' => 'yes',
//						'size'    => 'text-sm',
//					),
//				),
//				'clicked' => array(
//					'component' => 'Text',
//					'props'     => array(
//						'content' => 'no',
//						'size'    => 'text-sm',
//					),
//				),
				'date'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'May 23, 2023 at 1:50pm',
						'size'    => 'text-sm',
					),
				),
				'source'  => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'WooCommerce',
						'size'    => 'text-sm',
					),
				),
				'actions' => $grid_actions,
			),
			array(
				'subject' => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'Thanks for contacting us',
						'size'    => 'text-sm',
						'weight'  => 'medium',
					),
				),
				'status'  => array(
					'component' => 'StatusIndicator',
					'props'     => array(
						'label' => 'Sent',
					),
				),
				'from'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'aaron@rocketgenius.com',
						'size'    => 'text-sm',
					),
				),
				'to'      => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'someone@elseoutthere.com',
						'size'    => 'text-sm',
					),
				),
//				'opened'  => array(
//					'component' => 'Text',
//					'props'     => array(
//						'content' => 'yes',
//						'size'    => 'text-sm',
//					),
//				),
//				'clicked' => array(
//					'component' => 'Text',
//					'props'     => array(
//						'content' => 'no',
//						'size'    => 'text-sm',
//					),
//				),
				'date'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'August 13, 2023 at 3:50am',
						'size'    => 'text-sm',
					),
				),
				'source'  => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'WordPress',
						'size'    => 'text-sm',
					),
				),
				'actions' => $grid_actions,
			),
			array(
				'subject' => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'New WordPress User Registration',
						'size'    => 'text-sm',
						'weight'  => 'medium',
					),
				),
				'status'  => array(
					'component' => 'StatusIndicator',
					'props'     => array(
						'label' => 'Sent',
					),
				),
				'from'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'email@rocketgenius.com',
						'size'    => 'text-sm',
					),
				),
				'to'      => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'someone@outthere.com',
						'size'    => 'text-sm',
					),
				),
//				'opened'  => array(
//					'component' => 'Text',
//					'props'     => array(
//						'content' => 'yes',
//						'size'    => 'text-sm',
//					),
//				),
//				'clicked' => array(
//					'component' => 'Text',
//					'props'     => array(
//						'content' => 'no',
//						'size'    => 'text-sm',
//					),
//				),
				'date'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'February 23, 2023 at 1:50pm',
						'size'    => 'text-sm',
					),
				),
				'source'  => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'WooCommerce',
						'size'    => 'text-sm',
					),
				),
				'actions' => $grid_actions,
			),
			array(
				'subject' => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'Thanks for contacting us',
						'size'    => 'text-sm',
						'weight'  => 'medium',
					),
				),
				'status'  => array(
					'component' => 'StatusIndicator',
					'props'     => array(
						'label'  => 'Pending',
						'status' => 'inactive',
					),
				),
				'from'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'sam@rocketgenius.com',
						'size'    => 'text-sm',
					),
				),
				'to'      => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'someone@elseoutthere.com',
						'size'    => 'text-sm',
					),
				),
//				'opened'  => array(
//					'component' => 'Text',
//					'props'     => array(
//						'content' => 'yes',
//						'size'    => 'text-sm',
//					),
//				),
//				'clicked' => array(
//					'component' => 'Text',
//					'props'     => array(
//						'content' => 'no',
//						'size'    => 'text-sm',
//					),
//				),
				'date'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'February 23, 2023 at 1:50pm',
						'size'    => 'text-sm',
					),
				),
				'source'  => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'WooCommerce',
						'size'    => 'text-sm',
					),
				),
				'actions' => $grid_actions,
			),
			array(
				'subject' => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'Thanks for contacting us',
						'size'    => 'text-sm',
						'weight'  => 'medium',
					),
				),
				'status'  => array(
					'component' => 'StatusIndicator',
					'props'     => array(
						'label' => 'Sent',
					),
				),
				'from'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'aaron@rocketgenius.com',
						'size'    => 'text-sm',
					),
				),
				'to'      => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'someone@elseoutthere.com',
						'size'    => 'text-sm',
					),
				),
//				'opened'  => array(
//					'component' => 'Text',
//					'props'     => array(
//						'content' => 'yes',
//						'size'    => 'text-sm',
//					),
//				),
//				'clicked' => array(
//					'component' => 'Text',
//					'props'     => array(
//						'content' => 'no',
//						'size'    => 'text-sm',
//					),
//				),
				'date'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'May 23, 2023 at 1:50pm',
						'size'    => 'text-sm',
					),
				),
				'source'  => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'WooCommerce',
						'size'    => 'text-sm',
					),
				),
				'actions' => $grid_actions,
			),
			array(
				'subject' => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'Thanks for contacting us',
						'size'    => 'text-sm',
						'weight'  => 'medium',
					),
				),
				'status'  => array(
					'component' => 'StatusIndicator',
					'props'     => array(
						'label' => 'Sent',
					),
				),
				'from'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'aaron@rocketgenius.com',
						'size'    => 'text-sm',
					),
				),
				'to'      => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'someone@elseoutthere.com',
						'size'    => 'text-sm',
					),
				),
//				'opened'  => array(
//					'component' => 'Text',
//					'props'     => array(
//						'content' => 'yes',
//						'size'    => 'text-sm',
//					),
//				),
//				'clicked' => array(
//					'component' => 'Text',
//					'props'     => array(
//						'content' => 'no',
//						'size'    => 'text-sm',
//					),
//				),
				'date'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'August 13, 2023 at 3:50am',
						'size'    => 'text-sm',
					),
				),
				'source'  => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'WordPress',
						'size'    => 'text-sm',
					),
				),
				'actions' => $grid_actions,
			),
			array(
				'subject' => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'New WordPress User Registration',
						'size'    => 'text-sm',
						'weight'  => 'medium',
					),
				),
				'status'  => array(
					'component' => 'StatusIndicator',
					'props'     => array(
						'label' => 'Sent',
					),
				),
				'from'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'email@rocketgenius.com',
						'size'    => 'text-sm',
					),
				),
				'to'      => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'someone@outthere.com',
						'size'    => 'text-sm',
					),
				),
//				'opened'  => array(
//					'component' => 'Text',
//					'props'     => array(
//						'content' => 'yes',
//						'size'    => 'text-sm',
//					),
//				),
//				'clicked' => array(
//					'component' => 'Text',
//					'props'     => array(
//						'content' => 'no',
//						'size'    => 'text-sm',
//					),
//				),
				'date'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'February 23, 2023 at 1:50pm',
						'size'    => 'text-sm',
					),
				),
				'source'  => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'WooCommerce',
						'size'    => 'text-sm',
					),
				),
				'actions' => $grid_actions,
			),
			array(
				'subject' => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'Thanks for contacting us',
						'size'    => 'text-sm',
						'weight'  => 'medium',
					),
				),
				'status'  => array(
					'component' => 'StatusIndicator',
					'props'     => array(
						'label'  => 'Pending',
						'status' => 'inactive',
					),
				),
				'from'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'sam@rocketgenius.com',
						'size'    => 'text-sm',
					),
				),
				'to'      => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'someone@elseoutthere.com',
						'size'    => 'text-sm',
					),
				),
//				'opened'  => array(
//					'component' => 'Text',
//					'props'     => array(
//						'content' => 'yes',
//						'size'    => 'text-sm',
//					),
//				),
//				'clicked' => array(
//					'component' => 'Text',
//					'props'     => array(
//						'content' => 'no',
//						'size'    => 'text-sm',
//					),
//				),
				'date'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'February 23, 2023 at 1:50pm',
						'size'    => 'text-sm',
					),
				),
				'source'  => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'WooCommerce',
						'size'    => 'text-sm',
					),
				),
				'actions' => $grid_actions,
			),
			array(
				'subject' => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'Thanks for contacting us',
						'size'    => 'text-sm',
						'weight'  => 'medium',
					),
				),
				'status'  => array(
					'component' => 'StatusIndicator',
					'props'     => array(
						'label' => 'Sent',
					),
				),
				'from'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'aaron@rocketgenius.com',
						'size'    => 'text-sm',
					),
				),
				'to'      => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'someone@elseoutthere.com',
						'size'    => 'text-sm',
					),
				),
//				'opened'  => array(
//					'component' => 'Text',
//					'props'     => array(
//						'content' => 'yes',
//						'size'    => 'text-sm',
//					),
//				),
//				'clicked' => array(
//					'component' => 'Text',
//					'props'     => array(
//						'content' => 'no',
//						'size'    => 'text-sm',
//					),
//				),
				'date'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'May 23, 2023 at 1:50pm',
						'size'    => 'text-sm',
					),
				),
				'source'  => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'WooCommerce',
						'size'    => 'text-sm',
					),
				),
				'actions' => $grid_actions,
			),
			array(
				'subject' => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'Thanks for contacting us',
						'size'    => 'text-sm',
						'weight'  => 'medium',
					),
				),
				'status'  => array(
					'component' => 'StatusIndicator',
					'props'     => array(
						'label' => 'Sent',
					),
				),
				'from'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'aaron@rocketgenius.com',
						'size'    => 'text-sm',
					),
				),
				'to'      => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'someone@elseoutthere.com',
						'size'    => 'text-sm',
					),
				),
//				'opened'  => array(
//					'component' => 'Text',
//					'props'     => array(
//						'content' => 'yes',
//						'size'    => 'text-sm',
//					),
//				),
//				'clicked' => array(
//					'component' => 'Text',
//					'props'     => array(
//						'content' => 'no',
//						'size'    => 'text-sm',
//					),
//				),
				'date'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'August 13, 2023 at 3:50am',
						'size'    => 'text-sm',
					),
				),
				'source'  => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'WordPress',
						'size'    => 'text-sm',
					),
				),
				'actions' => $grid_actions,
			),
			array(
				'subject' => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'New WordPress User Registration',
						'size'    => 'text-sm',
						'weight'  => 'medium',
					),
				),
				'status'  => array(
					'component' => 'StatusIndicator',
					'props'     => array(
						'label' => 'Sent',
					),
				),
				'from'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'email@rocketgenius.com',
						'size'    => 'text-sm',
					),
				),
				'to'      => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'someone@outthere.com',
						'size'    => 'text-sm',
					),
				),
//				'opened'  => array(
//					'component' => 'Text',
//					'props'     => array(
//						'content' => 'yes',
//						'size'    => 'text-sm',
//					),
//				),
//				'clicked' => array(
//					'component' => 'Text',
//					'props'     => array(
//						'content' => 'no',
//						'size'    => 'text-sm',
//					),
//				),
				'date'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'February 23, 2023 at 1:50pm',
						'size'    => 'text-sm',
					),
				),
				'source'  => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'WooCommerce',
						'size'    => 'text-sm',
					),
				),
				'actions' => $grid_actions,
			),
			array(
				'subject' => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'Thanks for contacting us',
						'size'    => 'text-sm',
						'weight'  => 'medium',
					),
				),
				'status'  => array(
					'component' => 'StatusIndicator',
					'props'     => array(
						'label'  => 'Pending',
						'status' => 'inactive',
					),
				),
				'from'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'sam@rocketgenius.com',
						'size'    => 'text-sm',
					),
				),
				'to'      => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'someone@elseoutthere.com',
						'size'    => 'text-sm',
					),
				),
//				'opened'  => array(
//					'component' => 'Text',
//					'props'     => array(
//						'content' => 'yes',
//						'size'    => 'text-sm',
//					),
//				),
//				'clicked' => array(
//					'component' => 'Text',
//					'props'     => array(
//						'content' => 'no',
//						'size'    => 'text-sm',
//					),
//				),
				'date'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'February 23, 2023 at 1:50pm',
						'size'    => 'text-sm',
					),
				),
				'source'  => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'WooCommerce',
						'size'    => 'text-sm',
					),
				),
				'actions' => $grid_actions,
			),
			array(
				'subject' => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'Thanks for contacting us',
						'size'    => 'text-sm',
						'weight'  => 'medium',
					),
				),
				'status'  => array(
					'component' => 'StatusIndicator',
					'props'     => array(
						'label' => 'Sent',
					),
				),
				'from'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'aaron@rocketgenius.com',
						'size'    => 'text-sm',
					),
				),
				'to'      => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'someone@elseoutthere.com',
						'size'    => 'text-sm',
					),
				),
//				'opened'  => array(
//					'component' => 'Text',
//					'props'     => array(
//						'content' => 'yes',
//						'size'    => 'text-sm',
//					),
//				),
//				'clicked' => array(
//					'component' => 'Text',
//					'props'     => array(
//						'content' => 'no',
//						'size'    => 'text-sm',
//					),
//				),
				'date'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'May 23, 2023 at 1:50pm',
						'size'    => 'text-sm',
					),
				),
				'source'  => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'WooCommerce',
						'size'    => 'text-sm',
					),
				),
				'actions' => $grid_actions,
			),
			array(
				'subject' => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'Thanks for contacting us',
						'size'    => 'text-sm',
						'weight'  => 'medium',
					),
				),
				'status'  => array(
					'component' => 'StatusIndicator',
					'props'     => array(
						'label' => 'Sent',
					),
				),
				'from'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'aaron@rocketgenius.com',
						'size'    => 'text-sm',
					),
				),
				'to'      => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'someone@elseoutthere.com',
						'size'    => 'text-sm',
					),
				),
//				'opened'  => array(
//					'component' => 'Text',
//					'props'     => array(
//						'content' => 'yes',
//						'size'    => 'text-sm',
//					),
//				),
//				'clicked' => array(
//					'component' => 'Text',
//					'props'     => array(
//						'content' => 'no',
//						'size'    => 'text-sm',
//					),
//				),
				'date'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'August 13, 2023 at 3:50am',
						'size'    => 'text-sm',
					),
				),
				'source'  => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'WordPress',
						'size'    => 'text-sm',
					),
				),
				'actions' => $grid_actions,
			),
			array(
				'subject' => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'New WordPress User Registration',
						'size'    => 'text-sm',
						'weight'  => 'medium',
					),
				),
				'status'  => array(
					'component' => 'StatusIndicator',
					'props'     => array(
						'label' => 'Sent',
					),
				),
				'from'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'email@rocketgenius.com',
						'size'    => 'text-sm',
					),
				),
				'to'      => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'someone@outthere.com',
						'size'    => 'text-sm',
					),
				),
//				'opened'  => array(
//					'component' => 'Text',
//					'props'     => array(
//						'content' => 'yes',
//						'size'    => 'text-sm',
//					),
//				),
//				'clicked' => array(
//					'component' => 'Text',
//					'props'     => array(
//						'content' => 'no',
//						'size'    => 'text-sm',
//					),
//				),
				'date'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'February 23, 2023 at 1:50pm',
						'size'    => 'text-sm',
					),
				),
				'source'  => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'WooCommerce',
						'size'    => 'text-sm',
					),
				),
				'actions' => $grid_actions,
			),
			array(
				'subject' => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'Thanks for contacting us',
						'size'    => 'text-sm',
						'weight'  => 'medium',
					),
				),
				'status'  => array(
					'component' => 'StatusIndicator',
					'props'     => array(
						'label'  => 'Pending',
						'status' => 'inactive',
					),
				),
				'from'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'sam@rocketgenius.com',
						'size'    => 'text-sm',
					),
				),
				'to'      => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'someone@elseoutthere.com',
						'size'    => 'text-sm',
					),
				),
//				'opened'  => array(
//					'component' => 'Text',
//					'props'     => array(
//						'content' => 'yes',
//						'size'    => 'text-sm',
//					),
//				),
//				'clicked' => array(
//					'component' => 'Text',
//					'props'     => array(
//						'content' => 'no',
//						'size'    => 'text-sm',
//					),
//				),
				'date'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'February 23, 2023 at 1:50pm',
						'size'    => 'text-sm',
					),
				),
				'source'  => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'WooCommerce',
						'size'    => 'text-sm',
					),
				),
				'actions' => $grid_actions,
			),
			array(
				'subject' => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'Thanks for contacting us',
						'size'    => 'text-sm',
						'weight'  => 'medium',
					),
				),
				'status'  => array(
					'component' => 'StatusIndicator',
					'props'     => array(
						'label' => 'Sent',
					),
				),
				'from'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'aaron@rocketgenius.com',
						'size'    => 'text-sm',
					),
				),
				'to'      => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'someone@elseoutthere.com',
						'size'    => 'text-sm',
					),
				),
//				'opened'  => array(
//					'component' => 'Text',
//					'props'     => array(
//						'content' => 'yes',
//						'size'    => 'text-sm',
//					),
//				),
//				'clicked' => array(
//					'component' => 'Text',
//					'props'     => array(
//						'content' => 'no',
//						'size'    => 'text-sm',
//					),
//				),
				'date'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'May 23, 2023 at 1:50pm',
						'size'    => 'text-sm',
					),
				),
				'source'  => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'WooCommerce',
						'size'    => 'text-sm',
					),
				),
				'actions' => $grid_actions,
			),
			array(
				'subject' => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'Thanks for contacting us',
						'size'    => 'text-sm',
						'weight'  => 'medium',
					),
				),
				'status'  => array(
					'component' => 'StatusIndicator',
					'props'     => array(
						'label' => 'Sent',
					),
				),
				'from'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'aaron@rocketgenius.com',
						'size'    => 'text-sm',
					),
				),
				'to'      => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'someone@elseoutthere.com',
						'size'    => 'text-sm',
					),
				),
//				'opened'  => array(
//					'component' => 'Text',
//					'props'     => array(
//						'content' => 'yes',
//						'size'    => 'text-sm',
//					),
//				),
//				'clicked' => array(
//					'component' => 'Text',
//					'props'     => array(
//						'content' => 'no',
//						'size'    => 'text-sm',
//					),
//				),
				'date'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'August 13, 2023 at 3:50am',
						'size'    => 'text-sm',
					),
				),
				'source'  => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'WordPress',
						'size'    => 'text-sm',
					),
				),
				'actions' => $grid_actions,
			),
			array(
				'subject' => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'New WordPress User Registration',
						'size'    => 'text-sm',
						'weight'  => 'medium',
					),
				),
				'status'  => array(
					'component' => 'StatusIndicator',
					'props'     => array(
						'label' => 'Sent',
					),
				),
				'from'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'email@rocketgenius.com',
						'size'    => 'text-sm',
					),
				),
				'to'      => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'someone@outthere.com',
						'size'    => 'text-sm',
					),
				),
//				'opened'  => array(
//					'component' => 'Text',
//					'props'     => array(
//						'content' => 'yes',
//						'size'    => 'text-sm',
//					),
//				),
//				'clicked' => array(
//					'component' => 'Text',
//					'props'     => array(
//						'content' => 'no',
//						'size'    => 'text-sm',
//					),
//				),
				'date'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'February 23, 2023 at 1:50pm',
						'size'    => 'text-sm',
					),
				),
				'source'  => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'WooCommerce',
						'size'    => 'text-sm',
					),
				),
				'actions' => $grid_actions,
			),
			array(
				'subject' => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'Thanks for contacting us',
						'size'    => 'text-sm',
						'weight'  => 'medium',
					),
				),
				'status'  => array(
					'component' => 'StatusIndicator',
					'props'     => array(
						'label'  => 'Pending',
						'status' => 'inactive',
					),
				),
				'from'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'sam@rocketgenius.com',
						'size'    => 'text-sm',
					),
				),
				'to'      => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'someone@elseoutthere.com',
						'size'    => 'text-sm',
					),
				),
//				'opened'  => array(
//					'component' => 'Text',
//					'props'     => array(
//						'content' => 'yes',
//						'size'    => 'text-sm',
//					),
//				),
//				'clicked' => array(
//					'component' => 'Text',
//					'props'     => array(
//						'content' => 'no',
//						'size'    => 'text-sm',
//					),
//				),
				'date'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'February 23, 2023 at 1:50pm',
						'size'    => 'text-sm',
					),
				),
				'source'  => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'WooCommerce',
						'size'    => 'text-sm',
					),
				),
				'actions' => $grid_actions,
			),
			array(
				'subject' => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'Thanks for contacting us',
						'size'    => 'text-sm',
						'weight'  => 'medium',
					),
				),
				'status'  => array(
					'component' => 'StatusIndicator',
					'props'     => array(
						'label' => 'Sent',
					),
				),
				'from'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'aaron@rocketgenius.com',
						'size'    => 'text-sm',
					),
				),
				'to'      => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'someone@elseoutthere.com',
						'size'    => 'text-sm',
					),
				),
//				'opened'  => array(
//					'component' => 'Text',
//					'props'     => array(
//						'content' => 'yes',
//						'size'    => 'text-sm',
//					),
//				),
//				'clicked' => array(
//					'component' => 'Text',
//					'props'     => array(
//						'content' => 'no',
//						'size'    => 'text-sm',
//					),
//				),
				'date'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'May 23, 2023 at 1:50pm',
						'size'    => 'text-sm',
					),
				),
				'source'  => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'WooCommerce',
						'size'    => 'text-sm',
					),
				),
				'actions' => $grid_actions,
			),
			array(
				'subject' => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'Thanks for contacting us',
						'size'    => 'text-sm',
						'weight'  => 'medium',
					),
				),
				'status'  => array(
					'component' => 'StatusIndicator',
					'props'     => array(
						'label' => 'Sent',
					),
				),
				'from'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'aaron@rocketgenius.com',
						'size'    => 'text-sm',
					),
				),
				'to'      => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'someone@elseoutthere.com',
						'size'    => 'text-sm',
					),
				),
//				'opened'  => array(
//					'component' => 'Text',
//					'props'     => array(
//						'content' => 'yes',
//						'size'    => 'text-sm',
//					),
//				),
//				'clicked' => array(
//					'component' => 'Text',
//					'props'     => array(
//						'content' => 'no',
//						'size'    => 'text-sm',
//					),
//				),
				'date'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'August 13, 2023 at 3:50am',
						'size'    => 'text-sm',
					),
				),
				'source'  => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'WordPress',
						'size'    => 'text-sm',
					),
				),
				'actions' => $grid_actions,
			),
			array(
				'subject' => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'New WordPress User Registration',
						'size'    => 'text-sm',
						'weight'  => 'medium',
					),
				),
				'status'  => array(
					'component' => 'StatusIndicator',
					'props'     => array(
						'label' => 'Sent',
					),
				),
				'from'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'email@rocketgenius.com',
						'size'    => 'text-sm',
					),
				),
				'to'      => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'someone@outthere.com',
						'size'    => 'text-sm',
					),
				),
//				'opened'  => array(
//					'component' => 'Text',
//					'props'     => array(
//						'content' => 'yes',
//						'size'    => 'text-sm',
//					),
//				),
//				'clicked' => array(
//					'component' => 'Text',
//					'props'     => array(
//						'content' => 'no',
//						'size'    => 'text-sm',
//					),
//				),
				'date'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'February 23, 2023 at 1:50pm',
						'size'    => 'text-sm',
					),
				),
				'source'  => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'WooCommerce',
						'size'    => 'text-sm',
					),
				),
				'actions' => $grid_actions,
			),
			array(
				'subject' => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'Thanks for contacting us',
						'size'    => 'text-sm',
						'weight'  => 'medium',
					),
				),
				'status'  => array(
					'component' => 'StatusIndicator',
					'props'     => array(
						'label'  => 'Pending',
						'status' => 'inactive',
					),
				),
				'from'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'sam@rocketgenius.com',
						'size'    => 'text-sm',
					),
				),
				'to'      => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'someone@elseoutthere.com',
						'size'    => 'text-sm',
					),
				),
//				'opened'  => array(
//					'component' => 'Text',
//					'props'     => array(
//						'content' => 'yes',
//						'size'    => 'text-sm',
//					),
//				),
//				'clicked' => array(
//					'component' => 'Text',
//					'props'     => array(
//						'content' => 'no',
//						'size'    => 'text-sm',
//					),
//				),
				'date'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'February 23, 2023 at 1:50pm',
						'size'    => 'text-sm',
					),
				),
				'source'  => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'WooCommerce',
						'size'    => 'text-sm',
					),
				),
				'actions' => $grid_actions,
			),
			array(
				'subject' => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'Thanks for contacting us',
						'size'    => 'text-sm',
						'weight'  => 'medium',
					),
				),
				'status'  => array(
					'component' => 'StatusIndicator',
					'props'     => array(
						'label' => 'Sent',
					),
				),
				'from'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'aaron@rocketgenius.com',
						'size'    => 'text-sm',
					),
				),
				'to'      => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'someone@elseoutthere.com',
						'size'    => 'text-sm',
					),
				),
//				'opened'  => array(
//					'component' => 'Text',
//					'props'     => array(
//						'content' => 'yes',
//						'size'    => 'text-sm',
//					),
//				),
//				'clicked' => array(
//					'component' => 'Text',
//					'props'     => array(
//						'content' => 'no',
//						'size'    => 'text-sm',
//					),
//				),
				'date'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'May 23, 2023 at 1:50pm',
						'size'    => 'text-sm',
					),
				),
				'source'  => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'WooCommerce',
						'size'    => 'text-sm',
					),
				),
				'actions' => $grid_actions,
			),
			array(
				'subject' => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'Thanks for contacting us',
						'size'    => 'text-sm',
						'weight'  => 'medium',
					),
				),
				'status'  => array(
					'component' => 'StatusIndicator',
					'props'     => array(
						'label' => 'Sent',
					),
				),
				'from'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'aaron@rocketgenius.com',
						'size'    => 'text-sm',
					),
				),
				'to'      => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'someone@elseoutthere.com',
						'size'    => 'text-sm',
					),
				),
//				'opened'  => array(
//					'component' => 'Text',
//					'props'     => array(
//						'content' => 'yes',
//						'size'    => 'text-sm',
//					),
//				),
//				'clicked' => array(
//					'component' => 'Text',
//					'props'     => array(
//						'content' => 'no',
//						'size'    => 'text-sm',
//					),
//				),
				'date'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'August 13, 2023 at 3:50am',
						'size'    => 'text-sm',
					),
				),
				'source'  => array(
					'component' => 'Text',
					'props'     => array(
						'content' => 'WordPress',
						'size'    => 'text-sm',
					),
				),
				'actions' => $grid_actions,
			),
		);
	}

	public function get_bulk_actions() {
		return array(
			array(
				'label' => esc_html__( 'Bulk Actions', 'gravitysmtp' ),
				'value' => '-1',
			),
			array(
				'label' => esc_html__( 'Delete', 'gravitysmtp' ),
				'value' => 'delete',
			),
		);
	}

	public function get_columns() {
		$plugin_data_store = Gravity_SMTP::container()->get( Connector_Service_Provider::DATA_STORE_ROUTER );

		$open_tracking_enabled = $plugin_data_store->get_plugin_setting( Tracking_Service_Provider::SETTING_OPEN_TRACKING, 'false' );
		$open_tracking_enabled = Booliesh::get( $open_tracking_enabled );

		$columns = array(
			array(
				'component'       => 'Text',
				'hideWhenLoading' => true,
				'key'             => 'subject',
				'props'           => array(
					'content' => esc_html__( 'Subject', 'gravitysmtp' ),
					'size'    => 'text-sm',
					'weight'  => 'medium',
				),
				'sortable'        => true,
				'variableLoader'  => true,
			),
			array(
				'component'       => 'Text',
				'hideWhenLoading' => true,
				'key'             => 'status',
				'props'           => array(
					'content' => esc_html__( 'Status', 'gravitysmtp' ),
					'size'    => 'text-sm',
					'weight'  => 'medium',
				),
				'sortable'        => true,
			),
			array(
				'component'       => 'Text',
				'hideAt'          => 960,
				'hideWhenLoading' => true,
				'key'             => 'to',
				'props'           => array(
					'content' => esc_html__( 'Recipient', 'gravitysmtp' ),
					'size'    => 'text-sm',
					'weight'  => 'medium',
				),
				'sortable'        => true,
			),
		);

		if ( Feature_Flag_Manager::is_enabled( 'email_open_tracking' ) && $open_tracking_enabled ) {
			$columns[] = array(
				'component'       => 'Text',
				'hideAt'          => 640,
				'hideWhenLoading' => false,
				'key'             => 'opened',
				'props'           => array(
					'content' => esc_html__( 'Opened', 'gravitysmtp' ),
					'size'    => 'text-sm',
					'weight'  => 'medium',
				),
				'sortable'        => true,
			);
		}

		$columns[] = array(
			'component'       => 'Text',
			'hideAt'          => 960,
			'hideWhenLoading' => true,
			'key'             => 'source',
			'props'           => array(
				'content' => esc_html__( 'Source', 'gravitysmtp' ),
				'size'    => 'text-sm',
				'weight'  => 'medium',
			),
			'sortable'        => true,
			'variableLoader'  => true,
		);

		$columns[] = array(
			'cellClasses'     => 'gravitysmtp-activity-log-app__activity-log-table-integration',
			'component'       => 'Text',
			'hideAt'          => 960,
			'hideWhenLoading' => true,
			'key'             => 'integration',
			'props'           => array(
				'content' => esc_html__( 'Service', 'gravitysmtp' ),
				'size'    => 'text-sm',
				'weight'  => 'medium',
			),
			'sortable'        => true,
		);

		$columns[] = array(
			'component'       => 'Text',
			'hideAt'          => 640,
			'hideWhenLoading' => false,
			'key'             => 'date',
			'props'           => array(
				'content' => esc_html__( 'Date Sent', 'gravitysmtp' ),
				'size'    => 'text-sm',
				'weight'  => 'medium',
			),
			'sortable'        => true,
		);

		$columns[] = array(
			'component' => 'Text',
			'hideAt'    => 640,
			'key'       => 'actions',
			'props'     => array(
				'content' => esc_html__( 'Actions', 'gravitysmtp' ),
				'size'    => 'text-sm',
				'weight'  => 'medium',
			),
		);

		return apply_filters( 'gravitysmtp_email_log_columns', $columns );
	}

	public function get_column_style_props() {
		$plugin_data_store = Gravity_SMTP::container()->get( Connector_Service_Provider::DATA_STORE_ROUTER );

		$open_tracking_enabled = $plugin_data_store->get_plugin_setting( Tracking_Service_Provider::SETTING_OPEN_TRACKING, 'false' );
		$open_tracking_enabled = Booliesh::get( $open_tracking_enabled );

		$props = array(
			'subject'     => array( 'flexBasis' => '292px' ),
			'status'      => array( 'flex' => '0 0 122px' ),
			'from'        => array( 'flexBasis' => '160px' ),
			'to'          => array( 'flexBasis' => '160px' ),
		);

		if ( Feature_Flag_Manager::is_enabled( 'email_open_tracking' ) && $open_tracking_enabled ) {
			$props['opened'] = array( 'flex' => '0 0 90px' );
		}

		// Continue adding the remaining entries, starting with 'source'
		$props['source']      = array( 'flexBasis' => '104px' );
		$props['integration'] = array( 'flex' => '0 0 122px' );
		$props['date']        = array( 'flexBasis' => '250px' );
		$props['actions']     = array( 'flex' => '0 0 180px' );

		return apply_filters( 'gravitysmtp_email_log_column_style_props', $props );
	}


	public function get_i18n() {
		return array(
			'error_alert_title'            => esc_html__( 'Error Saving', 'gravitysmtp' ),
			// todo generic i18n for whole app
			'error_alert_generic_message'  => esc_html__( 'Could not save; please check your logs.', 'gravitysmtp' ),
			'error_alert_close_text'       => esc_html__( 'Close', 'gravitysmtp' ),
			'data_grid' => array(
				'top_heading'                               => esc_html__( 'Email Log', 'gravitysmtp' ),
				// 'top_content'                            => __( '', 'gravitysmtp' ), // removing text for now as it is redundant.
				'grid_heading'                              => esc_html__( 'Activity', 'gravitysmtp' ),
				'active_filters_label'                      => esc_html__( 'Filters:', 'gravitysmtp' ),
				'active_filters_reset'                      => esc_html__( 'Reset Filters', 'gravitysmtp' ),
				'bulk_select'                               => esc_html__( 'Select all rows', 'gravitysmtp' ),
				'clear_search_aria_label'                   => esc_html__( 'Clear search', 'gravitysmtp' ),
				/* translators: %s: date range */
				'date_filters_pill_label'                   => esc_html__( 'Date: %s', 'gravitysmtp' ),
				/* translators: 1: from date, 2: to date */
				'date_filters_trigger_aria_text'            => esc_html__( 'Date filters: %1$s to %2$s', 'gravitysmtp' ),
				'date_filters_reset'                        => esc_html__( 'Reset', 'gravitysmtp' ),
				'date_filters_today'                        => esc_html__( 'Today', 'gravitysmtp' ),
				'empty_title'                               => esc_html__( 'No emails yet', 'gravitysmtp' ),
				'empty_message'                             => esc_html__( 'As soon as your site sends some emails, you will see them here!', 'gravitysmtp' ),
				'grid_controls_bulk_actions_select_label'   => esc_html__( 'Select bulk actions', 'gravitysmtp' ),
				'grid_controls_bulk_actions_button_label'   => esc_html__( 'Apply', 'gravitysmtp' ),
				'grid_controls_search_placeholder'          => esc_html__( 'Search', 'gravitysmtp' ),
				'grid_controls_search_button_label'         => esc_html__( 'Search', 'gravitysmtp' ),
				/* translators: 1: number of selected entries. */
				'select_notice_selected_number_entries'     => esc_html__( 'All %1$s emails on this page are selected', 'gravitysmtp' ),
				/* translators: 1: number of selected entries. */
				'select_notice_selected_all_number_entries' => esc_html__( 'All %1$s emails in the email log are selected', 'gravitysmtp' ),
				/* translators: 1: number of entries to be selected. */
				'select_notice_select_all_number_entries'   => esc_html__( 'Select All %1$s Emails', 'gravitysmtp' ),
				'select_notice_clear_selection'             => esc_html__( 'Clear Selection', 'gravitysmtp' ),
				'simple_filters_droplist_reset'             => esc_html__( 'Reset', 'gravitysmtp' ),
				/* translators: %s: number of filters active. */
				'simple_filters_trigger_aria_text'          => esc_html__( 'Filters: %s filters active.', 'gravitysmtp' ),
				'pagination_next'                           => esc_html__( 'Next', 'gravitysmtp' ),
				'pagination_prev'                           => esc_html__( 'Previous', 'gravitysmtp' ),
				'pagination_next_aria_label'                => esc_html__( 'Next Page', 'gravitysmtp' ),
				'pagination_prev_aria_label'                => esc_html__( 'Previous Page', 'gravitysmtp' ),
				'search_no_results_title'                   => esc_html__( 'No results found', 'gravitysmtp' ),
				'search_no_results_message'                 => esc_html__( 'No results found for your search', 'gravitysmtp' ),
				/* translators: %s: search term */
				'search_pill_label'                         => esc_html__( 'Search: %s', 'gravitysmtp' ),
				'select_row'                                => esc_html__( 'Select row', 'gravitysmtp' ),
			),
			'debug_messages'               => array(
				/* translators: %1$s is the body of the ajax request. */
				'deleting_activity_log_rows'         => esc_html__( 'Deleting activity log rows: %1$s', 'gravitysmtp' ),
				/* translators: %1$s is the error. */
				'deleting_activity_log_rows_error'   => esc_html__( 'Error deleting activity log rows: %1$s', 'gravitysmtp' ),
				/* translators: %1$s is the body of the ajax request. */
				'deleting_single_activity_log'       => esc_html__( 'Deleting activity log: %1$s', 'gravitysmtp' ),
				/* translators: %1$s is the error. */
				'deleting_single_activity_log_error' => esc_html__( 'Error deleting activity log: %1$s', 'gravitysmtp' ),
				/* translators: %1$s is the body of the ajax request. */
				'fetching_activity_log_page'         => esc_html__( 'Fetching activity log page: %1$s', 'gravitysmtp' ),
				/* translators: %1$s is the error. */
				'fetching_activity_log_page_error'   => esc_html__( 'Error fetching activity log page: %1$s', 'gravitysmtp' ),
				/* translators: %1$s is the body of the ajax request. */
				'fetching_single_activity_log'       => esc_html__( 'Fetching activity log details: %1$s', 'gravitysmtp' ),
				/* translators: %1$s is the error. */
				'fetching_single_activity_log_error' => esc_html__( 'Error fetching activity log details: %1$s', 'gravitysmtp' ),
			),
			'confirm_delete_email_heading' => esc_html__( 'Delete Email', 'gravitysmtp' ),
			'confirm_delete_email_content' => esc_html__( 'Are you sure you want to delete this email log?', 'gravitysmtp' ),
			'confirm_delete_email_delete'  => esc_html__( 'Delete', 'gravitysmtp' ),
			'confirm_delete_email_cancel'  => esc_html__( 'Cancel', 'gravitysmtp' ),
			'confirm_resend_email_heading' => esc_html__( 'Resend Email', 'gravitysmtp' ),
			'confirm_resend_email_content' => esc_html__( 'Are you sure you want to resend this email?', 'gravitysmtp' ),
			'confirm_resend_email_resend'  => esc_html__( 'Resend', 'gravitysmtp' ),
			'confirm_resend_email_cancel'  => esc_html__( 'Cancel', 'gravitysmtp' ),
			'confirm_bulk_delete_heading'  => esc_html__( 'Confirm Deletion', 'gravitysmtp' ),
			/* translators: 1: number of selected entries. */
			'confirm_bulk_delete_content'  => esc_html__( 'Are you sure you want to delete %1$s entries? This action is irreversible, and all records will be permanently removed from the database.', 'gravitysmtp' ),
			'confirm_resend_email_heading' => esc_html__( 'Resend Email', 'gravitysmtp' ),
			'confirm_resend_email_content' => esc_html__( 'Are you sure you want to resend this email?', 'gravitysmtp' ),
			'confirm_resend_email_resend'  => esc_html__( 'Resend', 'gravitysmtp' ),
			'confirm_resend_email_cancel'  => esc_html__( 'Cancel', 'gravitysmtp' ),
		);
	}

	protected function get_connectors_list_items() {
		$connectors = Gravity_SMTP::container()->get( Connector_Service_Provider::CONNECTOR_DATA_MAP );
		$pill_label = esc_html__( '%1$s: %2$s', 'gravitysmtp' );

		$list_items = array();
		foreach ( $connectors as $connector_slug => $connector ) {
			if ( isset( $connector['data']['disabled'] ) && $connector['data']['disabled'] ) {
				continue;
			}

			$list_items[] = array(
				'key'   => "service-$connector_slug",
				'props' => array(
					'customAttributes' => array(
						'data-key'        => 'service',
						'data-value'      => $connector_slug,
						'data-pill-label' => sprintf( $pill_label, esc_html__( 'Service', 'gravitysmtp' ), $connector['title'] ),
						'id'              => "service-$connector_slug",
					),
					'element'          => 'button',
					'label'            => $connector['title'],
				),
			);
		}

		return $list_items;
	}

	protected function get_simple_filter_options() {
		/* translators: 1: label of filter key, 2: label of filter value. */
		$pill_label = esc_html__( '%1$s: %2$s', 'gravitysmtp' );

		return array(
			array(
				'key'               => 'status',
				'triggerAttributes' => array(
					'id'    => 'status',
					'label' => esc_html__( 'Status', 'gravitysmtp' ),
				),
				'listItems'         => array(
					array(
						'key'   => 'status-sent',
						'props' => array(
							'customAttributes' => array(
								'data-key'        => 'status',
								'data-value'      => 'sent',
								'data-pill-label' => sprintf( $pill_label, esc_html__( 'Status', 'gravitysmtp' ), esc_html__( 'Sent', 'gravitysmtp' ) ),
								'id'              => 'status-sent',
							),
							'element'          => 'button',
							'label'            => esc_html__( 'Sent', 'gravitysmtp' ),
						),
					),
					array(
						'key'   => 'status-failed',
						'props' => array(
							'customAttributes' => array(
								'data-key'        => 'status',
								'data-value'      => 'failed',
								'data-pill-label' => sprintf( $pill_label, esc_html__( 'Status', 'gravitysmtp' ), esc_html__( 'Failed', 'gravitysmtp' ) ),
								'id'              => 'status-failed',
							),
							'element'          => 'button',
							'label'            => esc_html__( 'Failed', 'gravitysmtp' ),
						),
					),
					array(
						'key'   => 'status-sandboxed',
						'props' => array(
							'customAttributes' => array(
								'data-key'        => 'status',
								'data-value'      => 'sandboxed',
								'data-pill-label' => sprintf( $pill_label, esc_html__( 'Status', 'gravitysmtp' ), esc_html__( 'Sandboxed', 'gravitysmtp' ) ),
								'id'              => 'status-sandboxed',
							),
							'element'          => 'button',
							'label'            => esc_html__( 'Sandboxed', 'gravitysmtp' ),
						),
					),
					array(
						'key'   => 'status-suppressed',
						'props' => array(
							'customAttributes' => array(
								'data-key'        => 'status',
								'data-value'      => 'suppressed',
								'data-pill-label' => sprintf( $pill_label, esc_html__( 'Status', 'gravitysmtp' ), esc_html__( 'Suppressed', 'gravitysmtp' ) ),
								'id'              => 'status-suppressed',
							),
							'element'          => 'button',
							'label'            => esc_html__( 'Suppressed', 'gravitysmtp' ),
						),
					),
					array(
						'key'   => 'status-pending',
						'props' => array(
							'customAttributes' => array(
								'data-key'        => 'status',
								'data-value'      => 'pending',
								'data-pill-label' => sprintf( $pill_label, esc_html__( 'Status', 'gravitysmtp' ), esc_html__( 'Pending', 'gravitysmtp' ) ),
								'id'              => 'status-pending',
							),
							'element'          => 'button',
							'label'            => esc_html__( 'Pending', 'gravitysmtp' ),
						),
					),
				),
			),
			array(
				'key'               => 'service',
				'triggerAttributes' => array(
					'id'    => 'service',
					'label' => esc_html__( 'Service', 'gravitysmtp' ),
				),
				'listItems'         => $this->get_connectors_list_items(),
			),
			array(
				'key'               => 'source',
				'triggerAttributes' => array(
					'id'    => 'source',
					'label' => esc_html__( 'Source', 'gravitysmtp' ),
				),
				'listItems' => $this->get_source_list_items( $pill_label ),
			),
		);
	}

	private function get_source_list_items( $pill_label ) {
		$transient = get_transient( self::SOURCE_LIST_ITEMS_TRANSIENT, array() );
		if ( ! empty( $transient ) ) {
			return $transient;
		}

		$emails  = Gravity_SMTP::container()->get( Connector_Service_Provider::EVENT_MODEL );
		$sources = $emails->get_all_sending_sources();
		$items   = array();

		foreach ( $sources as $source ) {
			$slug    = sanitize_title( $source );
			$items[] = array(
				'key'   => 'source-' . $slug,
				'props' => array(
					'customAttributes' => array(
						'data-key'        => 'source',
						'data-value'      => $source,
						'data-pill-label' => sprintf( $pill_label, esc_html__( 'Source', 'gravitysmtp' ), $source ),
						'id'              => 'source-' . $slug,
					),
					'element'          => 'button',
					'label'            => $source,
				),
			);
		}

		set_transient( self::SOURCE_LIST_ITEMS_TRANSIENT, $items );

		return $items;
	}

	public function get_log_data() {
		$emails      = Gravity_SMTP::container()->get( Connector_Service_Provider::EVENT_MODEL );
		$search_term = filter_input( INPUT_GET, 'search_term' );
		$search_type = filter_input( INPUT_GET, 'search_type' );
		$filters     = filter_input( INPUT_GET, 'filters' );

		$plugin_data_store = Gravity_SMTP::container()->get( Connector_Service_Provider::DATA_STORE_ROUTER );

		$open_tracking_enabled = $plugin_data_store->get_plugin_setting( Tracking_Service_Provider::SETTING_OPEN_TRACKING, 'false' );
		$open_tracking_enabled = Booliesh::get( $open_tracking_enabled );

		if ( ! empty( $search_term ) ) {
			$search_term = htmlspecialchars( $search_term );
		}

		if ( ! empty( $search_type ) ) {
			$search_type = htmlspecialchars( $search_type );
		}

		if ( ! empty( $filters ) ) {
			$filters = json_decode( base64_decode( $filters ), true );
			if ( ! is_array( $filters ) ) {
				$filters = array();
			}
		}

		$count = $emails->count( $search_term, $search_type, $filters );

		$opts     = Gravity_SMTP::container()->get( Connector_Service_Provider::DATA_STORE_ROUTER );
		$per_page = $opts->get_plugin_setting( Save_Plugin_Settings_Endpoint::PARAM_PER_PAGE, 20 );

		return array(
			'version'                  => GF_GRAVITY_SMTP_VERSION,
			'route_path'               => admin_url( 'admin.php' ),
			'ajax_url'                 => admin_url( 'admin-ajax.php' ),
			'ajax_grid_pagination_url' => trailingslashit( GF_GRAVITY_SMTP_PLUGIN_URL ) . 'includes/logging/endpoints/get-paginated-items.php',
			'base_url'                 => admin_url( 'admin.php?page=gravitysmtp-activity-log' ),
			'nav_item_param_key'       => 'tab',
			'open_tracking_enabled'    => Feature_Flag_Manager::is_enabled( 'email_open_tracking' ) && $open_tracking_enabled,
			'initial_row_count'        => $count,
			'initial_load_timestamp'   => current_time( 'mysql', true ),
			'rows_per_page'            => $per_page,
			'data_grid'                => array(
				'bulk_actions_options' => $this->get_bulk_actions(),
				'columns'              => $this->get_columns(),
				'column_style_props'   => $this->get_column_style_props(),
				'data'                 => array(
					'value'   => $this->get_data_rows(),
					'default' => $this->get_demo_data_rows(),
				),
				'simple_filter_options' => $this->get_simple_filter_options(),
			),
			'caps' => array(
				Roles::VIEW_EMAIL_LOG           => current_user_can( Roles::VIEW_EMAIL_LOG ),
				Roles::VIEW_EMAIL_LOG_DETAILS   => current_user_can( Roles::VIEW_EMAIL_LOG_DETAILS ),
				Roles::DELETE_EMAIL_LOG         => current_user_can( Roles::DELETE_EMAIL_LOG ),
				Roles::DELETE_EMAIL_LOG_DETAILS => current_user_can( Roles::DELETE_EMAIL_LOG_DETAILS ),
				Roles::VIEW_EMAIL_LOG_PREVIEW   => current_user_can( Roles::VIEW_EMAIL_LOG_PREVIEW ),
			),
		);
	}

	public function get_data_rows() {
		$emails            = Gravity_SMTP::container()->get( Connector_Service_Provider::EVENT_MODEL );
		$recipient_parser  = Gravity_SMTP::container()->get( Utils_Service_Provider::RECIPIENT_PARSER );
		$plugin_data_store = Gravity_SMTP::container()->get( Connector_Service_Provider::DATA_STORE_ROUTER );

		$open_tracking_enabled = $plugin_data_store->get_plugin_setting( Tracking_Service_Provider::SETTING_OPEN_TRACKING, 'false' );
		$open_tracking_enabled = Booliesh::get( $open_tracking_enabled );

		/**
		 * @var Data_Store_Router $opts
		 */
		$opts     = Gravity_SMTP::container()->get( Connector_Service_Provider::DATA_STORE_ROUTER );
		$per_page = $opts->get_plugin_setting( Save_Plugin_Settings_Endpoint::PARAM_PER_PAGE, 20 );

		$current_page = filter_input( INPUT_GET, 'log_page', FILTER_SANITIZE_NUMBER_INT );
		if ( empty( $current_page ) ) {
			$current_page = 1;
		}

		$search_term = filter_input( INPUT_GET, 'search_term' );
		$search_type = filter_input( INPUT_GET, 'search_type' );
		$filters     = filter_input( INPUT_GET, 'filters' );

		if ( ! empty( $search_term ) ) {
			$search_term = htmlspecialchars( $search_term );
		}

		if ( ! empty( $search_type ) ) {
			$search_type = htmlspecialchars( $search_type );
		}

		if ( ! empty( $filters ) ) {
			$filters = json_decode( base64_decode( $filters ), true );
			if ( ! is_array( $filters ) ) {
				$filters = array();
			}
		}

		$data = $emails->paginate( $current_page, $per_page, false, $search_term, $search_type, null, null, $filters );
		$rows = array();

		foreach ( $data as $row ) {
			$grid_actions = $this->get_grid_actions( $row );
			$extra        = strpos( $row['extra'], '{' ) === 0 ? json_decode( $row['extra'], true ) : unserialize( $row['extra'] );
			$to           = isset( $extra['to'] ) ? $extra['to'] : '';
			$to_address   = $recipient_parser->parse( $to )->first()->email();
			$more_count   = max( 0, $row['email_counts'] - 1 );

			$row_data = array(
				'id'      => $row['id'],
				'subject' => array(
					'component' => 'Button',
					'props'     => array(
						'action'        => 'view',
						'customClasses' => array( 'gravitysmtp-data-grid__subject' ),
						'label'         => $row['subject'],
						'type'          => 'unstyled',
						'data'          => array(
							'event_id' => $row['id'],
						),
						'disabled' => ! current_user_can( Roles::VIEW_EMAIL_LOG_DETAILS ),
					),
				),
				'status'  => array(
					'component' => 'StatusIndicator',
					'props'     => array(
						'label'  => Status_Enum::label( $row['status'] ),
						'status' => Status_Enum::indicator( $row['status'] ),
						'hasDot' => false,
					),
				),
				'to'      => array(
					'component'  => 'Box',
					'props'      => array(
						'customClasses' => array( 'gravitysmtp-activity-log-app__activity-log-table-recipient' ),
						'display'       => 'flex',
					),
					'components' => array(
						array(
							'component'  => 'Box',
							'props'      => array(
								'customClasses' => array( 'gravitysmtp-activity-log-app__activity-log-table-recipient-meta' ),
								'display'       => 'flex',
							),
							'components' => array(
								array(
									'component' => 'Gravatar',
									'props'     => array(
										'circular'      => true,
										'customClasses' => array( 'gravitysmtp-activity-log-app__activity-log-table-recipient-gravatar' ),
										'defaultImage'  => 'mp',
										'emailHash'     => hash( 'sha256', $to_address ),
										'height'        => 24,
										'width'         => 24,
									),
								),
								array(
									'component' => $more_count > 0 ? 'Text' : null,
									'props'     => array(
										'content'       => '+' . (string) $more_count,
										'customClasses' => array( 'gravitysmtp-activity-log-app__activity-log-table-recipient-more' ),
										'size'          => 'text-xxs',
									),
								),
							),
						),
						array(
							'component' => 'Text',
							'props'     => array(
								'content'       => $to_address,
								'customClasses' => array( 'gravitysmtp-activity-log-app__activity-log-table-recipient-email' ),
								'size'          => 'text-sm',
							),
						),
					),
				),
			);

			if ( Feature_Flag_Manager::is_enabled( 'email_open_tracking' ) && $open_tracking_enabled ) {
				$row_data['opened'] = array(
					'component' => 'Text',
					'props'     => array(
						'content' => $row['opened'],
						'size'    => 'text-sm',
					),
				);
			}

			$row_data['source'] = array(
				'component' => 'Text',
				'props'     => array(
					'content' => $row['source'],
					'size'    => 'text-sm',
				),
			);

			$row_data['integration'] = array(
				'external' => true,
				'key'      => $row['service'] . '_logo',
				'props'    => array(
					'height' => 24,
					'title' => Integration_Enum::svg_title( $row['service'] ),
					'width'  => 24,
				),
			);

			$row_data['date'] = array(
				'component' => 'Text',
				'props'     => array(
					'content' => $this->convert_dates_to_timezone( $row['date_updated'] ),
					'size'    => 'text-sm',
				),
			);

			$row_data['actions'] = $grid_actions;

			$rows[] = $row_data;
		}

		return $rows;
	}

	private function convert_dates_to_timezone( $date ) {
		$gmt_time   = new \DateTimeZone( 'UTC' );
		$local_time = new \DateTimeZone( wp_timezone_string() );
		$datetime   = new \DateTime( $date, $gmt_time );
		$datetime->setTimezone( $local_time );

		return $datetime->format( 'F d, Y \a\t h:ia' );
	}

	public function data() {
		$log_single_config = Gravity_SMTP::container()->get( App_Service_Provider::EMAIL_LOG_SINGLE_CONFIG );

		return array(
			'components' => array(
				'activity_log' => array(
					'data'      => array_merge( $this->get_log_data(), $log_single_config->get_log_single_data() ),
					'i18n'      => array_merge( $this->get_i18n(), $log_single_config->get_i18n() ),
					'endpoints' => array(),
				),
			),
		);
	}

}
