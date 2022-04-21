<?php

namespace WPML\TM\Jobs\Dispatch;

use WPML\Element\API\Post;
use WPML\FP\Obj;
use WPML\FP\Str;

class Posts extends Elements {

	public static function dispatch(
		callable $sendBatch,
		Messages $messages,
		callable $buildBatch,
		$data,
		$type = 'post'
	) {
		parent::dispatch( $sendBatch, $messages, $buildBatch, $data, $type );
	}

	protected static function filterElements( Messages $messages, $postsData, $targetLanguages ) {
		$ignoredPostsMessages = [];
		$postsToTranslation   = [];

		foreach ( $postsData as $postId => $postData ) {
			$postsToTranslation[ $postId ] = [
				'type'             => $postData['type'],
				'media'            => Obj::propOr( [], 'media-translation', $postData ),
				'target_languages' => []
			];

			$post     = self::getPost( $postId );
			$postLang = Post::getLang( $postId );


			foreach ( $targetLanguages as $language ) {
				if ( $postLang === $language ) {
					$ignoredPostsMessages [] = $messages->ignoreOriginalPostMessage( $post, $language );
					continue;
				}

				if ( self::hasInProgressJob( $post->ID, $post->post_type, $language ) ) {
					$ignoredPostsMessages [] = $messages->ignoreInProgressPostMessage( $post, $language );
					continue;
				}

				$postsToTranslation[ $postId ]['target_languages'] [] = $language;
			}
		}

		return [ $postsToTranslation, $ignoredPostsMessages ];
	}

	private static function getPost( $postId ) {
		return Str::includes( 'external_', $postId ) ?
			apply_filters( 'wpml_get_translatable_item', null, $postId ) :
			get_post( $postId );
	}
}
