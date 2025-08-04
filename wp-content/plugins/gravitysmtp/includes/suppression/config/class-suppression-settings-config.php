<?php

namespace Gravity_Forms\Gravity_SMTP\Suppression\Config;

use Gravity_Forms\Gravity_SMTP\Apps\App_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Connectors\Endpoints\Save_Plugin_Settings_Endpoint;
use Gravity_Forms\Gravity_SMTP\Enums\Suppression_Reason_Enum;
use Gravity_Forms\Gravity_SMTP\Gravity_SMTP;
use Gravity_Forms\Gravity_SMTP\Handler\Endpoints\Resend_Email_Endpoint;
use Gravity_Forms\Gravity_SMTP\Suppression\Endpoints\Add_Suppressed_Emails_Endpoint;
use Gravity_Forms\Gravity_SMTP\Suppression\Endpoints\Reactivate_Suppressed_Emails_Endpoint;
use Gravity_Forms\Gravity_SMTP\Suppression\Suppression_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Users\Roles;
use Gravity_Forms\Gravity_Tools\Config;

class Suppression_Settings_Config extends Config {

	protected $script_to_localize = 'gravitysmtp_scripts_admin';
	protected $name               = 'gravitysmtp_admin_config';

	public function should_enqueue() {
		$page = filter_input( INPUT_GET, 'page' );

		if ( ! is_string( $page ) ) {
			return false;
		}

		$page = htmlspecialchars( $page );

		return $page === 'gravitysmtp-suppression';
	}

