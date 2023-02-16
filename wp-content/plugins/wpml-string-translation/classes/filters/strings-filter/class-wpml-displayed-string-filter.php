<?php
/**
 * WPML_Displayed_String_Filter class file.
 *
 * @package WPML\ST
 */

use WPML\ST\StringsFilter\Translator;
use WPML\ST\StringsFilter\StringEntity;
use WPML\ST\StringsFilter\TranslationEntity;

/**
 * Class WPML_Displayed_String_Filter
 *
 * Handles all string translating when rendering translated strings to the user, unless auto-registering is
 * active for strings.
 */
class WPML_Displayed_String_Filter {
	/** @var Translator */
	protected $translator;

	/**
	 * @param Translator $translator
	 */
	public function __construct( Translator $translator ) {
		$this->translator = $translator;
	}

	/**
	 * Translate by name and context.
	 *
	 * @param string       $untranslated_text Untranslated text.
	 * @param string       $name Name of the string.
	 * @param string|array $context Context.
	 * @param null|boolean $has_translation If string has translation.
	 *
	 * @return string
	 */
	public function translate_by_name_and_context(
		$untranslated_text,
		$name,
		$context = '',
		&$has_translation = null
	) {
		if ( is_array( $untranslated_text ) || is_object( $untranslated_text ) ) {
			return '';
		}

		$translation = $this->get_translation( $untranslated_text, $name, $context );

		$has_translation = $translation->hasTranslation();

		return $translation->getValue();
	}

	/**
	 * Transform translation parameters.
	 *
	 * @param string       $name Name of the string.
	 * @param string|array $context Context.
	 *
	 * @return array
	 */
	protected function transform_parameters( $name, $context ) {
		list ( $domain, $gettext_context ) = wpml_st_extract_context_parameters( $context );

		return array( $name, $domain, $gettext_context );
	}

	/**
	 * Truncates a string to the maximum string table column width.
	 *
	 * @param string $string String to translate.
	 *
	 * @return string
	 */
	public static function truncate_long_string( $string ) {
		return strlen( $string ) > WPML_STRING_TABLE_NAME_CONTEXT_LENGTH
			? mb_substr( $string, 0, WPML_STRING_TABLE_NAME_CONTEXT_LENGTH )
			: $string;
	}

	/**
	 * Get translation of the string.
	 *
	 * @param string       $untranslated_text Untranslated text.
	 * @param string       $name Name of the string.
	 * @param string|array $context Context.
	 *
	 * @return TranslationEntity
	 */
	protected function get_translation( $untranslated_text, $name, $context ) {
		list ( $name, $domain, $gettext_context ) = $this->transform_parameters( $name, $context );
		$untranslated_text                        = is_numeric( $untranslated_text ) ? (string) $untranslated_text : $untranslated_text;

		$translation = $this->translator->translate(
			new StringEntity(
				$untranslated_text,
				$name,
				$domain,
				$gettext_context
			)
		);

		if ( ! $translation->hasTranslation() ) {
			list( $name, $domain ) = array_map( array( $this, 'truncate_long_string' ), array( $name, $domain ) );
			$translation           = $this->translator->translate(
				new StringEntity(
					$untranslated_text,
					$name,
					$domain,
					$gettext_context
				)
			);
		}

		return $translation;
	}
}
