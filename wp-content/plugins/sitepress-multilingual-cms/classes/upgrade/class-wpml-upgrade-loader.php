<?php
/**
 * WPML_Upgrade_Loader class file.
 *
 * @package WPML
 */

use WPML\Upgrade\Commands\AddContextIndexToStrings;
use WPML\Upgrade\Commands\AddStatusIndexToStringTranslations;
use WPML\Upgrade\Commands\AddStringPackageIdIndexToStrings;
use WPML\Upgrade\Command\DisableOptionsAutoloading;
use WPML\Upgrade\Commands\RemoveRestDisabledNotice;

/**
 * Class WPML_Upgrade_Loader
 */
class WPML_Upgrade_Loader implements IWPML_Action {

	const TRANSIENT_UPGRADE_IN_PROGRESS = 'wpml_core_update_in_progress';

	/**
	 * SitePress instance.
	 *
	 * @var SitePress
	 */
	private $sitepress;

	/**
	 * Upgrade Schema instance.
	 *
	 * @var WPML_Upgrade_Schema
	 */
	private $upgrade_schema;

	/**
	 * Settings Helper instance.
	 *
	 * @var WPML_Settings_Helper
	 */
	private $settings;

	/**
	 * Upgrade Command Factory instance.
	 *
	 * @var WPML_Upgrade_Command_Factory
	 */
	private $factory;

	/**
	 * Notices instance.
	 *
	 * @var WPML_Notices
	 */
	private $notices;

	/**
	 * WPML_Upgrade_Loader constructor.
	 *
	 * @param SitePress                    $sitepress      SitePress instance.
	 * @param WPML_Upgrade_Schema          $upgrade_schema Upgrade schema instance.
	 * @param WPML_Settings_Helper         $settings       Settings Helper instance.
	 * @param WPML_Notices                 $wpml_notices   Notices instance.
	 * @param WPML_Upgrade_Command_Factory $factory        Upgrade Command Factory instance.
	 */
	public function __construct(
		SitePress $sitepress,
		WPML_Upgrade_Schema $upgrade_schema,
		WPML_Settings_Helper $settings,
		WPML_Notices $wpml_notices,
		WPML_Upgrade_Command_Factory $factory
	) {
		$this->sitepress      = $sitepress;
		$this->upgrade_schema = $upgrade_schema;
		$this->settings       = $settings;
		$this->notices        = $wpml_notices;
		$this->factory        = $factory;
	}

	/**
	 * Add hooks.
	 */
	public function add_hooks() {
		add_action( 'wpml_loaded', array( $this, 'wpml_upgrade' ) );
	}

	/**
	 * Upgrade WPML plugin.
	 */
	public function wpml_upgrade() {
		if ( get_transient( self::TRANSIENT_UPGRADE_IN_PROGRESS ) ) {
			return;
		}

		$commands = [
			$this->factory->create_command_definition( 'WPML_Upgrade_Localization_Files', [ $this->sitepress ], [ 'admin' ] ),
			$this->factory->create_command_definition( 'WPML_Upgrade_Fix_Non_Admin_With_Admin_Cap', [], [ 'admin' ] ),
			$this->factory->create_command_definition( 'WPML_Upgrade_Table_Translate_Job_For_3_9_0', [ $this->upgrade_schema ], [ 'admin', 'ajax', 'front-end' ] ),
			$this->factory->create_command_definition( 'WPML_Upgrade_Remove_Translation_Services_Transient', [], [ 'admin' ] ),
			$this->factory->create_command_definition( 'WPML_Upgrade_Display_Mode_For_Posts', [ $this->sitepress, $this->settings, $this->notices ], [ 'admin', 'ajax' ] ),
			$this->factory->create_command_definition( 'WPML_Add_UUID_Column_To_Translation_Status', [ $this->upgrade_schema ], [ 'admin', 'ajax', 'front-end' ] ),
			$this->factory->create_command_definition( 'WPML_Upgrade_Element_Type_Length_And_Collation', [ $this->upgrade_schema ], [ 'admin', 'ajax', 'front-end' ] ),
			$this->factory->create_command_definition( 'WPML_Upgrade_Add_Word_Count_Column_To_Strings', [ $this->upgrade_schema ], [ 'admin', 'ajax', 'front-end' ] ),
			$this->factory->create_command_definition( 'WPML_Upgrade_Media_Without_Language', [ $this->upgrade_schema->get_wpdb(), $this->sitepress->get_default_language() ], [ 'admin', 'ajax' ] ),
			$this->factory->create_command_definition( 'WPML_Upgrade_Media_Duplication_In_Core', [ $this->sitepress, $this->upgrade_schema, $this->notices ], [ 'admin', 'ajax' ] ),
			$this->factory->create_command_definition( 'WPML_Upgrade_Chinese_Flags', [ 'wpdb' => $this->sitepress->wpdb() ], [ 'admin' ] ),
			$this->factory->create_command_definition( 'WPML_Upgrade_Add_Editor_Column_To_Icl_Translate_Job', [ $this->upgrade_schema ], [ 'admin', 'ajax', 'front-end' ] ),
			$this->factory->create_command_definition( 'WPML_Upgrade_WPML_Site_ID', [], [ 'admin' ] ),
			$this->factory->create_command_definition( 'WPML_Upgrade_WPML_Site_ID_Remaining', [], [ 'admin' ] ),
			$this->factory->create_command_definition( 'WPML_Upgrade_Add_Location_Column_To_Strings', [ $this->upgrade_schema ], [ 'admin', 'ajax', 'front-end' ] ),
			$this->factory->create_command_definition( 'WPML_Upgrade_Add_Wrap_Column_To_Translate', [ $this->upgrade_schema ], [ 'admin', 'ajax', 'front-end' ] ),
			$this->factory->create_command_definition( 'WPML_Upgrade_Add_Wrap_Column_To_Strings', [ $this->upgrade_schema ], [ 'admin', 'ajax', 'front-end' ] ),
			$this->factory->create_command_definition( AddContextIndexToStrings::class, array( $this->upgrade_schema ), array( 'admin', 'ajax', 'front-end' ) ),
			$this->factory->create_command_definition( AddStatusIndexToStringTranslations::class, array( $this->upgrade_schema ), array( 'admin', 'ajax', 'front-end' ) ),
			$this->factory->create_command_definition( AddStringPackageIdIndexToStrings::class, array( $this->upgrade_schema ), array( 'admin', 'ajax', 'front-end' ) ),
			$this->factory->create_command_definition( DisableOptionsAutoloading::class, [], [ 'admin' ] ),
			$this->factory->create_command_definition( RemoveRestDisabledNotice::class, [], [ 'admin' ] ),
		];

		$upgrade = new WPML_Upgrade( $commands, $this->sitepress, $this->factory );
		$upgrade->run();
	}
}
