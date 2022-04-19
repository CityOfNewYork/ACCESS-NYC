<?php
namespace GatherContent\Importer;

class General extends Base {

	protected static $single_instance = null;

	/**
	 * GatherContent\Importer\Debug instance
	 *
	 * @var GatherContent\Importer\Debug
	 */
	protected $debug;

	/**
	 * GatherContent\Importer\API instance
	 *
	 * @var GatherContent\Importer\API
	 */
	protected $api;

	/**
	 * GatherContent\Importer\Admin\Admin instance
	 *
	 * @var GatherContent\Importer\Admin\Admin
	 */
	protected $admin;

	/**
	 * GatherContent\Importer\Admin\Support instance
	 *
	 * @var GatherContent\Importer\Admin\Support
	 */
	protected $support;

	/**
	 * GatherContent\Importer\importer Sync\Pull instance
	 *
	 * @var GatherContent\Importer\importer Sync\Pull
	 */
	protected $pull;

	/**
	 * GatherContent\Importer\importer Sync\Push instance
	 *
	 * @var GatherContent\Importer\importer Sync\Push
	 */
	protected $push;

	/**
	 * GatherContent\Importer\Select2_Ajax_Handler instance
	 *
	 * @var GatherContent\Importer\Select2_Ajax_Handler
	 */
	protected $ajax_handler;

	/**
	 * GatherContent\Importer\Admin\Bulk instance
	 *
	 * @var GatherContent\Importer\Admin\Bulk
	 */
	protected $bulk_ui;

	/**
	 * GatherContent\Importer\Admin\Single instance
	 *
	 * @var GatherContent\Importer\Admin\Single
	 */
	protected $single_ui;

	/**
	 * GatherContent\Importer\Compatibility\ACF instance
	 *
	 * @var GatherContent\Importer\Compatibility\ACF
	 */
	protected $compatibility_acf;

	/**
	 * GatherContent\Importer\Compatibility\WPML instance
	 *
	 * @var GatherContent\Importer\Compatibility\WPML
	 */
	protected $compatibility_wml;

	const OPTION_NAME = 'gathercontent_importer';

	/**
	 * Creates or returns an instance of this class.
	 * @since  3.0.0
	 * @return General A single instance of this class.
	 */
	public static function get_instance() {
		if ( null === self::$single_instance ) {
			self::$single_instance = new self();
		}

		return self::$single_instance;
	}

	protected function __construct() {
		parent::__construct( $_GET, $_POST );
		new Utils;

		$this->api   = new API( _wp_http_get_object() );
		$this->admin = new Admin\Admin( $this->api );
		$this->support = new Admin\Support();
		$this->debug = new Debug( $this->admin );
		$this->pull  = new Sync\Pull( $this->api );
		$this->push  = new Sync\Push( $this->api );
		$this->ajax_handler = new Admin\Ajax\Handlers( $this->api );
		if ( isset( $this->admin->mapping_wizard->mappings ) ) {
			$this->bulk_ui = new Admin\Bulk(
				$this->api,
				$this->admin->mapping_wizard
			);
			$this->single_ui = new Admin\Single(
				$this->api,
				$this->admin->mapping_wizard
			);
		}

		if ( class_exists( 'acf' ) ) {
			$this->compatibility_acf  = new Compatibility\ACF();
		}

		if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
			$this->compatibility_wml  = new Compatibility\WPML();
		}
	}

	/**
	 * Initiate plugins_loaded hooks.
	 *
	 * @since  3.0.2
	 *
	 * @return void
	 */
	public static function init_plugins_loaded_hooks() {
		Sync\Pull::init_plugins_loaded_hooks();
	}

	/**
	 * Initiate admin hooks
	 *
	 * @since  3.0.0
	 *
	 * @return void
	 */
	public function init_hooks() {
		$this->admin->init_hooks();
		$this->support->init_hooks();
		$this->pull->init_hooks();
		$this->push->init_hooks();
		$this->ajax_handler->init_hooks();
		if ( $this->bulk_ui ) {
			$this->bulk_ui->init_hooks();
			$this->single_ui->init_hooks();
		}

		$this->debug->init_hooks();

		if ( class_exists( 'acf' ) ) {
			$this->compatibility_acf->init_hooks();
		}

		if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
			$this->compatibility_wml->init_hooks();
		}
	}

	/**
	 * Magic getter for our object, to make protected properties accessible.
	 * @param string $field
	 * @return mixed
	 */
	public function __get( $field ) {
		return $this->{$field};
	}

}

