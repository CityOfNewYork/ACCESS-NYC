<?php

use WPML\FP\Fns;
use function WPML\FP\partial;

class WPML_ST_Slug_New_Match_Finder {
	/**
	 * @param string                     $match
	 * @param WPML_ST_Slug_Custom_Type[] $custom_types
	 *
	 * @return WPML_ST_Slug_New_Match
	 */
	public function get( $match, array $custom_types ) {
		$best_match = $this->find_the_best_match( $match, $this->map_to_new_matches( $match, $custom_types ) );
		if ( ! $best_match ) {
			$best_match = new WPML_ST_Slug_New_Match( $match, false );
		}

		return $best_match;
	}

	/**
	 * @param string                     $match
	 * @param WPML_ST_Slug_Custom_Type[] $custom_types
	 *
	 * @return WPML_ST_Slug_New_Match[]
	 */
	private function map_to_new_matches( $match, array $custom_types ) {
		return Fns::map( partial( [ $this, 'find_match_of_type' ], $match ), $custom_types );
	}

	/**
	 * @param string                   $match
	 * @param WPML_ST_Slug_Custom_Type $custom_type
	 *
	 * @return WPML_ST_Slug_New_Match
	 */
	public function find_match_of_type( $match, WPML_ST_Slug_Custom_Type $custom_type ) {
		if ( $custom_type->is_using_tags() ) {
			$slug             = $this->filter_slug_using_tag( $custom_type->get_slug() );
			$slug_translation = $this->filter_slug_using_tag( $custom_type->get_slug_translation() );

			$new_match = $this->adjust_match( $match, $slug, $slug_translation );

			$result = new WPML_ST_Slug_New_Match( $new_match, $custom_type->is_display_as_translated() );
		} else {
			$new_match = $this->adjust_match( $match, $custom_type->get_slug(), $custom_type->get_slug_translation() );
			$result    = new WPML_ST_Slug_New_Match(
				$new_match,
				$match !== $new_match && $custom_type->is_display_as_translated()
			);
		}

		return $result;
	}

	private function filter_slug_using_tag( $slug ) {
		if ( preg_match( '#%([^/]+)%#', $slug ) ) {
			$slug = preg_replace( '#%[^/]+%#', '.+?', $slug );
		}

		if ( preg_match( '#\.\+\?#', $slug ) ) {
			$slug = preg_replace( '#\.\+\?#', '(.+?)', $slug );
		}

		return $slug;
	}

	/**
	 * @param string $match
	 * @param string $slug
	 * @param string $slug_translation
	 *
	 * @return string
	 */
	private function adjust_match( $match, $slug, $slug_translation ) {
		if (
			! empty( $slug_translation )
			&& preg_match( '#^[^/]*/?\(?' . preg_quote( $slug ) . '\)?/#', $match )
			&& $slug !== $slug_translation
		) {
			$replace = function( $match ) use ( $slug, $slug_translation ) {
				return str_replace( $slug, $slug_translation, $match[0]);
			};
			$match = preg_replace_callback( '#^\(?' . preg_quote( addslashes( $slug ) ) . '\)?/#', $replace, $match );
		}

		return $match;
	}

	/**
	 * The best is that which differs the most from the original
	 *
	 * @param string                   $match
	 * @param WPML_ST_Slug_New_Match[] $new_matches
	 *
	 * @return WPML_ST_Slug_New_Match
	 */
	private function find_the_best_match( $match, $new_matches ) {
		$similarities = array();
		foreach ( $new_matches as $new_match ) {
			$percent = 0;
			similar_text( $match, $new_match->get_value(), $percent );
			// Multiply $percent by 100 because floats as array keys are truncated to integers
			// This will allow for fractional percentages.
			$similarities[ intval( $percent * 100 ) ] = $new_match;
		}

		ksort( $similarities );

		return reset( $similarities );
	}
}
