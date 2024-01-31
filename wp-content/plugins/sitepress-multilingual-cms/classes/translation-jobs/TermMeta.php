<?php

namespace WPML\TM\Jobs;

use WPML\FP\Fns;
use WPML\FP\Obj;
use WPML\FP\Str;
use function WPML\FP\pipe;

class TermMeta {
	/**
	 * It returns translated term description stored inside wp_icl_translate
	 *
	 * @param int $iclTranslateJobId
	 * @param int $termTaxonomyId
	 *
	 * @return string
	 */
	public static function getTermDescription( $iclTranslateJobId, $termTaxonomyId ) {
		global $wpdb;

		$sql = "SELECT field_data_translated
				FROM {$wpdb->prefix}icl_translate
				WHERE job_id = %d AND field_type = 'tdesc_%d'";

		$description = $wpdb->get_var( $wpdb->prepare( $sql, $iclTranslateJobId, $termTaxonomyId ) );

		return $description ? base64_decode( $description ) : '';
	}

	/**
	 * It returns term meta stored inside wp_icl_translate table.
	 *
	 * Data has such format:
	 * [
	 *   (object)[
	 *     field_type => 'some_scalar_field',
	 *     field_data_translated => 'Translated value'
	 *   ],
	 *   (object)[
	 *      field_type => 'some_array_valued_field_like_checkboxes'
	 *      field_data_translated => [
	 *         'Translated option 1', 'Translated option 2', 'Translated option 3'
	 *      ]
	 *   ],
	 *   (object)[
	 *      field_type => 'another_array_valued_field_like_checkboxes'
	 *      field_data_translated => [
	 *         'option1' => ['Translated option 1'],
	 *         'option2' => ['Translated option 2'],
	 *      ]
	 *   ]
	 * ]
	 *
	 * @param int $iclTranslateJobId
	 * @param int $term_taxonomy_id
	 *
	 * @return array
	 */
	public static function getTermMeta( $iclTranslateJobId, $term_taxonomy_id ) {
		return array_merge(
			self::geRegularTermMeta( $iclTranslateJobId, $term_taxonomy_id ),
			self::getTermMetaWithArrayValue( $iclTranslateJobId, $term_taxonomy_id )
		);
	}

	/**
	 * It returns term meta which have scalar values
	 *
	 * @param int $iclTranslateJobId
	 * @param int $termTaxonomyId
	 *
	 * @return mixed[]
	 */
	private static function geRegularTermMeta( $iclTranslateJobId, $termTaxonomyId ) {
		global $wpdb;

		$sql = "SELECT field_data_translated, field_type
				FROM {$wpdb->prefix}icl_translate
				WHERE job_id = %d AND field_type LIKE 'tfield-%-%d'";

		$rowset = $wpdb->get_results( $wpdb->prepare( $sql, $iclTranslateJobId, $termTaxonomyId ) );

		return Fns::map( Obj::over( Obj::lensProp( 'field_data_translated' ), 'base64_decode' ), $rowset );
	}

