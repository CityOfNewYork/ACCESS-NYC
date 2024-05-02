<?php

namespace WPML\TM\ATE\AutoTranslate\Endpoint;

use WPML\API\PostTypes;
use WPML\Collect\Support\Collection;
use WPML\FP\Either;
use WPML\FP\Lst;
use WPML\FP\Obj;
use WPML\Setup\Option;
use WPML\TM\API\ATE\LanguageMappings;

class ActivateLanguage {

	public function run( Collection $data ) {
		$postTypes = PostTypes::getAutomaticTranslatable();

		$updatePostTypes = function ( $newLanguages, $mergingFn ) use ( $postTypes ) {
			if ( $newLanguages && $postTypes ) {
				$completed = Option::getTranslateEverythingCompleted();
				foreach ( $postTypes as $postType ) {
					$existingLanguages = Obj::propOr( [], $postType, $completed );
					Option::markPostTypeAsCompleted( $postType, $mergingFn( $existingLanguages, $newLanguages ) );
				}
			}
		};

		$translateExistingContent = $data->get( 'translate-existing-content', false );
		$newLanguages             = $data->get( 'languages' );

		if ( $translateExistingContent ) {
			$doesSupportAutomaticTranslations = function ( $code ) {
				$languageDetails = [ $code => [ 'code' => $code ] ]; // we need to build an input acceptable by LanguageMappings::withCanBeTranslatedAutomatically
				$languageDetails = LanguageMappings::withCanBeTranslatedAutomatically( $languageDetails );

				return Obj::pathOr( false, [ $code, 'can_be_translated_automatically' ], $languageDetails );
			};
			list( $newLanguagesWhichCanBeAutoTranslated, $newLanguagesWhichCannotBeAutoTranslated ) = Lst::partition(
				$doesSupportAutomaticTranslations,
				$newLanguages
			);

			// those languages which can be auto-translated should be removed from the completed list
			$updatePostTypes( $newLanguagesWhichCanBeAutoTranslated, Lst::diff() );

			// those languages which cannot be auto-translated should be added to the completed list
			// to avoid accidental triggering Translate Everything for them when a user changes mapping or translation engines,
			// a∂nd they will become eligible for auto-translation.
			$updatePostTypes( $newLanguagesWhichCannotBeAutoTranslated, Lst::concat() );
		} else {
			/**
			 * If a user has chosen not to translate existing content, we should mark all languages as completed regardless of whether they can be auto-translated or not.
			 */
			$updatePostTypes( $newLanguages, Lst::concat() );
		}


		return Either::of( 'ok' );
	}
}