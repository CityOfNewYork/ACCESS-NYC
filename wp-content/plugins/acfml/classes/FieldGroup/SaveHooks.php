<?php

namespace ACFML\FieldGroup;

use ACFML\Helper\Fields;
use WPML\FP\Fns;
use WPML\FP\Obj;
use WPML\LIB\WP\Hooks;
use WPML\Element\API\Languages;
use WPML\TM\Settings\CustomFieldChangeDetector;
use function WPML\FP\spreadArgs;

class SaveHooks implements \IWPML_Action {

	// On 10, group settings might get stored in local JSON files:
	// we need to update field translation settings before that.
	const UPDATE_FIELD_GROUP_PRIORITY = 9;

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
		Hooks::onAction( 'acf/update_field_group', self::UPDATE_FIELD_GROUP_PRIORITY )
			->then( spreadArgs( [ $this, 'onUpdateFieldGroup' ] ) );
	}

	/**
	 * @param array $fieldGroup
	 *
	 * @return void
	 */
	public function onUpdateFieldGroup( $fieldGroup ) {
		$this->overwriteAllFieldPreferencesWithGroupMode( $fieldGroup );
		$this->fieldNamePatterns->updateFieldNamePatterns( $fieldGroup );
		$this->detectNonTranslatableLoc->process( $fieldGroup );
		$this->maybeForceTranslationStatusProcessOnAttachedPosts( $fieldGroup );
		$this->flushAcfCache( $fieldGroup );
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

		$updateFieldTranslationPreference = Fns::tap(
			function( $field ) use ( $getFieldPreference ) {
				acf_update_field( Obj::assoc( 'wpml_cf_preferences', $getFieldPreference( $field ), wp_slash( $field ) ) );
			}
		);

		Fields::iterate( acf_get_fields( $fieldGroup ), $updateFieldTranslationPreference, Fns::identity() );
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

	/**
	 * @param array $fieldGroup
	 *
	 * @return void
	 */
	private function flushAcfCache( $fieldGroup ) {
		$active_languages = Languages::getActive();

		wp_cache_delete( 'acf_get_field_group_posts', 'acf' );
		wp_cache_delete( 'acf_get_field_group_post:key:' . $fieldGroup['key'], 'acf' );
		wp_cache_delete( 'acf_get_field_posts:' . $fieldGroup['ID'], 'acf' );
		if ( ! empty( $active_languages ) ) {
			foreach ( $active_languages as $lang_code => $language ) {
				wp_cache_delete( 'acf_get_field_posts:' . $fieldGroup['ID'] . ':' . $lang_code, 'acf' );
			}
		}
	}
}
