<?php

namespace WPML\TranslationRoles;

class RemoveManager extends Remove {
	protected static function getCap() {
		return \WPML_Manage_Translations_Role::CAPABILITY;
	}
}
