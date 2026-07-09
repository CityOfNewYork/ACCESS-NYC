<?php

namespace ACFML\Options;

use ACFML\Strings\Factory;
use ACFML\Strings\Package;
use WPML\FP\Obj;
use WPML\FP\Str;

class TranslationJobHooks implements \IWPML_Backend_Action, \IWPML_REST_Action {

	public function add_hooks() {
		add_action( 'wpml_pro_translation_completed', [ $this, 'saveFieldGroupStringsTranslations' ], 10, 3 );
	}

	/**
	 * @param int       $translatedPostId
	 * @param array     $fields
	 * @param \stdClass $job
	 */
	public function saveFieldGroupStringsTranslations( $translatedPostId, $fields, $job ) {
		if ( 'package_' . Package::OPTION_PACKAGE_KIND_SLUG !== Obj::prop( 'original_post_type', $job ) ) {
			return;
		}

		$packageObject = Factory::createWpmlPackage( $job->original_doc_id );
		if ( ! isset( $packageObject->name ) ) {
			return;
		}

		$language = $job->language_code;
		foreach ( $job->elements as $element ) {
			$optionFieldName = Str::match( '/^options-(.*?)-(field_(.*?))-(.*?)$/', Obj::propOr( '', 'field_type', $element ) );
			$optionField     = $optionFieldName ? $optionFieldName[1] : '';
			$optionKey       = $optionFieldName ? $optionFieldName[2] : '';

			if ( empty( $optionField ) || empty( $optionKey ) ) {
				continue;
			}

			$translatedValue = base64_decode( Obj::propOr( '', 'field_data_translated', $element ) );
			$optionName      = $packageObject->name . '_' . $language . '_' . $optionField;
			update_option( $optionName, $translatedValue );
			update_option( '_' . $optionName, $optionKey );
		}
	}

}
