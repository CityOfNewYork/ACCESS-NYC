<?php

namespace ACFML\FieldGroup;

use WPML\FP\Obj;
use function WPML\FP\curryN;

class ModeDefaults {

	const IGNORE    = WPML_IGNORE_CUSTOM_FIELD;
	const COPY      = WPML_COPY_CUSTOM_FIELD;
	const COPY_ONCE = WPML_COPY_ONCE_CUSTOM_FIELD;
	const TRANSLATE = WPML_TRANSLATE_CUSTOM_FIELD;

	const MAP = [
		// Basic
		'text' => [
			Mode::TRANSLATION  => self::TRANSLATE,
			Mode::LOCALIZATION => self::TRANSLATE,
		],
		'textarea' => [
			Mode::TRANSLATION  => self::TRANSLATE,
			Mode::LOCALIZATION => self::TRANSLATE,
		],
		'number' => [
			Mode::TRANSLATION  => self::COPY,
			Mode::LOCALIZATION => self::COPY_ONCE,
		],
		'range' => [
			Mode::TRANSLATION  => self::COPY,
			Mode::LOCALIZATION => self::COPY_ONCE,
		],
		'email' => [
			Mode::TRANSLATION  => self::COPY,
			Mode::LOCALIZATION => self::COPY_ONCE,
		],
		'url' => [
			Mode::TRANSLATION  => self::COPY,
			Mode::LOCALIZATION => self::COPY_ONCE,
		],
		'password' => [
			Mode::TRANSLATION  => self::COPY,
			Mode::LOCALIZATION => self::COPY_ONCE,
		],
		// Content
		'image' => [
			Mode::TRANSLATION  => self::COPY,
			Mode::LOCALIZATION => self::COPY_ONCE,
		],
		'file' => [
			Mode::TRANSLATION  => self::COPY,
			Mode::LOCALIZATION => self::COPY_ONCE,
		],
		'wysiwyg' => [
			Mode::TRANSLATION  => self::TRANSLATE,
			Mode::LOCALIZATION => self::TRANSLATE,
		],
		'oembed' => [
			Mode::TRANSLATION  => self::COPY,
			Mode::LOCALIZATION => self::COPY_ONCE,
		],
		'gallery' => [
			Mode::TRANSLATION  => self::COPY,
			Mode::LOCALIZATION => self::COPY_ONCE,
		],
		// Choice
		'select' => [
			Mode::TRANSLATION  => self::COPY,
			Mode::LOCALIZATION => self::COPY_ONCE,
		],
		'checkbox' => [
			Mode::TRANSLATION  => self::COPY,
			Mode::LOCALIZATION => self::COPY_ONCE,
		],
		'radio' => [
			Mode::TRANSLATION  => self::COPY,
			Mode::LOCALIZATION => self::COPY_ONCE,
		],
		'button_group' => [
			Mode::TRANSLATION  => self::COPY,
			Mode::LOCALIZATION => self::COPY_ONCE,
		],
		'true_false' => [
			Mode::TRANSLATION  => self::COPY,
			Mode::LOCALIZATION => self::COPY_ONCE,
		],
		// jQuery
		'google_map' => [
			Mode::TRANSLATION  => self::COPY,
			Mode::LOCALIZATION => self::COPY_ONCE,
		],
		'date_picker' => [
			Mode::TRANSLATION  => self::COPY,
			Mode::LOCALIZATION => self::COPY_ONCE,
		],
		'date_time_picker' => [
			Mode::TRANSLATION  => self::COPY,
			Mode::LOCALIZATION => self::COPY_ONCE,
		],
		'time_picker' => [
			Mode::TRANSLATION  => self::COPY,
			Mode::LOCALIZATION => self::COPY_ONCE,
		],
		'color_picker' => [
			Mode::TRANSLATION  => self::COPY,
			Mode::LOCALIZATION => self::COPY_ONCE,
		],
		// Layout
		'message' => [
			Mode::TRANSLATION  => self::TRANSLATE,
			Mode::LOCALIZATION => self::TRANSLATE,
		],
		'accordion' => [
			Mode::TRANSLATION  => self::COPY,
			Mode::LOCALIZATION => self::COPY_ONCE,
		],
		'tab' => [
			Mode::TRANSLATION  => self::COPY,
			Mode::LOCALIZATION => self::COPY_ONCE,
		],
		'group' => [
			Mode::TRANSLATION  => self::COPY,
			Mode::LOCALIZATION => self::COPY_ONCE,
		],
		'repeater' => [
			Mode::TRANSLATION  => self::COPY,
			Mode::LOCALIZATION => self::COPY_ONCE,
		],
		'flexible_content' => [
			Mode::TRANSLATION  => self::COPY,
			Mode::LOCALIZATION => self::COPY_ONCE,
		],
		'clone' => [
			Mode::TRANSLATION  => self::COPY,
			Mode::LOCALIZATION => self::COPY_ONCE,
		],
		// Relational
		'link' => [
			Mode::TRANSLATION  => self::COPY,
			Mode::LOCALIZATION => self::COPY_ONCE,
		],
		'post_object' => [
			Mode::TRANSLATION  => self::COPY,
			Mode::LOCALIZATION => self::COPY_ONCE,
		],
		'page_link' => [
			Mode::TRANSLATION  => self::COPY,
			Mode::LOCALIZATION => self::COPY_ONCE,
		],
		'relationship' => [
			Mode::TRANSLATION  => self::COPY,
			Mode::LOCALIZATION => self::COPY_ONCE,
		],
		'taxonomy' => [
			Mode::TRANSLATION  => self::COPY,
			Mode::LOCALIZATION => self::COPY_ONCE,
		],
		'user' => [
			Mode::TRANSLATION  => self::COPY,
			Mode::LOCALIZATION => self::COPY_ONCE,
		],
	];

	/**
	 * @param string $groupMode
	 * @param array  $field
	 *
	 * @return callable|int
	 */
	public static function get( $groupMode = null, $field = null ) {
		$get = curryN( 2, function( $groupMode, $field ) {
			$fieldTranslationPreference = (int) Obj::pathOr( self::TRANSLATE, [ Obj::prop( 'type', $field ), $groupMode ], self::MAP );

			/**
			 * This filter allows to override the default translation preference
			 * based on the field group mode.
			 *
			 * @param int    $fieldTranslationPreference The translation preference (1, 2, 3).
			 * @param string $groupMode                  The field group mode.
			 * @param array  $field                      The ACF field.
			 *
			 * @since 2.0.0
			 *
			 */
			return (int) apply_filters( 'acfml_field_group_mode_field_translation_preference', $fieldTranslationPreference, $groupMode, $field );
		} );

		return call_user_func_array( $get, func_get_args() );
	}
}
