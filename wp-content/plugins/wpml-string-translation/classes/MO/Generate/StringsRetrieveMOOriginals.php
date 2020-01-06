<?php

namespace WPML\ST\MO\Generate;

use WPML\ST\TranslationFile\StringsRetrieve;

class StringsRetrieveMOOriginals extends StringsRetrieve {

	/**
	 * @param array $row_data
	 *
	 * @return string|null
	 */
	public static function parseTranslation( array $row_data ) {
		return ! empty( $row_data['mo_string'] ) ? $row_data['mo_string'] : null;
	}

}
