<?php

class OTGS_Installer_WP_Components_Hooks {

	const EVENT_SEND_COMPONENTS_MONTHLY = 'otgs_send_components_data';
	const EVENT_SEND_COMPONENTS_AFTER_REGISTRATION = 'otgs_send_components_data_on_product_registration';
	const REPORT_SCHEDULING_PERIOD = '+1 month';
	const MONTHLY_CRON = 'monthly';

	/**
	 * @var OTGS_Installer_WP_Components_Storage
	 */
	private $storage;

	/**
	 * @var OTGS_Installer_WP_Components_Sender
	 */
	private $sender;

	/**
	 * @var OTGS_Installer_WP_Share_Local_Components_Setting
	 */
	private $setting;

	/**
	 * @var OTGS_Installer_PHP_Functions
	 */
	private $php_functions;

	public function __construct(
		OTGS_Installer_WP_Components_Storage $storage,
		OTGS_Installer_WP_Components_Sender $sender,
		OTGS_Installer_WP_Share_Local_Components_Setting $setting,
		OTGS_Installer_PHP_Functions $php_functions
	) {
		$this->storage       = $storage;
		$this->sender        = $sender;
		$this->setting       = $setting;
		$this->php_functions = $php_functions;
	}

	public function add_hooks() {
		add_action( 'wp_ajax_end_user_get_info', array( $this, 'process_report_instantly' ) );
		add_action( 'wp_ajax_' . OTGS_Installer_WP_Components_Setting_Ajax::AJAX_ACTION, array( $this, 'force_send_components_data' ), OTGS_Installer_WP_Components_Setting_Ajax::SAVE_SETTING_PRIORITY + 1 );
		add_action( self::EVENT_SEND_COMPONENTS_MONTHLY, array( $this, 'send_components_data' ) );
		add_action( self::EVENT_SEND_COMPONENTS_AFTER_REGISTRATION, array( $this, 'send_components_data' ) );
		add_action( 'init', array( $this, 'schedule_components_report' ) );

		add_filter( 'cron_schedules', array( $this, 'custom_monthly_cron_schedule' ) );
	}

	public function schedule_components_report() {
		if ( ! wp_next_scheduled( self::EVENT_SEND_COMPONENTS_MONTHLY ) ) {
			wp_schedule_event( strtotime( self::REPORT_SCHEDULING_PERIOD ), self::MONTHLY_CRON, self::EVENT_SEND_COMPONENTS_MONTHLY );
		}
	}

	public function process_report_instantly() {
		$this->storage->refresh_cache();
		$this->sender->send( $this->storage->get(), true );
	}

	public function force_send_components_data() {
		$this->storage->refresh_cache();
		$this->sender->send( $this->storage->get() );
	}

	public function send_components_data() {
		if ( $this->storage->is_outdated() ) {
			$this->storage->refresh_cache();
			$this->sender->send( $this->storage->get() );
		}
	}

	/**
	 * @return array {
	 *     The array of cron schedules keyed by the schedule name.
	 *
	 *     @type int $interval The schedule interval in seconds.
	 *     @type string $display The schedule display name.
	 * }
	 */
	public function custom_monthly_cron_schedule( $schedules ) {
		$schedules[self::MONTHLY_CRON] = array(
			'interval' => 2592000, // 30 days in seconds
			'display' => __( 'Monthly', 'sitepress' )
		);
		return $schedules;
	}
}