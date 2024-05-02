<?php

namespace WPML\TM\ATE\TranslateEverything\Pause;

use WPML\LIB\WP\User;

class UserAuthorisation {
	public function isAllowedToPauseAutomaticTranslation() {
		return $this->isTranslationManager();
	}

	public function isAllowedToResumeAutomaticTranslation() {
		return $this->isTranslationManager();
	}

	private function isTranslationManager() {
		if (
			! User::canManageTranslations()
			// Check also for manage_options as on WPML Setup the admin
			// has not the above capability.
			&& ! User::canManageOptions()
		) {
			// User is neither Translation Manager nor Administrator.
			return false;
		}

		return true;
	}
}
