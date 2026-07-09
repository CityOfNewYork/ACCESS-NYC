<?php

namespace ACFML\Tools;

use ACFML\FieldGroup\Mode;
use ACFML\Helper\FieldGroup;
use WPML\LIB\WP\Hooks;
use function WPML\FP\spreadArgs;

class Local extends Transfer implements \IWPML_Backend_Action, \IWPML_Frontend_Action, \IWPML_DIC_Action {
	/**
	 * @var \WPML_ACF_Field_Settings
	 */
	private $field_settings;

	public function __construct( \WPML_ACF_Field_Settings $field_settings ) {
		$this->field_settings = $field_settings;
	}


	public function add_hooks() {
		if ( ! $this->isImportFromFile() ) {
			add_filter( 'acf/prepare_field_group_for_import', [ $this, 'unsetTranslated' ] );
			if ( is_admin() && LocalSettings::isScanModeEnabled() ) {
				add_filter( 'acf/prepare_fields_for_import', [ $this, 'syncTranslationPreferences' ] );
			}
		}

		if ( is_admin() ) {
			Hooks::onFilter( 'acf/prepare_field_group_for_import' )
				->then( spreadArgs( [ $this, 'ensureTranslationMode' ] ) );
			add_action( 'acf/include_admin_tools', [ $this, 'loadUI' ] );
		}
	}

	/**
	 * @param array $fieldGroup
	 *
	 * @return array
	 */
	public function unsetTranslated( $fieldGroup ) {
		if ( $this->isGroupTranslatable() && isset( $fieldGroup[ self::LANGUAGE_PROPERTY ], $fieldGroup['key'] ) ) {
			if ( apply_filters( 'wpml_current_language', null ) !== $fieldGroup[ self::LANGUAGE_PROPERTY ] ) {
				// reset field group but keep 'key', otherwise ACF will php notice.
				$fieldGroup = [
					'key' => $fieldGroup['key'],
				];
			}
		}

		return $fieldGroup;
	}

	/**
	 * @param array $fields
	 *
	 * @return mixed
	 */
	public function syncTranslationPreferences( $fields ) {
		foreach ( $fields as $field ) {
			$this->field_settings->update_field_settings( $field );
		}
		return $fields;
	}

	private function isImportFromFile() {
		return isset( $_FILES['acf_import_file'] );
	}

	public function loadUI() {
		acf_register_admin_tool( 'ACFML\Tools\LocalUI' );
	}

	/**
	 * @param array $fieldGroup
	 *
	 * @return array
	 */
	public function ensureTranslationMode( $fieldGroup ) {
		if ( Mode::getMode( $fieldGroup ) === null ) {
			$fieldGroup[ Mode::KEY ] = Mode::ADVANCED;
		}

		return $fieldGroup;
	}
}
