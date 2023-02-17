<?php
/**
 * WPML_ST_Upgrade_Command_Factory class file.
 *
 * @package wpml-string-translation
 */

use function WPML\Container\make;
use WPML\ST\Upgrade\Command\RegenerateMoFilesWithStringNames;
use WPML\ST\Upgrade\Command\MigrateMultilingualWidgets;

/**
 * Class WPML_ST_Upgrade_Command_Factory
 */
class WPML_ST_Upgrade_Command_Factory {
	/**
	 * WP db instance.
	 *
	 * @var wpdb wpdb
	 */
	private $wpdb;

	/**
	 * SitePress instance.
	 *
	 * @var SitePress
	 */
	private $sitepress;

	/**
	 * WPML_ST_Upgrade_Command_Factory constructor.
	 *
	 * @param wpdb      $wpdb WP db instance.
	 * @param SitePress $sitepress SitePress instance.
	 */
	public function __construct( wpdb $wpdb, SitePress $sitepress ) {
		$this->wpdb      = $wpdb;
		$this->sitepress = $sitepress;
	}

	/**
	 * Create upgrade commands.
	 *
	 * @param string $class_name Name of upgrade command class.
	 *
	 * @throws WPML_ST_Upgrade_Command_Not_Found_Exception Exception when command not found.
	 * @return IWPML_St_Upgrade_Command
	 */
	public function create( $class_name ) {
		switch ( $class_name ) {
			case 'WPML_ST_Upgrade_Migrate_Originals':
				$result = new WPML_ST_Upgrade_Migrate_Originals( $this->wpdb, $this->sitepress );
				break;
			case 'WPML_ST_Upgrade_Display_Strings_Scan_Notices':
				$themes_and_plugins_settings = new WPML_ST_Themes_And_Plugins_Settings();
				$result                      = new WPML_ST_Upgrade_Display_Strings_Scan_Notices( $themes_and_plugins_settings );
				break;
			case 'WPML_ST_Upgrade_DB_String_Packages':
				$result = new WPML_ST_Upgrade_DB_String_Packages( $this->wpdb );
				break;
			case 'WPML_ST_Upgrade_MO_Scanning':
				$result = new WPML_ST_Upgrade_MO_Scanning( $this->wpdb );
				break;
			case 'WPML_ST_Upgrade_DB_String_Name_Index':
				$result = new WPML_ST_Upgrade_DB_String_Name_Index( $this->wpdb );
				break;
			case 'WPML_ST_Upgrade_DB_Longtext_String_Value':
				$result = new WPML_ST_Upgrade_DB_Longtext_String_Value( $this->wpdb );
				break;
			case 'WPML_ST_Upgrade_DB_Strings_Add_Translation_Priority_Field':
				$result = new WPML_ST_Upgrade_DB_Strings_Add_Translation_Priority_Field( $this->wpdb );
				break;
			case 'WPML_ST_Upgrade_DB_String_Packages_Word_Count':
				$result = new WPML_ST_Upgrade_DB_String_Packages_Word_Count( wpml_get_upgrade_schema() );
				break;
			case '\WPML\ST\Upgrade\Command\RegenerateMoFilesWithStringNames':
				$isBackground = true;
				$result       = new RegenerateMoFilesWithStringNames(
					\WPML\ST\MO\Generate\Process\ProcessFactory::createStatus( $isBackground ),
					\WPML\ST\MO\Generate\Process\ProcessFactory::createSingle( $isBackground )
				);
				break;
			case MigrateMultilingualWidgets::class:
				$result = new MigrateMultilingualWidgets();
				break;
			default:
				throw new WPML_ST_Upgrade_Command_Not_Found_Exception( $class_name );
		}

		return $result;
	}
}