	/**
	 * It returns term meta with array values grouped by term name.
	 *
	 *  Custom field created by Toolset Types example:
	 *
	 *  A term has checkboxes field with options: A, B, and C. They are stored in wp_icl_translate table as 3 entries under such field_type:
	 *   - tfield-wpcf-jakub-checkboxes-13_wpcf-fields-checkboxes-option-6c88acb978ec7f24eb6a2bb12fc2d1c4-1_0
	 *   - tfield-wpcf-jakub-checkboxes-13_wpcf-fields-checkboxes-option-6cdwwdwdwdwdwwdwddwd2bb12fc2d1c4-1_0
	 *   - tfield-wpcf-jakub-checkboxes-13_wpcf-fields-checkboxes-option-611111wdwdwdwwdwddwd2bb12fc2d1c4-1_0
	 *
	 *  Options translations are A fr, B fr, and C fr.
	 *
	 *  Our goal is to group them into one entry:
	 *  (object) [
	 *     field_type => 'tfield-wpcf-jakub-checkboxes-13'
	 *     field_data_translated => [
	 *        wpcf-fields-checkboxes-option-6c88acb978ec7f24eb6a2bb12fc2d1c4-1 => [
	 *           0 => 'A fr',
	 *        ],
	 *        wpcf-fields-checkboxes-option-6cdwwdwdwdwdwwdwddwd2bb12fc2d1c4-1 => [
	 *           0 => 'B fr',
	 *        ],
	 *        wpcf-fields-checkboxes-option-611111wdwdwdwwdwddwd2bb12fc2d1c4-1 => [
	 *           0 => 'C fr',
	 *        ],
	 *     ]
	 *  ]
	 *
	 *  Custom field created by ACF example:
	 *
	 *  ACF stores data in a slightly different way. Again, A term has checkboxes field with options A, B, C with the same translations A fr, B fr, C fr.
	 *  They are stored in wp_icl_translate in this way:
	 *  - tfield-jakub_checkboxes-13_0
	 *  - tfield-jakub_checkboxes-13_1
	 *  - tfield-jakub_checkboxes-13_2
	 *
	 *  Our goal is to group them into one entry:
	 *  (object)[
	 *     field_type => 'tfield-jakub-checkboxes-13',
	 *     field_data_translated => [
	 *       0 => 'A fr',
	 *       1 => 'B fr',
	 *       2 => 'C fr'
	 *     ]
	 *  ]
	 *
	 * @param int $iclTranslateJobId
	 * @param int $termTaxonomyId
	 *
	 * @return mixed[]
	 */
	private static function getTermMetaWithArrayValue( $iclTranslateJobId, $termTaxonomyId ) {
		global $wpdb;

		$sql = "SELECT field_data_translated, field_type
				FROM {$wpdb->prefix}icl_translate
				WHERE job_id = %d AND field_type LIKE 'tfield-%-%d_%'";

		$rowset = $wpdb->get_results( $wpdb->prepare( $sql, $iclTranslateJobId, $termTaxonomyId ) );

		/**
		 * From field type like: tfield-wpcf-jakub-checkboxes-13_wpcf-fields-checkboxes-option-6c88acb978ec7f24eb6a2bb12fc2d1c4-1_0
		 * extracts core field name: wpcf-jakub-checkboxes-13
		 */
		$extractFieldName = pipe( Obj::prop( 'field_type' ), Str::match( '/tfield-(.*)-\d/U' ), Obj::prop( 1 ) );

		/**
		 * From field type like: tfield-wpcf-jakub-checkboxes-13_wpcf-fields-checkboxes-option-6c88acb978ec7f24eb6a2bb12fc2d1c4-1_0
		 * extracts option name part: wpcf-fields-checkboxes-option-6c88acb978ec7f24eb6a2bb12fc2d1c4-1_0
		 */
		$extractOptions = function ( $row, $fieldName ) {
			return Str::pregReplace( "/tfield-{$fieldName}-\d+_/U", '', $row->field_type );
		};

		$groupOptions = function ( $carry, $row ) use ( $extractFieldName, $extractOptions ) {
			$fieldName = $extractFieldName( $row );
			if ( ! isset( $carry[ $fieldName ] ) ) {
				$carry[ $fieldName ] = [];
			}

			/** @var string $options */
			$options = $extractOptions( $row, $fieldName );

			/**
			 * If field_type is: tfield-wpcf-jakub-checkboxes-13_wpcf-fields-checkboxes-option-6c88acb978ec7f24eb6a2bb12fc2d1c4-1_0
			 * then meta keys are: [wpcf-jakub-checkboxes-13, wpcf-fields-checkboxes-option-6c88acb978ec7f24eb6a2bb12fc2d1c4-1, 0]
			 */
			$metaKeys = array_merge( [ $fieldName ], explode( '_', $options ) );

			/**
			 * Builds array like:
			 * [wpcf-jakub-checkboxes-13 => [wpcf-fields-checkboxes-option-6c88acb978ec7f24eb6a2bb12fc2d1c4-1 => [0 => Translation_value ] ] ]
			 *
			 * If there are already data under wpcf-jakub-checkboxes-13 key, they are preserve too. The new values are appended.
			 */
			return Utils::insertUnderKeys( $metaKeys, $carry, base64_decode( $row->field_data_translated ) );
		};

		$recreateJobElement = function ( $data, $fieldType ) use ( $termTaxonomyId ) {
			return (object) [
				'field_type'            => 'tfield-' . $fieldType . '-' . $termTaxonomyId,
				'field_data_translated' => $data,
			];
		};

		return Obj::values( Fns::map( $recreateJobElement, Fns::reduce( $groupOptions, [], $rowset ) ) );
	}
}
