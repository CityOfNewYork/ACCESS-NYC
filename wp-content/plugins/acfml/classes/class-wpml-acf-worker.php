<?php

use WPML\API\Sanitize;
use WPML\FP\Obj;

class WPML_ACF_Worker implements \IWPML_Backend_Action, \IWPML_Frontend_Action, \IWPML_DIC_Action {

	const META_TYPE_POST = 'post';
	const META_TYPE_TERM = 'term';

	const METADATA_CONTEXT_POST_FIELD = 'custom_field';
	const METADATA_CONTEXT_TERM_FIELD = 'term_field';

	/**
	 * @var \ACFML\Field\Resolver
	 */
	private $fieldResolver;

	/**
	 * WPML_ACF_Worker constructor.
	 *
	 * @param \ACFML\Field\Resolver $fieldResolver
	 */
	public function __construct( \ACFML\Field\Resolver $fieldResolver ) {
		$this->fieldResolver = $fieldResolver;
	}

	/**
	 * Registers WP hooks.
	 */
	public function add_hooks() {
		add_filter( 'wpml_duplicate_generic_string', [ $this, 'translateMetaValue' ], 10, 3 );
		add_filter( 'wpml_sync_parent_for_post_type', [ $this, 'sync_parent_for_post_type' ], 10, 2 );
		add_action( 'wpml_after_copy_custom_field', [ $this, 'after_copy_custom_field' ], 10, 3 );
		add_action( 'wpml_after_copy_term_field', [ $this, 'after_copy_term_field' ], 10, 3 );
	}

	/**
	 * When a custom field has been copied, adjusts its values to represent translated objects.
	 *
	 * @param int    $post_id_from The ID of the original post.
	 * @param int    $post_id_to   The ID of the translated post.
	 * @param string $meta_key     The meta key of the copied custom field.
	 */
	public function after_copy_custom_field( $post_id_from, $post_id_to, $meta_key ) {
		$this->afterCopyObjectField( $post_id_from, $post_id_to, $meta_key, self::META_TYPE_POST, get_post_type( $post_id_to ) );
	}

	/**
	 * When a term field has been copied, adjusts its values to represent translated objects.
	 *
	 * @param int    $term_id_from The term_id of the original term.
	 * @param int    $term_id_to   The term_id of the translated term.
	 * @param string $meta_key     The meta key of the copied term field.
	 */
	public function after_copy_term_field( $term_id_from, $term_id_to, $meta_key ) {
		$this->afterCopyObjectField( $term_id_from, $term_id_to, $meta_key, self::META_TYPE_TERM, get_term( $term_id_to )->taxonomy );
	}

	/**
	 * When an object field has been copied, adjusts its values to represent translated objects.
	 *
	 * @param int    $objectFromId The id of the original object: ID for posts and term_id for terms.
	 * @param int    $objectToId   The id of the translated object: ID for posts and term_id for terms.
	 * @param string $metaKey      The meta key of the copied object field.
	 * @param string $metaType     The type of object that the meta field is for: post or term.
	 * @param string $objectType   The type of the object holding the meta field: a post type slug or a taxonomy slug.
	 */
	private function afterCopyObjectField( $objectFromId, $objectToId, $metaKey, $metaType, $objectType ) {
		$field = acf_get_field( $metaKey );
		if ( ! $field ) {
			return;
		}

		$targetLang = $this->getTargetLang( $objectToId, $objectType );
		if ( ! $targetLang ) {
			return;
		}

		$metaValue          = get_metadata( $metaType, $objectToId, $metaKey, true );
		$metaValueConverted = $this->convertMetaValue( $metaValue, $metaKey, Obj::prop( 'type', $field ), $metaType, $objectFromId, $objectToId, $targetLang );

		if ( $metaValue !== $metaValueConverted ) {
			update_metadata( $metaType, $objectToId, $metaKey, $metaValueConverted, $metaValue );
		}
	}

	/**
	 * @param string      $metaValue
	 * @param string      $metaKey
	 * @param string|null $fieldType
	 * @param string      $metaType
	 * @param int|string  $objectFromId The ID of original object (post ID or term term_id).
	 * @param int|string  $objectToId   The ID of translated object.
	 * @param string      $targetLang
	 *
	 * @return mixed
	 */
	public function convertMetaValue( $metaValue, $metaKey, $fieldType, $metaType, $objectFromId, $objectToId, $targetLang ) {
		$metaData = $this->prepareMetaData( $metaValue, $metaKey, $fieldType, $metaType, $objectFromId, $objectToId );
		return $this->translateMetaValue( $metaValue, $targetLang, $metaData );
	}

	/**
	 * Prepares metadata for field value translation resolution.
	 *
	 * Note that there is no published standard when dealing with taxonomy data, including term meta.
	 *
	 * @param string      $metaValue    The meta value of processed custom field.
	 * @param string      $metaKey      The meta key of processed custom field.
	 * @param string|null $fieldType    The ACF type of the field.
	 * @param string      $metaType     The type of object that the meta field is for: post or term.
	 * @param int|string  $objectFromId The ID of original object (post ID or term term_id).
	 * @param int|string  $objectToId   The ID of translated object.
	 *
	 * @return array
	 */
	private function prepareMetaData( $metaValue, $metaKey, $fieldType, $metaType, $objectFromId, $objectToId ) {
		$isSerialized = is_serialized( $metaValue );
		$idKey        = sprintf( '%s_id', $metaType );
		$masterIdKey  = sprintf( 'master_%s_id', $metaType );
		return [
			'context'       => self::META_TYPE_TERM === $metaType ? self::METADATA_CONTEXT_TERM_FIELD : self::METADATA_CONTEXT_POST_FIELD,
			'attribute'     => 'value',
			'key'           => $metaKey,
			'type'          => $fieldType,
			'is_serialized' => $isSerialized,
			$idKey          => $objectToId,
			$masterIdKey    => $objectFromId,
		];
	}

	/**
	 * Synchronizes ACF field value during the meta duplicate/copy process.
	 *
	 * @param mixed  $metaValue  ACF value being copied.
	 * @param string $targetLang The target language.
	 * @param array  $metaData   Meta data of the value.
	 *
	 * @return mixed
	 */
	public function translateMetaValue( $metaValue, $targetLang, $metaData ) {
		$processedData = new WPML_ACF_Processed_Data( $metaValue, $targetLang, $metaData );
		return $this->resolveMetaValue( $processedData );
	}

	/**
	 * Converts IDs and stuff inside ACF field value.
	 *
	 * @param WPML_ACF_Processed_Data $processedData The data being processed.
	 *
	 * @return mixed
	 */
	private function resolveMetaValue( WPML_ACF_Processed_Data $processedData ) {
		$field = $this->fieldResolver->run( $processedData );
		return $field->convert_ids();
	}

	/**
	 * @param  bool   $sync
	 * @param  string $post_type
	 *
	 * @return bool
	 */
	public function sync_parent_for_post_type( $sync, $post_type ) {
		if ( 'acf-field' === $post_type || 'acf-field-group' === $post_type ) {
			$sync = false;
		}

		return $sync;
	}

	/**
	 * Returns target language code.
	 *
	 * @param int|string $target_object_id   The id of the translated object (post ID or term term_id).
	 * @param string     $target_object_type The post type or taxonomy slug.
	 *
	 * @return string|null The language code or null.
	 */
	private function getTargetLang( $target_object_id, $target_object_type ) {
		return apply_filters( 'wpml_element_language_code', null, [
			'element_id'   => $target_object_id,
			'element_type' => $target_object_type,
		] );
	}

}
