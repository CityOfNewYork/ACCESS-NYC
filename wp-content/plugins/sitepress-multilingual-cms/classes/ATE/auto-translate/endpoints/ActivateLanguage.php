<?php

namespace WPML\TM\ATE\AutoTranslate\Endpoint;

use WPML\API\PostTypes;
use WPML\Collect\Support\Collection;
use WPML\FP\Either;
use WPML\FP\Lst;
use WPML\FP\Obj;
use WPML\Setup\Option;

class ActivateLanguage {

	public function run( Collection $data ) {
		$newLanguages             = $data->get( 'languages' );
		$translateExistingContent = $data->get( 'translate-existing-content', false );
		$mergingFn                = $translateExistingContent ? Lst::diff() : Lst::concat();

		$postTypes = PostTypes::getAutomaticTranslatable();

		if ( $newLanguages && $postTypes ) {
			$completed = Option::getTranslateEverythingCompleted();
			foreach ( $postTypes as $postType ) {
				$existingLanguages = Obj::propOr( [], $postType, $completed );
				Option::markPostTypeAsCompleted( $postType, $mergingFn( $existingLanguages, $newLanguages ) );
			}
		}

		return Either::of( 'ok' );
	}
}