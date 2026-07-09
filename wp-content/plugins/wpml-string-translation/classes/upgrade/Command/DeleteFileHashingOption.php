<?php

namespace WPML\ST\Upgrade\Command;

class DeleteFileHashingOption implements \IWPML_St_Upgrade_Command {

	const OPTION_NAME = 'wpml-scanning-files-hashing';

	public function run() {
		delete_option( self::OPTION_NAME );
		return true;
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
