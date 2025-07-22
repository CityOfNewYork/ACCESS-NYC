<?php

namespace WPML\PB\Elementor\Hooks;

class DisplayConditions implements \IWPML_Frontend_Action {

	/** @var array|null */
	private $conditionConfig;

	public function add_hooks() {
		add_filter( 'elementor/frontend/builder_content_data', [ $this, 'convertDisplayConditions' ], 10, 2 );
	}

	/**
	 * @return array
	 */
	private function getConditionConfig() {
		if ( ! isset( $this->conditionConfig ) ) {
			/**
			 * Filter the display conditions IDs that should be converted.
			 *
			 * @since 2.3.0
			 *
			 * @param array $conditionConfig {
			 *     @type array $condition_name {
			 *         @type string $field Field name containing the IDs to convert
			 *         @type string $type  Type of content ('term' or 'post')
			 *     }
			 * }
			 */
			$this->conditionConfig = apply_filters(
				'wpml_elementor_display_conditions_ids_to_convert',
				[
					'in_categories'         => [
						'field' => 'categories',
						'type'  => 'term',
					],
					'archive_of_categories' => [
						'field' => 'categories',
						'type'  => 'term',
					],
					'in_tags'               => [
						'field' => 'tags',
						'type'  => 'term',
					],
					'archive_of_tags'       => [
						'field' => 'tags',
						'type'  => 'term',
					],
					'page_parent'           => [
						'field' => 'pages',
						'type'  => 'post',
					],
				]
			);
		}

		return $this->conditionConfig;
	}

	/**
	 * @param array $dataArray
	 * @param int   $postId
	 *
	 * @return array
	 */
	public function convertDisplayConditions( $dataArray, $postId ) {
		foreach ( $dataArray as &$data ) {
			if ( isset( $data['settings']['e_display_conditions'] ) && is_array( $data['settings']['e_display_conditions'] ) ) {
				$data['settings']['e_display_conditions'] = $this->processDisplayConditions( $data['settings']['e_display_conditions'] );
			}

			if ( isset( $data['elements'] ) && is_array( $data['elements'] ) ) {
				$data['elements'] = $this->convertDisplayConditions( $data['elements'], $postId );
			}
		}

		return $dataArray;
	}

	/**
	 * @param array $conditions
	 *
	 * @return array
	 */
	private function processDisplayConditions( $conditions ) {
		return array_map(
			function( $conditionJson ) {
				$condition = $this->parseConditionJson( $conditionJson );

				if ( null !== $condition ) {
					  $condition     = $this->convertCondition( $condition );
					  $conditionJson = wp_json_encode( $condition );
				}

				return $conditionJson;
			},
			$conditions
		);
	}

	/**
	 * @param string $conditionJson
	 *
	 * @return array|null
	 */
	private function parseConditionJson( $conditionJson ) {
		$condition = json_decode( $conditionJson, true );
		if ( json_last_error() === JSON_ERROR_NONE && is_array( $condition ) ) {
			return $condition;
		}
		return null;
	}

	/**
	 * @param array $condition
	 *
	 * @return array
	 */
	private function convertCondition( $condition ) {
		return array_map(
			function( $conditionGroup ) {
				return $this->convertConditionIds( $conditionGroup );
			},
			$condition
		);
	}

	/**
	 * @param array $conditionGroups
	 * 
	 * @return array
	 */
	private function convertConditionIds( $conditionGroups ) {
		$isSingleLegacyCondition = $conditionGroups && array_keys( $conditionGroups )[0] !== 0;

		$result = $isSingleLegacyCondition ? [ $conditionGroups ] : $conditionGroups;

		foreach ( $result as $key => $conditionGroup ) {
			$configForCondition = $this->getConfigForCondition( $conditionGroup['condition'] );

			if ( $configForCondition && isset( $conditionGroup[ $configForCondition['field'] ] ) ) {
				$result[ $key ] = $this->convertIdsForCondition( $conditionGroup, $configForCondition );
			}
		}

		return $isSingleLegacyCondition ? $result[0] : $result;
	}

	/**
	 * @param array $conditionGroup
	 * @param array $configForCondition
	 * 
	 * @return array
	 */
	private function convertIdsForCondition( $conditionGroup, $configForCondition ) {
		$conditionGroup[ $configForCondition['field'] ] = array_map(
			function( $term ) use ( $configForCondition ) {
				if ( 'term' === $configForCondition['type'] ) {
					return $this->convertTermId( $term );
				} else {
					return $this->convertPostId( $term );
				}
			},
			$conditionGroup[ $configForCondition['field'] ]
		);

		return $conditionGroup;
	}

	/**
	 * @param string $condition
	 * 
	 * @return array|null
	 */
	private function getConfigForCondition( $condition ) {
		$allConfig = $this->getConditionConfig();
		return array_key_exists( $condition, $allConfig ) ? $allConfig[ $condition ] : null;
	}

	/**
	 * @param array $term
	 * 
	 * @return array
	 */
	private function convertTermId( $term ) {
		if ( ! isset( $term['id'] ) ) {
			return $term;
		}

		$termObj = get_term( $term['id'] );

		if ( $termObj instanceof \WP_Term ) {
			$term['id'] = $this->convertId( $term['id'], $termObj->taxonomy );
		}

		return $term;
	}

	/**
	 * @param array $item
	 * 
	 * @return array
	 */
	private function convertPostId( $item ) {
		if ( ! isset( $item['id'] ) ) {
			return $item;
		}

		$item['id'] = $this->convertId( $item['id'], 'post' );

		return $item;
	}

	/**
	 * @param int|string $elementId
	 * @param string     $elementType
	 *
	 * @return int|string
	 */
	private function convertId( $elementId, $elementType ) {
		return apply_filters( 'wpml_object_id', $elementId, $elementType, true );
	}

}
