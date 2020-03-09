<?php

namespace WPML\ST\StringsFilter;

use WPML_Displayed_String_Filter;
use WPML_Register_String_Filter;
use WPML_String_Translation;

class Provider {

	/** @var WPML_String_Translation */
	private $string_translation;

	/** @var WPML_Displayed_String_Filter[]|WPML_Register_String_Filter[] */
	private $filters = [];

	public function __construct( WPML_String_Translation $string_translation ) {
		$this->string_translation = $string_translation;
	}

	/**
	 * Get filter.
	 *
	 * @param string|null $lang Language.
	 * @param string|null $name Language name.
	 *
	 * @return WPML_Displayed_String_Filter|WPML_Register_String_Filter|null
	 */
	public function getFilter( $lang = null, $name = null ) {
		if ( ! $lang ) {
			$lang = $this->string_translation->get_current_string_language( $name );
		}

		if ( ! $lang ) {
			return null;
		}

		if ( ! ( array_key_exists( $lang, $this->filters ) && $this->filters[ $lang ] ) ) {
			$this->filters[ $lang ] = $this->string_translation->get_string_filter( $lang );
		}

		return $this->filters[ $lang ];
	}

	public function clearFilters() {
		$this->filters = [];
	}
}
