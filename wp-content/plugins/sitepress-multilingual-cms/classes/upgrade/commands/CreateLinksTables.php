<?php

namespace WPML\TM\Upgrade\Commands;

use WPML\Infrastructure\WordPress\Component\Translation\Domain\Links\Repository;

class CreateLinksTables implements \IWPML_Upgrade_Command {

	public function run_admin() {
		return Repository::createDatabaseTables();
	}

    public function run_ajax() { }

    public function run_frontend() { }

    public function get_results() { }

}
