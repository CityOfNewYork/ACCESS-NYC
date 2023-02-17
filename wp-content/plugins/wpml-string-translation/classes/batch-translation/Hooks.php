<?php

namespace WPML\ST\Batch\Translation;

use WPML\FP\Obj;
use WPML\LIB\WP\Hooks as WPHooks;
use function WPML\FP\spreadArgs;

class Hooks {

	public static function addHooks(
		callable $getBatchId,
		callable $setBatchRecord,
		callable $getBatchRecord,
		callable $getString
	) {

		WPHooks::onFilter( 'wpml_tm_batch_factory_elements', 10, 2 )
			->then( spreadArgs( Convert::toBatchElements( $getBatchId, $setBatchRecord ) ) );

		WPHooks::onFilter( 'wpml_tm_basket_items_types', 10, 1 )
			->then( spreadArgs( Obj::set( Obj::lensProp( 'st-batch' ), 'core' ) ) );

		WPHooks::onFilter( 'wpml_is_external', 10, 2 )
			->then(
				spreadArgs(
					function ( $state, $type ) {
						return $state
						  || ( is_object( $type ) && Obj::prop( 'post_type', $type ) === 'strings' )
						  || $type === 'st-batch';
					}
				)
			);

		WPHooks::onFilter( 'wpml_get_translatable_item', 10, 3 )
			->then( spreadArgs( Strings::get( $getBatchRecord, $getString ) ) );

		WPHooks::onAction( 'wpml_save_external', 10, 3 )
			->then( spreadArgs( StringTranslations::save() ) );

		WPHooks::onFilter( 'wpml_tm_populate_prev_translation', 10, 3 )
			->then( spreadArgs( StringTranslations::addExisting() ) );
	}

	public static function addStringTranslationStatusHooks(
		callable $updateTranslationStatus,
		callable $initializeTranslation
	) {
		WPHooks::onAction( 'wpml_tm_added_translation_element', 10, 2 )->then( spreadArgs( $initializeTranslation ) );
		WPHooks::onAction( 'wpml_tm_job_in_progress', 10, 2 )->then( spreadArgs( $updateTranslationStatus ) );
		WPHooks::onAction( 'wpml_tm_job_cancelled', 10, 1 )->then( spreadArgs( StringTranslations::cancelTranslations() ) );
	}
}

