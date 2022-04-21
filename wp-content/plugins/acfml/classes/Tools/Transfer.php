<?php

namespace ACFML\Tools;

abstract class Transfer {
	const FIELD_GROUP_POST_TYPE = 'acf-field-group';
	const LANGUAGE_PROPERTY     = 'wpml_field_group_language';

	/**
	 * @return bool
	 */
	protected function isGroupTranslatable() {
		return is_post_type_translated( self::FIELD_GROUP_POST_TYPE );
	}
}
