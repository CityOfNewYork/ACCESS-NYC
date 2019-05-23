<?php

class WPML_TM_Upgrade_Loader implements IWPML_Action {

	/** @var SitePress */
	private $sitepress;

	/** @var WPML_Upgrade_Schema */
	private $upgrade_schema;

	/** @var WPML_Settings_Helper */
	private $settings;

	/** @var WPML_Upgrade_Command_Factory */
	private $factory;

	/** @var WPML_Notices */
	private $notices;

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

	public function add_hooks() {
		add_action( 'init', array( $this, 'wpml_tm_upgrade' ) );
	}

	public function wpml_tm_upgrade() {

		$commands = array(
			$this->factory->create_command_definition( 'WPML_TM_Upgrade_Translation_Priorities_For_Posts', array(), array( 'admin', 'ajax', 'front-end' ) ),

			$this->factory->create_command_definition(
				'WPML_TM_Upgrade_Default_Editor_For_Old_Jobs',
				array( $this->sitepress ),
				array( 'admin', 'ajax', 'front-end' )
			),

			$this->factory->create_command_definition(
				'WPML_TM_Upgrade_Service_Redirect_To_Field',
				array(),
				array( 'admin' )
			),

			$this->factory->create_command_definition( 'WPML_TM_Add_TP_ID_Column_To_Translation_Status', array( $this->upgrade_schema ), array( 'admin', 'ajax', 'front-end' ) ),
			$this->factory->create_command_definition( 'WPML_TM_Add_TP_Revision_And_TS_Status_Columns_To_Translation_Status', array( $this->upgrade_schema ), array( 'admin', 'ajax', 'front-end' ) ),
			$this->factory->create_command_definition( 'WPML_TM_Add_TP_Revision_And_TS_Status_Columns_To_Core_Status', array( $this->upgrade_schema ), array( 'admin', 'ajax', 'front-end' ) ),
			$this->factory->create_command_definition( 'WPML_TM_Upgrade_WPML_Site_ID_ATE', array( $this->upgrade_schema ), array( 'admin' ) ),
			$this->factory->create_command_definition(
				'WPML_TM_Upgrade_Cancel_Orphan_Jobs',
				array( new WPML_TP_Sync_Orphan_Jobs_Factory(), new WPML_TM_Jobs_Migration_State() ), array( 'admin' )
			),
			$this->factory->create_command_definition( 'WPML_TM_Upgrade_ATE_Jobs_Cleanup', array(), array( 'admin' ) ),
		);

		$upgrade = new WPML_Upgrade( $commands, $this->sitepress, $this->factory );
		$upgrade->run();
	}
}
