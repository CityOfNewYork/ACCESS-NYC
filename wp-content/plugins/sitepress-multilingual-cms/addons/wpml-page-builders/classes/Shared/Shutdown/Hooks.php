<?php

namespace WPML\PB\Shutdown;

class Hooks implements \IWPML_Frontend_Action, \IWPML_Backend_Action, \IWPML_DIC_Action {

	const PRIORITY_REGISTER_STRINGS          = 10;
	const PRIORITY_SAVE_TRANSLATIONS_TO_POST = 20;
	const PRIORITY_TRANSLATE_MEDIA           = 30;

	/** @var \WPML_PB_Integration $pbIntegration */
	private $pbIntegration;

	public function __construct( \WPML_PB_Integration $pbIntegration ) {
		$this->pbIntegration = $pbIntegration;
	}

	public function add_hooks() {
		add_action( 'shutdown', [ $this, 'registerStrings' ], self::PRIORITY_REGISTER_STRINGS );
		add_action( 'shutdown', [ $this->pbIntegration, 'save_translations_to_post' ], self::PRIORITY_SAVE_TRANSLATIONS_TO_POST );
		add_action( 'shutdown', [ $this, 'translateMedias' ], self::PRIORITY_TRANSLATE_MEDIA );
	}

	/**
	 * This applies only on original posts.
	 */
	public function registerStrings() {
		foreach( $this->pbIntegration->get_save_post_queue() as $post ) {
			$this->pbIntegration->register_all_strings_for_translation( $post );
		}
	}

	/**
	 * This applies only on post translations.
	 */
	public function translateMedias() {
		if ( defined( 'WPML_MEDIA_VERSION' ) ) {
			foreach( $this->pbIntegration->get_save_post_queue() as $post ) {
				$this->pbIntegration->translate_media( $post );
			}
		}
	}
}
