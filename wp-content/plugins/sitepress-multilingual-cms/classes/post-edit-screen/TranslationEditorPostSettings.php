<?php

namespace WPML\TM\PostEditScreen;

use WPML\Element\API\PostTranslations;
use WPML\Element\API\Translations;
use WPML\FP\Fns;
use WPML\LIB\WP\Hooks;
use WPML\TM\PostEditScreen\Endpoints\SetEditorMode;
use WPML\Core\WP\App\Resources;
use function WPML\FP\spreadArgs;

class TranslationEditorPostSettings {
	private $sitepress;

	public function __construct( $sitepress ) {
		$this->sitepress = $sitepress;
	}

	public function add_hooks() {
		Hooks::onAction( 'admin_enqueue_scripts' )
		     ->then( [ $this, 'localize' ] )
		     ->then( Resources::enqueueApp( 'postEditTranslationEditor' ) );

		$render = Fns::once( spreadArgs( [ $this, 'render' ] ) );

		Hooks::onAction( 'wpml_before_post_edit_translations_table' )
		     ->then( $render );

		Hooks::onAction( 'wpml_before_post_edit_translations_summary' )
		     ->then( $render );
	}

	public static function localize() {
		return [
			'name' => 'wpml_translation_post_editor',
			'data' => [
				'endpoints' => [
					'setEditorMode' => SetEditorMode::class,
				],
			],
		];
	}

	public function render( $post ) {
		global $wp_post_types;

		if ( ! Translations::isOriginal( $post->ID, PostTranslations::get( $post->ID ) ) ) {
			return;
		}

		list( $useTmEditor, $isWpmlEditorBlocked, $reason ) = \WPML_TM_Post_Edit_TM_Editor_Mode::get_editor_settings( $this->sitepress, $post->ID );
		$enabledEditor  = $useTmEditor && ! $isWpmlEditorBlocked ? 'wpml' : 'native';
		$postTypeLabels = $wp_post_types[ $post->post_type ]->labels;

		echo '<div id="translation-editor-post-settings" data-post-id="' . $post->ID . '" data-post-type="' . $post->post_type . '" data-enabled-editor="' . $enabledEditor . '" data-is-wpml-blocked="' . $isWpmlEditorBlocked . '" data-wpml-blocked-reason="' . $reason . '" data-type-singular="' . $postTypeLabels->singular_name . '" data-type-plural="' . $postTypeLabels->name . '"></div>';
	}
}