	public function data() {
		$opts     = Gravity_SMTP::container()->get( Connector_Service_Provider::DATA_STORE_ROUTER );
		$per_page = $opts->get_plugin_setting( Save_Plugin_Settings_Endpoint::PARAM_PER_PAGE, 20 );

		return array(
			'components' => array(
				'suppression' => array(
					'endpoints' => array(
						Add_Suppressed_Emails_Endpoint::ACTION_NAME        => array(
							'action' => array(
								'value'   => Add_Suppressed_Emails_Endpoint::ACTION_NAME,
								'default' => 'mock_endpoint',
							),
							'nonce'  => array(
								'value'   => wp_create_nonce( Add_Suppressed_Emails_Endpoint::ACTION_NAME ),
								'default' => 'nonce',
							),
						),
						Reactivate_Suppressed_Emails_Endpoint::ACTION_NAME => array(
							'action' => array(
								'value'   => Reactivate_Suppressed_Emails_Endpoint::ACTION_NAME,
								'default' => 'mock_endpoint',
							),
							'nonce'  => array(
								'value'   => wp_create_nonce( Reactivate_Suppressed_Emails_Endpoint::ACTION_NAME ),
								'default' => 'nonce',
							),
						),
						'suppressed_emails_page'                           => array(
							'action' => array(
								'value'   => 'suppressed_emails_page',
								'default' => 'mock_endpoint',
							),
							'nonce'  => array(
								'value'   => wp_create_nonce( 'suppressed_emails_page' ),
								'default' => 'nonce',
							),
						),
					),
					'i18n' => array(
						'suppression'    => array(
							'top_heading'                   => esc_html__( 'Suppressions', 'gravitysmtp' ),
							'top_content'                   => __( "Add specific email addresses to a blacklist to suppress send attempts to those recipients.", 'gravitysmtp' ),
							'data_grid'                     => array(
								'bulk_select'                               => esc_html__( 'Select all rows', 'gravitysmtp' ),
								'top_heading'                               => esc_html__( 'Suppressed Recipients', 'gravitysmtp' ),
								'clear_search_aria_label'                   => esc_html__( 'Clear search', 'gravitysmtp' ),
								'empty_title'                               => esc_html__( 'No suppressions', 'gravitysmtp' ),
								'empty_message'                             => esc_html__( 'You will see suppressions here when you set some up.', 'gravitysmtp' ),
								'grid_controls_search_button_label'         => esc_html__( 'Search', 'gravitysmtp' ),
								'grid_controls_search_placeholder'          => esc_html__( 'Search', 'gravitysmtp' ),
								'grid_controls_bulk_actions_select_label'   => esc_html__( 'Select bulk actions', 'gravitysmtp' ),
								'grid_controls_bulk_actions_button_label'   => esc_html__( 'Apply', 'gravitysmtp' ),
								/* translators: 1: number of selected entries. */
								'select_notice_selected_number_entries'     => esc_html__( 'All %1$s suppressions on this page are selected', 'gravitysmtp' ),
								/* translators: 1: number of selected entries. */
								'select_notice_selected_all_number_entries' => esc_html__( 'All %1$s suppressions are selected', 'gravitysmtp' ),
								/* translators: 1: number of entries to be selected. */
								'select_notice_select_all_number_entries'   => esc_html__( 'Select All %1$s Suppressions', 'gravitysmtp' ),
								'select_notice_clear_selection'             => esc_html__( 'Clear Selection', 'gravitysmtp' ),
								'pagination_next'                           => esc_html__( 'Next', 'gravitysmtp' ),
								'pagination_prev'                           => esc_html__( 'Previous', 'gravitysmtp' ),
								'pagination_next_aria_label'                => esc_html__( 'Next Page', 'gravitysmtp' ),
								'pagination_prev_aria_label'                => esc_html__( 'Previous Page', 'gravitysmtp' ),
								'search_no_results_title'                   => esc_html__( 'No results found', 'gravitysmtp' ),
								'search_no_results_message'                 => esc_html__( 'No results found for your search', 'gravitysmtp' ),
							),
							'dialog' => array(
								'add_note'                 => esc_html__( 'Add Note', 'gravitysmtp' ),
								'cancel'                   => esc_html__( 'Cancel', 'gravitysmtp' ),
								'confirm_add'              => esc_html__( 'Add Suppressed Recipients', 'gravitysmtp' ),
								'confirm_reactivate'       => esc_html__( 'Reactivate', 'gravitysmtp' ),
								'description_add'          => esc_html__( 'Recipients on the suppression list will not receive emails.', 'gravitysmtp' ),
								'email_addresses'          => esc_html__( 'Email Addresses', 'gravitysmtp' ),
								'heading_add'              => esc_html__( 'Add Recipients', 'gravitysmtp' ),
								'heading_reactivate'       => esc_html__( 'Reactivate', 'gravitysmtp' ),
								'manually_add'             => esc_html__( 'Manually Add', 'gravitysmtp' ),
								'mb_heading_email_address' => esc_html__( 'Email Address', 'gravitysmtp' ),
								'mb_heading_reason'        => esc_html__( 'Reason', 'gravitysmtp' ),
								'mb_heading_note'          => esc_html__( 'Note', 'gravitysmtp' ),
								'mb_heading_date'          => esc_html__( 'Date Suppressed', 'gravitysmtp' ),
							),
							'snackbar' => array(
								'emails_reactivated'             => esc_html__( 'Emails reactivated.', 'gravitysmtp' ),
								'emails_reactivated_error'       => esc_html__( 'Error reactivating emails.', 'gravitysmtp' ),
								'fetching_suppressions_error'    => esc_html__( 'Error getting suppressions for requested page.', 'gravitysmtp' ) ,
								'suppressions_added'             => esc_html__( 'Suppressions added.', 'gravitysmtp' ),
								'suppressions_added_error'       => esc_html__( 'Error adding suppressions.', 'gravitysmtp' ),
							),
						),
						'debug_messages' => array(
							/* translators: %s: body of the ajax request. */
							'adding_suppressed_emails'             => esc_html__( 'Adding suppressed emails: %s', 'gravitysmtp' ),
							/* translators: %s: error data. */
							'adding_suppressed_emails_error'       => esc_html__( 'Error adding suppressed emails: %s', 'gravitysmtp' ),
							/* translators: %s: body of the ajax request. */
							'fetching_suppressions_page'           => esc_html__( 'Fetching suppressions page: %1$s', 'gravitysmtp' ),
							/* translators: %s: error data. */
							'fetching_suppressions_page_error'     => esc_html__( 'Error fetching suppressions page: %1$s', 'gravitysmtp' ),
							/* translators: %s: body of the ajax request. */
							'reactivating_suppressed_emails'       => esc_html__( 'Reactivating suppressed emails: %s', 'gravitysmtp' ),
							/* translators: %s: error data. */
							'reactivating_suppressed_emails_error' => esc_html__( 'Error reactivating suppressed emails: %s', 'gravitysmtp' ),

						),
					),
					'data'      => array(
						'caps'              => array(
							Roles::VIEW_EMAIL_SUPPRESSION_SETTINGS => current_user_can( Roles::VIEW_EMAIL_SUPPRESSION_SETTINGS ),
							Roles::EDIT_EMAIL_SUPPRESSION_SETTINGS => current_user_can( Roles::EDIT_EMAIL_SUPPRESSION_SETTINGS ),
						),
						'suppressed_emails' => array(
							'ajax_grid_pagination_url' => trailingslashit( GF_GRAVITY_SMTP_PLUGIN_URL ) . 'includes/suppression/endpoints/get-paginated-items.php',
							'bulk_actions_options'     => $this->get_suppression_bulk_actions(),
							'columns'                  => $this->get_suppression_columns(),
							'column_style_props'       => $this->get_suppression_column_style_props(),
							'initial_row_count'        => $this->get_suppression_data_row_count(),
							'initial_load_timestamp'   => current_time( 'mysql', true ),
							'rows_per_page'            => $per_page,
							'data'                     => array(
								'value'   => $this->get_suppression_data_rows(),
								'default' => array(),
							),
						),
					)
				),
			),
		);
	}

