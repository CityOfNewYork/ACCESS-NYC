<?php

namespace WPML\TM\PostEditScreen\Endpoints;

use WPML\Ajax\IHandler;
use WPML\Collect\Support\Collection;
use WPML\FP\Right;
use WPML_TM_Post_Edit_TM_Editor_Mode;

class SetEditorMode implements IHandler {

	/** @var \SitePress $sitepress */
	private $sitepress;

	public function __construct( \SitePress $sitepress ) {
		$this->sitepress = $sitepress;
	}

	public function run( Collection $data ) {
		$tmSettings = $this->sitepress->get_setting( 'translation-management' );

		$useNativeEditor = $data->get( 'enabledEditor' ) !== 'wpml';
		$postId          = $data->get( 'postId' );
		$editorModeFor   = $data->get( 'editorModeFor' );

		switch ( $editorModeFor ) {
			case 'global':
				$tmSettings[ WPML_TM_Post_Edit_TM_Editor_Mode::TM_KEY_GLOBAL_USE_NATIVE ]        = $useNativeEditor;
				$tmSettings[ WPML_TM_Post_Edit_TM_Editor_Mode::TM_KEY_FOR_POST_TYPE_USE_NATIVE ] = [];
				$this->sitepress->set_setting( 'translation-management', $tmSettings, true );
				WPML_TM_Post_Edit_TM_Editor_Mode::delete_all_posts_option();
				break;

			case 'all_posts_of_type':
				$post_type = get_post_type( $postId );

				if ( $post_type ) {
					$tmSettings[ WPML_TM_Post_Edit_TM_Editor_Mode::TM_KEY_FOR_POST_TYPE_USE_NATIVE ][ $post_type ] = $useNativeEditor;
					$this->sitepress->set_setting( 'translation-management', $tmSettings, true );
					WPML_TM_Post_Edit_TM_Editor_Mode::delete_all_posts_option( $post_type );
				}
				break;

			case 'this_post':
				update_post_meta(
					$postId,
					WPML_TM_Post_Edit_TM_Editor_Mode::POST_META_KEY_USE_NATIVE,
					$useNativeEditor ? 'yes' : 'no'
				);
				break;
		}

		return Right::of( true );
	}
}
