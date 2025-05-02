<?php

namespace WPML\ST\Upgrade\Command;

use WPML\StringTranslation\Application\Setting\Repository\SettingsRepositoryInterface;
use WPML\ST\MO\Hooks\PreloadThemeMoFile;

/**
 * Class UpgradeAutoregisteringStrings
 */
class UpgradeAutoregisteringStrings implements \IWPML_St_Upgrade_Command {

	/**
	 * @var \wpdb wpdb
	 */
	private $wpdb;

	/**
	 * @var \SitePress $sitepress
	 */
	private $sitepress;

	/**
	 * UpgradeAutoregisteringStrings constructor.
	 *
	 * @param \wpdb      $wpdb
	 * @param \SitePress $sitepress
	 */
	public function __construct( \wpdb $wpdb, \SitePress $sitepress ) {
		$this->wpdb      = $wpdb;
		$this->sitepress = $sitepress;
	}

	public function run() {
		$startWpmlVersion  = get_option( \WPML_Installation::WPML_START_VERSION_KEY );
		$isNewInstallation = ICL_SITEPRESS_VERSION === $startWpmlVersion;
		if ( $isNewInstallation ) {
			$settings = $this->sitepress->get_setting( 'st' );
			if ( ! is_array( $settings ) ) {
				$settings = [];
			}
			$settings['autoregister_strings'] = SettingsRepositoryInterface::AUTOREGISTER_STRINGS_TYPE_ONLY_VIEWED_BY_ADMIN;
			$this->sitepress->set_setting( 'st', $settings );
			$this->sitepress->set_setting( PreloadThemeMoFile::SETTING_KEY, PreloadThemeMoFile::SETTING_ENABLED_FOR_LOAD_TEXT_DOMAIN );
			$this->sitepress->save_settings();
		}

		$stringsTableSql   = "SHOW TABLES LIKE '{$this->wpdb->prefix}icl_strings'";
		$stringsTableExist = $this->wpdb->get_var( $stringsTableSql ) === "{$this->wpdb->prefix}icl_strings";

		if ( ! $stringsTableExist ) {
			return false;
		}

		$stringTypeCreated    = $this->createColumn( 'string_type', 'TINYINT NOT NULL DEFAULT 0' );
		$componentIdCreated   = $this->createColumn( 'component_id', 'VARCHAR(500) DEFAULT NULL' );
		$componentTypeCreated = $this->createColumn( 'component_type', 'TINYINT NOT NULL DEFAULT 0' );

		return $stringTypeCreated && $componentIdCreated && $componentTypeCreated;
	}

	private function createColumn( $columnName, $createColumnSql, $tableName = 'icl_strings' ) {
		$columnSql    = "SHOW COLUMNS FROM `{$this->wpdb->prefix}{$tableName}` LIKE '" . $columnName . "'";
		$columnExists = $this->wpdb->get_var( $columnSql ) === $columnName;

		if ( ! $columnExists ) {
			$sql = "ALTER TABLE {$this->wpdb->prefix}{$tableName} ADD COLUMN `" . $columnName . "` " . $createColumnSql;
			return (bool) $this->wpdb->query( $sql );
		} else {
			return true;
		}
	}

	public function run_ajax() {
		return $this->run();
	}

	public function run_frontend() {
	}

	/**
	 * @return string
	 */
	public static function get_command_id() {
		return __CLASS__;
	}
}
