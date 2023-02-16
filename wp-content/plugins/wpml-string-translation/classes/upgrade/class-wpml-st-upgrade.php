<?php
/**
 * WPML_ST_Upgrade class file.
 *
 * @package wpml-string-translation
 */

/**
 * Class WPML_ST_Upgrade
 */
class WPML_ST_Upgrade {

	const TRANSIENT_UPGRADE_IN_PROGRESS = 'wpml_st_upgrade_in_progress';

	/**
	 * SitePress instance.
	 *
	 * @var SitePress $sitepress
	 */
	private $sitepress;

	/**
	 * Upgrade Command Factory instance.
	 *
	 * @var  WPML_ST_Upgrade_Command_Factory
	 */
	private $command_factory;

	/**
	 * Upgrade in progress flag.
	 *
	 * @var bool $upgrade_in_progress
	 */
	private $upgrade_in_progress;

	/**
	 * WPML_ST_Upgrade constructor.
	 *
	 * @param SitePress                            $sitepress SitePress instance.
	 * @param WPML_ST_Upgrade_Command_Factory|null $command_factory Upgrade Command Factory instance.
	 */
	public function __construct( SitePress $sitepress, WPML_ST_Upgrade_Command_Factory $command_factory = null ) {
		$this->sitepress       = $sitepress;
		$this->command_factory = $command_factory;
	}

	/**
	 * Run upgrade.
	 */
	public function run() {
		if ( get_transient( self::TRANSIENT_UPGRADE_IN_PROGRESS ) ) {
			return;
		}

		if ( $this->sitepress->get_wp_api()->is_admin() ) {
			if ( $this->sitepress->get_wp_api()->constant( 'DOING_AJAX' ) ) {
				$this->run_ajax();
			} else {
				$this->run_admin();
			}
		} else {
			$this->run_front_end();
		}

		$this->set_upgrade_completed();
	}

	/**
	 * Run admin.
	 */
	private function run_admin() {
		$this->maybe_run( 'WPML_ST_Upgrade_Migrate_Originals' );
		$this->maybe_run( 'WPML_ST_Upgrade_Display_Strings_Scan_Notices' );
		$this->maybe_run( 'WPML_ST_Upgrade_DB_String_Packages' );
		$this->maybe_run( 'WPML_ST_Upgrade_MO_Scanning' );
		$this->maybe_run( 'WPML_ST_Upgrade_DB_String_Name_Index' );
		$this->maybe_run( 'WPML_ST_Upgrade_DB_Longtext_String_Value' );
		$this->maybe_run( 'WPML_ST_Upgrade_DB_Strings_Add_Translation_Priority_Field' );
		$this->maybe_run( 'WPML_ST_Upgrade_DB_String_Packages_Word_Count' );
		$this->maybe_run( '\WPML\ST\Upgrade\Command\RegenerateMoFilesWithStringNames' );
		$this->maybe_run( \WPML\ST\Upgrade\Command\MigrateMultilingualWidgets::class );
	}

	/**
	 * Run ajax.
	 */
	private function run_ajax() {
		$this->maybe_run_ajax( 'WPML_ST_Upgrade_Migrate_Originals' );

		// It has to be maybe_run.
		$this->maybe_run( 'WPML_ST_Upgrade_MO_Scanning' );
		$this->maybe_run( 'WPML_ST_Upgrade_DB_String_Packages_Word_Count' );
	}

	/**
	 * Run on frontend.
	 */
	private function run_front_end() {
		$this->maybe_run( 'WPML_ST_Upgrade_MO_Scanning' );
		$this->maybe_run( 'WPML_ST_Upgrade_DB_String_Packages_Word_Count' );
	}

	/**
	 * Maybe run command.
	 *
	 * @param string $class Command class name.
	 */
	private function maybe_run( $class ) {
		if ( ! $this->has_command_been_executed( $class ) ) {
			$this->set_upgrade_in_progress();
			$upgrade = $this->command_factory->create( $class );
			if ( $upgrade->run() ) {
				$this->mark_command_as_executed( $class );
			}
		}
	}

	/**
	 * Maybe run command in ajax.
	 *
	 * @param string $class Command class name.
	 */
	private function maybe_run_ajax( $class ) {
		if ( ! $this->has_command_been_executed( $class ) ) {
			$this->run_ajax_command( $class );
		}
	}

	/**
	 * Run command in ajax.
	 *
	 * @param string $class Command class name.
	 */
	private function run_ajax_command( $class ) {
		if ( $this->nonce_ok( $class ) ) {
			$upgrade = $this->command_factory->create( $class );
			if ( $upgrade->run_ajax() ) {
				$this->mark_command_as_executed( $class );
				$this->sitepress->get_wp_api()->wp_send_json_success( '' );
			}
		}
	}

	/**
	 * Check nonce.
	 *
	 * @param string $class Command class name.
	 *
	 * @return bool
	 */
	private function nonce_ok( $class ) {
		$ok = false;

		$class = strtolower( $class );
		$class = str_replace( '_', '-', $class );
		if ( isset( $_POST['action'] ) && $_POST['action'] === $class ) {
			$nonce = $this->filter_nonce_parameter();
			if ( $this->sitepress->get_wp_api()->wp_verify_nonce( $nonce, $class . '-nonce' ) ) {
				$ok = true;
			}
		}

		return $ok;
	}

	/**
	 * Check if command was executed.
	 *
	 * @param string $class Command class name.
	 *
	 * @return bool
	 */
	public function has_command_been_executed( $class ) {
		$id       = call_user_func( [ $class, 'get_command_id' ] );
		$settings = $this->sitepress->get_setting( 'st', [] );

		return isset( $settings[ $id . '_has_run' ] );
	}

	/**
	 * Mark command as executed.
	 *
	 * @param string $class Command class name.
	 */
	public function mark_command_as_executed( $class ) {
		$id                           = call_user_func( [ $class, 'get_command_id' ] );
		$settings                     = $this->sitepress->get_setting( 'st', [] );
		$settings[ $id . '_has_run' ] = true;
		$this->sitepress->set_setting( 'st', $settings, true );
		wp_cache_flush();
	}

	/**
	 * Filter nonce.
	 *
	 * @return mixed
	 */
	protected function filter_nonce_parameter() {
		return filter_input( INPUT_POST, 'nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
	}

	/**
	 * Set flag that upgrade is in process.
	 */
	private function set_upgrade_in_progress() {
		if ( ! $this->upgrade_in_progress ) {
			$this->upgrade_in_progress = true;
			set_transient( self::TRANSIENT_UPGRADE_IN_PROGRESS, true, MINUTE_IN_SECONDS );
		}
	}

	/**
	 * Mark upgrade as completed.
	 */
	private function set_upgrade_completed() {
		if ( $this->upgrade_in_progress ) {
			$this->upgrade_in_progress = false;
			delete_transient( self::TRANSIENT_UPGRADE_IN_PROGRESS );
		}
	}
}
