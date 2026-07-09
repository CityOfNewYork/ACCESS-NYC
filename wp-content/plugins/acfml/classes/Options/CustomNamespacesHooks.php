<?php

namespace ACFML\Options;

use WPML\FP\Relation;
use WPML\FP\Str;

class CustomNamespacesHooks implements \IWPML_Action {

	public function add_hooks() {
		add_filter( 'acf/validate_post_id', [ $this, 'appendLanguageToCustomNamespace' ] );
	}

	/**
	 * @param string|int $postId
	 *
	 * @return string|int
	 */
	public function appendLanguageToCustomNamespace( $postId ) {
		if ( is_string( $postId )
			&& ! is_numeric( $postId )
			&& ! self::isRestrictedNamespace( $postId )
			&& ! self::isCommonNamespace( $postId )
			&& self::isValidOptionPagePostId( $postId )
		) {
			$cl = acf_get_setting( 'current_language' );
			$dl = acf_get_setting( 'default_language' );

			if ( ! $this->hasLanguageAppended( $postId, $cl ) && $cl !== $dl ) {
				$postId .= '_' . $cl;
			}
		}

		return $postId;
	}

	/**
	 * @param string|int $postId
	 *
	 * @return bool
	 */
	private static function isRestrictedNamespace( $postId ) {
		// This list describes why a string may be a restricted namespace.
		$restricted = [
			'new_post' => 'The post id is new_post so it is fake id used in acf_form function when creating new post with ACF fields.',
		];
		return array_key_exists( $postId, $restricted );
	}

	/**
	 * @param string|int $postId
	 *
	 * @return bool
	 */
	private static function isCommonNamespace( $postId ) {
		if ( Str::startsWith( 'options', $postId ) ) {
			return true;
		} elseif ( Str::startsWith( 'term_', $postId ) ) {
			return true;
		} elseif ( Str::startsWith( 'block_', $postId ) ) {
			return true;
		} elseif ( Str::startsWith( 'user_', $postId ) ) {
			return true;
		} elseif ( Str::startsWith( 'widget_', $postId ) ) {
			return true;
		}

		$taxonomies = get_taxonomies( [], 'names' );
		foreach ( $taxonomies as $taxonomy_name ) {
			if ( (bool) Str::startsWith( sprintf( '%s_', $taxonomy_name ), $postId ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param string|int $postId
	 *
	 * @return bool
	 */
	private static function isValidOptionPagePostId( $postId ) {
		if ( function_exists( 'acf_get_options_pages' ) ) {
			$optionPages = acf_get_options_pages();

			if ( $optionPages ) {
				return wpml_collect( acf_get_options_pages() )->first( Relation::propEq( 'post_id', $postId ) );
			}
		}

		return false;
	}

	/**
	 * @param string|int $postId
	 * @param string     $language
	 *
	 * @return bool
	 */
	private function hasLanguageAppended( $postId, $language ) {
		$suffix = '_' . $language;
		return Str::endsWith( $suffix, $postId ); /* @phpstan-ignore-line */
	}

}
