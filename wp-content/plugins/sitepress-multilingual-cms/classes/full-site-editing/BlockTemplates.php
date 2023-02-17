<?php

namespace WPML\FullSiteEditing;

use WPML\Element\API\PostTranslations;
use WPML\FP\Lst;
use WPML\FP\Obj;
use WPML\LIB\WP\Hooks;
use WPML\LIB\WP\Post;
use function WPML\FP\spreadArgs;

class BlockTemplates implements \IWPML_Backend_Action, \IWPML_Frontend_Action, \IWPML_REST_Action {

	public function add_hooks() {
		Hooks::onFilter( 'wpml_tm_translation_job_data', 10, 2 )
		     ->then( spreadArgs( [ self::class, 'doNotTranslateTitle' ] ) );

		Hooks::onFilter( 'wpml_pre_save_pro_translation', 10, 2 )
		     ->then( spreadArgs( [ self::class, 'copyOriginalTitleToTranslation' ] ) );

		Hooks::onAction( "rest_after_insert_wp_template", 10, 1 )
		     ->then( spreadArgs( [ self::class, 'syncPostName' ] ) );

		Hooks::onAction( "rest_after_insert_wp_template_part", 10, 1 )
		     ->then( spreadArgs( [ self::class, 'syncPostName' ] ) );

	}

	/**
	 * @param array  $package
	 * @param object $post
	 *
	 * @return array
	 */
	public static function doNotTranslateTitle( array $package, $post ) {
		if ( Lst::includes( Obj::prop( 'post_type', $post ), [ 'wp_template', 'wp_template_part' ] ) ) {
			$package = Obj::assocPath( [ 'contents', 'title', 'translate' ], 0, $package );
		}

		return $package;
	}

	/**
	 * @param array  $postData
	 * @param object $job
	 *
	 * @return array
	 */
	public static function copyOriginalTitleToTranslation( $postData, $job ) {
		if ( Lst::includes(
			Obj::prop( 'original_post_type', $job ),
			[ 'post_wp_template', 'post_wp_template_part' ]
		) ) {
			// WordPress looks up the post by 'name' so we need the translation to have the same name.
			$post                   = Post::get( $job->original_doc_id );
			$postData['post_title'] = $post->post_title;
			$postData['post_name']  = $post->post_name;
		}

		return $postData;
	}

	/**
	 * @param \WP_Post $post Inserted or updated post object.
	 */
	public static function syncPostName( \WP_Post $post ) {

		wpml_collect( PostTranslations::get( $post->ID ) )
			->reject( Obj::prop( 'original' ) )
			->pluck( 'element_id' )
			->map( function ( $postId ) use ( $post ) {
				global $wpdb;
				$wpdb->update( $wpdb->prefix . 'posts', [ 'post_name' => $post->post_name ], [ 'ID' => $postId ] );
			} );
	}

}