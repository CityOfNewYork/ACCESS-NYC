<?php

namespace ACFML\Tools;

use ACFML\Helper\FieldGroup;

abstract class Transfer {
	const FIELD_GROUP_POST_TYPE = FieldGroup::CPT;
	const LANGUAGE_PROPERTY     = 'wpml_field_group_language';

	/**
	 * @depecated Use FieldGroup::isTranslatable instead.
	 *
	 * @return bool
	 */
	protected function isGroupTranslatable() {
		return FieldGroup::isTranslatable();
	}
}
