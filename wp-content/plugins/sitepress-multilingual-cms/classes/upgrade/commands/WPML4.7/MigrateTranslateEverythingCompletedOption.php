<?php

namespace WPML\TM\Upgrade\Commands;

use WPML\WP\OptionManager;
use WPML\Setup\Option;

/**
 * In WPML 4.7, the option 'translate-everything-completed' was removed and its value was moved to 'translate-everything-posts'.
 * This class is responsible for migrating the value from the old option to the new one.
 *
 * This is especially dangerous for the case when a user enabled TEA with the option to translate only new content.
 * Without this migration, after switching to WPML 4.7, TEA would start translating that existing content, which
 * a user decided not to translate.
 */
class MigrateTranslateEverythingCompletedOption implements \IWPML_Upgrade_Command {

	public function run() {
		if ( ! Option::shouldTranslateEverything() ) {
			return true;
		}

		$optionManager = new OptionManager();

		$oldOption = $optionManager->get( Option::OPTION_GROUP, 'translate-everything-completed', [] );
		$optionManager->set( Option::OPTION_GROUP, Option::TRANSLATE_EVERYTHING_POSTS, $oldOption );

		return true;
	}

	public function run_admin() {
		return $this->run();
	}

	public function run_ajax() {
		return null;
	}

	public function run_frontend() {
		return null;
	}

	public function get_results() {
		return true;
	}
}
