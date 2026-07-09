<?php

namespace ACFML\Tools;

use ACFML\FieldGroup\Mode;
use ACFML\Helper\FieldGroup;
use WPML\FP\Obj;
use WPML\LIB\WP\Hooks;
use function WPML\FP\spreadArgs;

class Local extends Transfer implements \IWPML_Backend_Action, \IWPML_Frontend_Action, \IWPML_DIC_Action {

	// Needs to happen before WPML_ACF_Field_Settings::processSubfieldsQueue() at priority 10.
	const PROCESS_SUBFIELDS_QUEUE_PRIORITY = 9;
	const SCAN_MODE_CACHE_OPTION_KEY       = 'acfml_local_fields_preferences';

	/**
	 * @var \WPML_ACF_Field_Settings
	 */
	private $field_settings;

	/**
	 * @var array
	 */
	private $subfieldsQueue = [];

	public function __construct( \WPML_ACF_Field_Settings $field_settings ) {
		$this->field_settings = $field_settings;
	}


	public function add_hooks() {
		if ( ! $this->isImportFromFile() ) {
			add_filter( 'acf/prepare_field_group_for_import', [ $this, 'unsetTranslated' ] );
			if ( is_admin() && LocalSettings::shouldRunScan() ) {
				add_filter( 'acf/prepare_fields_for_import', [ $this, 'syncTranslationPreferences' ] );
				// Save changes in translation preferences for existing subfields.
				add_action( 'shutdown', [ $this, 'processSubfieldsQueue' ], self::PROCESS_SUBFIELDS_QUEUE_PRIORITY );
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
			$this->field_settings->update_field_settings( $field, false );
			$this->subfieldsQueue[] = $field;
		}
		return $fields;
	}

	private function isImportFromFile() {
		// phpcs:ignore WordPress.VIP.SuperGlobalInputUsage.AccessDetected
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

	public function processSubfieldsQueue() {
		if ( empty( $this->subfieldsQueue ) ) {
			return;
		}

		$subfieldsPreferences = wpml_collect( $this->subfieldsQueue )
			->filter( function( $field ) {
				return (bool) Obj::prop( 'wpml_cf_preferences', $field ) && (bool) Obj::prop( 'key', $field );
			} )
			->keyBy( 'key' )
			->pluck( 'wpml_cf_preferences' )
			->toArray();

		$currentHash = md5( wp_json_encode( $subfieldsPreferences ) );
		$storedHash  = get_option( self::SCAN_MODE_CACHE_OPTION_KEY, '' );

		if ( $currentHash === $storedHash ) {
			return;
		}

		foreach ( $this->subfieldsQueue as $field ) {
			$this->field_settings->maybeAddToSubfieldsQueue( $field );
		}

		$this->subfieldsQueue = [];
		update_option( self::SCAN_MODE_CACHE_OPTION_KEY, $currentHash, false );
	}
}