	public function get_suppression_bulk_actions() {
		return array(
			array(
				'label' => esc_html__( 'Bulk Actions', 'gravitysmtp' ),
				'value' => '-1',
			),
			array(
				'label' => esc_html__( 'Reactivate', 'gravitysmtp' ),
				'value' => 'reactivate',
			),
		);
	}

	public function get_suppression_columns() {
		$columns = array(
			array(
				'component'       => 'Text',
				'hideWhenLoading' => true,
				'key'             => 'email',
				'props'           => array(
					'content' => esc_html__( 'Email', 'gravitysmtp' ),
					'size'    => 'text-sm',
					'weight'  => 'medium',
				),
				'sortable'        => true,
				'variableLoader'  => true,
			),
			array(
				'component'       => 'Text',
				'hideWhenLoading' => true,
				'key'             => 'reason',
				'props'           => array(
					'content' => esc_html__( 'Reason', 'gravitysmtp' ),
					'size'    => 'text-sm',
					'weight'  => 'medium',
				),
				'sortable'        => true,
			),
			array(
				'component'       => 'Text',
				'hideAt'          => 640,
				'hideWhenLoading' => false,
				'key'             => 'date',
				'props'           => array(
					'content' => esc_html__( 'Date Suppressed', 'gravitysmtp' ),
					'size'    => 'text-sm',
					'weight'  => 'medium',
				),
				'sortable'        => true,
			),
			array(
				'component' => 'Text',
				'hideAt'    => 640,
				'key'       => 'actions',
				'props'     => array(
					'content' => esc_html__( 'Actions', 'gravitysmtp' ),
					'size'    => 'text-sm',
					'weight'  => 'medium',
				),
			),
		);

		return apply_filters( 'gravitysmtp_email_suppression_columns', $columns );
	}

	public function get_suppression_column_style_props() {
		$props = array(
			'email'   => array( 'flexBasis' => '292px' ),
			'reason'  => array( 'flex' => '0 0 200px' ),
			'date'    => array( 'flexBasis' => '250px' ),
			'actions' => array( 'flex' => '0 0 130px' ),
		);

		return apply_filters( 'gravitysmtp_email_suppression_column_style_props', $props );
	}

	public function get_suppression_data_rows() {
		$opts     = Gravity_SMTP::container()->get( Connector_Service_Provider::DATA_STORE_ROUTER );
		$per_page = $opts->get_plugin_setting( Save_Plugin_Settings_Endpoint::PARAM_PER_PAGE, 20 );

		$current_page = filter_input( INPUT_GET, 'log_page', FILTER_SANITIZE_NUMBER_INT );
		$search_term  = filter_input( INPUT_GET, 'search_term' );
		if ( empty( $current_page ) ) {
			$current_page = 1;
		}

		$suppressed_emails = Gravity_SMTP::$container->get( Suppression_Service_Provider::SUPPRESSED_EMAILS_MODEL );
		$data              = $suppressed_emails->paginate( $current_page, $per_page, $search_term, null, null);

		return $suppressed_emails->format_as_data_rows( $data );
	}

	public function get_suppression_data_row_count() {
		$suppressed_emails = Gravity_SMTP::$container->get( Suppression_Service_Provider::SUPPRESSED_EMAILS_MODEL );
		return $suppressed_emails->count();
	}

}
