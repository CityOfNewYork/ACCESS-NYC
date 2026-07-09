<?php

namespace WordfenceLS;

use RuntimeException;

class Controller_DB {
	const TABLE_2FA_SECRETS = 'wfls_2fa_secrets';
	const TABLE_SETTINGS = 'wfls_settings';
	const TABLE_ROLE_COUNTS = 'wfls_role_counts';
	const TABLE_ROLE_COUNTS_TEMPORARY = 'wfls_role_counts_temporary';

	const SCHEMA_VERSION = 2;
	
	/**
	 * Returns the singleton Controller_DB.
	 *
	 * @return Controller_DB
	 */
	public static function shared() {
		static $_shared = null;
		if ($_shared === null) {
			$_shared = new Controller_DB();
		}
		return $_shared;
	}
	
	/**
	 * Returns the table prefix for the main site on multisites and the site itself on single site installations.
	 *
	 * @return string
	 */
	public static function network_prefix() {
		global $wpdb;
		return $wpdb->base_prefix;
	}
	
	/**
	 * Returns the table with the site (single site installations) or network (multisite) prefix added.
	 *
	 * @param string $table
	 * @return string
	 */
	public static function network_table($table) {
		return self::network_prefix() . $table;
	}
	
	public function __get($key) {
		switch ($key) {
			case 'secrets':
				return self::network_table(self::TABLE_2FA_SECRETS);
			case 'settings':
				return self::network_table(self::TABLE_SETTINGS);
			case 'role_counts':
				return self::network_table(self::TABLE_ROLE_COUNTS);
			case 'role_counts_temporary':
				return self::network_table(self::TABLE_ROLE_COUNTS_TEMPORARY);
		}
		
		throw new \OutOfBoundsException('Unknown key: ' . $key);
	}
	
	public function install() {
		$this->_create_schema();
		
		global $wpdb;
		$table = $this->secrets;
		$wpdb->query($wpdb->prepare("UPDATE `{$table}` SET `vtime` = LEAST(`vtime`, %d)", Controller_Time::time()));
	}
	
	public function uninstall() {
		$tables = array(self::TABLE_2FA_SECRETS, self::TABLE_SETTINGS, self::TABLE_ROLE_COUNTS);
		foreach ($tables as $table) {
			global $wpdb;
			$wpdb->query('DROP TABLE IF EXISTS `' . self::network_table($table) . '`');
		}
	}

	private function create_table($name, $definition, $temporary = false) {
		global $wpdb;
		if (is_array($definition)) {
			foreach ($definition as $attempt) {
				if ($this->create_table($name, $attempt, $temporary))
					return true;
			}
			return false;
		}
		else {
			return $wpdb->query('CREATE ' . ($temporary ? 'TEMPORARY ' : '') . 'TABLE IF NOT EXISTS `' . self::network_table($name) . '` ' . $definition);
		}
	}

	private function create_temporary_table($name, $definition) {
		if (Controller_Settings::shared()->get_bool(Controller_Settings::OPTION_DISABLE_TEMPORARY_TABLES))
			return false;
		if ($this->create_table($name, $definition, true))
			return true;
		Controller_Settings::shared()->set(Controller_Settings::OPTION_DISABLE_TEMPORARY_TABLES, true);
		return false;
	}

	private function get_role_counts_table_definition($engine = null) {
		$engineClause = $engine === null ? '' : "ENGINE={$engine}";
		return <<<SQL
				(
				serialized_roles VARBINARY(255) NOT NULL,
				two_factor_inactive TINYINT(1) NOT NULL,
				user_count BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
				PRIMARY KEY (serialized_roles, two_factor_inactive)
				) {$engineClause};
SQL;
	}

	private function get_role_counts_table_definition_options() {
		return array(
			$this->get_role_counts_table_definition('MEMORY'),
			$this->get_role_counts_table_definition('MyISAM'),
			$this->get_role_counts_table_definition()
		);
	}
	
	protected function _create_schema() {
		$tables = array(
			self::TABLE_2FA_SECRETS => '(
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `secret` tinyblob NOT NULL,
  `recovery` blob NOT NULL,
  `ctime` int(10) unsigned NOT NULL,
  `vtime` int(10) unsigned NOT NULL,
  `mode` enum(\'authenticator\') NOT NULL DEFAULT \'authenticator\',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;',
			self::TABLE_SETTINGS => '(
  `name` varchar(191) NOT NULL DEFAULT \'\',
  `value` longblob,
  `autoload` enum(\'no\',\'yes\') NOT NULL DEFAULT \'yes\',
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;',
			self::TABLE_ROLE_COUNTS => $this->get_role_counts_table_definition_options()
		);
		
		foreach ($tables as $table => $def) {
			$this->create_table($table, $def);
		}

		Controller_Settings::shared()->set(Controller_Settings::OPTION_SCHEMA_VERSION, self::SCHEMA_VERSION);
	}

	public function require_schema_version($version) {
		$current = Controller_Settings::shared()->get_int(Controller_Settings::OPTION_SCHEMA_VERSION);
		if ($current < $version) {
			$this->install();
		}
	}

	public function query($query) {
		global $wpdb;
		if ($wpdb->query($query) === false)
			throw new RuntimeException("Failed to execute query: {$query}");
	}

	public function get_wpdb() {
		global $wpdb;
		return $wpdb;
	}

	public function create_temporary_role_counts_table() {
		return $this->create_temporary_table(self::TABLE_ROLE_COUNTS_TEMPORARY, $this->get_role_counts_table_definition_options());
	}

}