<?php

namespace WPML\Media;

use WPML\Core\WP\App\Resources;
use WPML\LIB\WP\Hooks;
use WPML\Media\Option;
use WPML\Media\Setup\Endpoint\PerformSetup;
use WPML\Media\Setup\Endpoint\PrepareSetup;
use WPML\Media\Translate\Endpoint\DuplicateFeaturedImages;
use WPML\Media\Translate\Endpoint\FinishMediaTranslation;
use WPML\Media\Translate\Endpoint\PrepareForTranslation;
use WPML\Media\Translate\Endpoint\TranslateExistingMedia;

class Loader implements \IWPML_Backend_Action {

	public function add_hooks() {
		if ( ! Option::isSetupFinished() ) {
			Hooks::onAction( 'wp_loaded' )
			     ->then( [ self::class, 'getData' ] )
			     ->then( Resources::enqueueApp( 'media-setup' ) );
		}
	}

	public static function getData() {
		return [
			'name' => 'media_setup',
			'data' => [
				'endpoints' => [
					'prepareForTranslation'   => PrepareForTranslation::class,
					'translateExistingMedia'  => TranslateExistingMedia::class,
					'duplicateFeaturedImages' => DuplicateFeaturedImages::class,
					'finishMediaTranslation'  => FinishMediaTranslation::class,
					'prepareForSetup'         => PrepareSetup::class,
					'performSetup'            => PerformSetup::class,
				]
			]
		];
	}
}
