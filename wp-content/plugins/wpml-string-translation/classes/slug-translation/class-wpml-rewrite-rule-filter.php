<?php

class WPML_Rewrite_Rule_Filter implements IWPML_ST_Rewrite_Rule_Filter {
	/** @var WPML_ST_Slug_Translation_Custom_Types_Repository[] */
	private $custom_types_repositories;

	/** @var WPML_ST_Slug_New_Match_Finder */
	private $new_match_finder;

	/**
	 * @param WPML_ST_Slug_Translation_Custom_Types_Repository[] $custom_types_repositories
	 * @param WPML_ST_Slug_New_Match_Finder                      $new_match_finder
	 */
	public function __construct( array $custom_types_repositories, WPML_ST_Slug_New_Match_Finder $new_match_finder ) {
		$this->custom_types_repositories = $custom_types_repositories;
		$this->new_match_finder          = $new_match_finder;
	}


	/**
	 * @param array|false|null $rules
	 *
	 * @return array
	 */
	function rewrite_rules_filter( $rules ) {
		if ( ! is_array( $rules ) && empty( $rules ) ) {
			return $rules;
		}

		$custom_types = $this->get_custom_types();
		if ( ! $custom_types ) {
			return $rules;
		}

		$result = array();
		foreach ( $rules as $match => $query ) {
			$new_match                         = $this->new_match_finder->get( $match, $custom_types );
			$result[ $new_match->get_value() ] = $query;

			if ( $new_match->should_preserve_original() ) {
				$result[ $match ] = $query;
			}
		}

		return $result;
	}

	private function get_custom_types() {
		if ( empty( $this->custom_types_repositories ) ) {
			return array();
		}

		$types = array();
		foreach ( $this->custom_types_repositories as $repository ) {
			$types[] = $repository->get();
		}


		return call_user_func_array( "array_merge", $types );
	}
}
