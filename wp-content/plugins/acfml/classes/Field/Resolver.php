<?php

namespace ACFML\Field;

use ACFML\Convertable\LinkFieldData;
use WPML\FP\Fns;
use WPML\FP\Logic;
use WPML\FP\Obj;
use WPML\FP\Str;
use WPML\FP\Type;

class Resolver {

	/**
	 * @param \WPML_ACF_Processed_Data $processed_data
	 *
	 * @return \WPML_ACF_Field
	 */
	public function run( \WPML_ACF_Processed_Data $processed_data ) {
		$fieldType = $this->getFieldType( $processed_data->meta_data );
		return $this->runByType( $processed_data, $fieldType );
	}

	/**
	 * @param  \WPML_ACF_Processed_Data $processed_data
	 * @param  string|null              $fieldType
	 *
	 * @return \WPML_ACF_Field
	 */
	private function runByType( \WPML_ACF_Processed_Data $processed_data, $fieldType ) {
		switch ( $fieldType ) {
			case 'post_object':
				return new \WPML_ACF_Post_Object_Field( $processed_data, new \WPML_ACF_Post_Ids() );
			case 'page_link':
				return new \WPML_ACF_Page_Link_Field( $processed_data, new \WPML_ACF_Post_Ids() );
			case 'relationship':
				return new \WPML_ACF_Relationship_Field( $processed_data, new \WPML_ACF_Post_Ids() );
			case 'taxonomy':
				return new \WPML_ACF_Taxonomy_Field( $processed_data, new \WPML_ACF_Term_Ids() );
			case 'gallery':
				return new \WPML_ACF_Gallery_Field( $processed_data, new \WPML_ACF_Post_Ids() );
			case 'link':
				return new \WPML_ACF_Link_Field( $processed_data, new LinkFieldData() );
		}

		return new \WPML_ACF_Void_Field( $processed_data );
	}

	/**
	 * @param  array $metaData
	 *
	 * @return string|null
	 */
	private function getFieldType( $metaData ) {
		if ( Obj::prop( 'type', $metaData ) ) {
			return Obj::prop( 'type', $metaData );
		}

		$fieldKey = Obj::prop( 'key', $metaData );
		if ( null === $fieldKey ) {
			return null;
		}

		$objectId = $this->getObjectId( $metaData );
		if ( null === $objectId ) {
			return null;
		}

		return Logic::ifElse( Type::isArray(), Obj::prop( 'type' ), Fns::always( null ), get_field_object( $fieldKey, $objectId ) );
	}

	/**
	 * @param  array $metaData
	 *
	 * @return int|string|null
	 */
	private function getObjectId( $metaData ) {
		$context = Obj::prop( 'context', $metaData );
		switch ( $context ) {
			case \WPML_ACF_Worker::METADATA_CONTEXT_TERM_FIELD:
				// Passing term_XX to get_field_object will get field values for the term with term_id XX.
				return Logic::ifElse( Logic::isNotNull(), Str::concat( 'term_' ), Fns::identity(), Obj::prop( 'master_term_id', $metaData ) );
			case \WPML_ACF_Worker::METADATA_CONTEXT_POST_FIELD:
			default:
				return Obj::prop( 'master_post_id', $metaData );
		}
	}

}
