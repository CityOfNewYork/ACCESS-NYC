<?php

namespace ACFML\FieldGroup;

use ACFML\Helper\Fields;
use WPML\FP\Fns;
use WPML\FP\Obj;
use WPML\LIB\WP\Hooks;
use WPML\TM\Settings\CustomFieldChangeDetector;
use function WPML\FP\spreadArgs;

class SaveHooks implements \IWPML_Action {

	/**
	 * @var FieldNamePatterns $fieldNamePatterns
	 */
	private $fieldNamePatterns;

	/**
	 * @var DetectNonTranslatableLocations $detectNonTranslatableLoc
	 */
	private $detectNonTranslatableLoc;

	public function __construct(
		FieldNamePatterns $fieldNamePatterns,
		DetectNonTranslatableLocations $detectNonTranslatableLoc
	) {
		$this->fieldNamePatterns        = $fieldNamePatterns;
		$this->detectNonTranslatableLoc = $detectNonTranslatableLoc;
	}

	public function add_hooks() {
		Hooks::onAction( 'acf/update_field_group' )
			->then( spreadArgs( [ $this, 'onUpdateFieldGroup' ] ) );
	}

	/**
	 * @param array $fieldGroup
	 *
	 * @return void
	 */
	public function onUpdateFieldGroup( $fieldGroup ) {
		$this->overwriteAllFieldPreferencesWithGroupMode( $fieldGroup );
		$this->updateFieldNamePatterns( $fieldGroup );
		$this->detectNonTranslatableLoc->process( $fieldGroup );
		$this->maybeForceTranslationStatusProcessOnAttachedPosts( $fieldGroup );
	}

	/**
	 * @param array $fieldGroup
	 *
	 * @return void
	 */
	private function overwriteAllFieldPreferencesWithGroupMode( $fieldGroup ) {
		if ( Mode::isAdvanced( $fieldGroup ) ) {
			return;
		}

		$getFieldPreference = ModeDefaults::get( Mode::getMode( $fieldGroup ) );

		$updateFieldTranslationPreference = Fns::tap( function( $field ) use ( $getFieldPreference ) {
			acf_update_field( Obj::assoc( 'wpml_cf_preferences', $getFieldPreference( $field ), $field ) );
		} );

		Fields::iterate( acf_get_fields( $fieldGroup ), $updateFieldTranslationPreference, Fns::identity() );
	}

	/**
	 * @param array $fieldGroup
	 *
	 * @return void
	 */
	private function updateFieldNamePatterns( $fieldGroup ) {
		$namePatterns = wpml_collect();

		if ( ! Mode::isAdvanced( $fieldGroup ) ) {
			$getFieldNamePattern = function( $field, $fieldPattern ) use ( $namePatterns ) {
				$namePatterns->push( $fieldPattern );

				return $field;
			};

			Fields::iterate( acf_get_fields( $fieldGroup ), $getFieldNamePattern, Fns::identity() );
		}

		$this->fieldNamePatterns->updateGroup( $fieldGroup['ID'], $namePatterns->toArray() );
	}

	/**
	 * @param array $fieldGroup
	 *
	 * @return void
	 */
	private function maybeForceTranslationStatusProcessOnAttachedPosts( $fieldGroup ) {
		if ( isset( $_POST['acfml_force_translation_status_process'] ) ) { // phpcs:ignore
			$fieldNames = wpml_collect( acf_get_fields( $fieldGroup ) )
				->map( Obj::prop( 'name' ) )
				->toArray();

			CustomFieldChangeDetector::notify( $fieldNames ); // @phpstan-ignore-line
		}
	}
}
