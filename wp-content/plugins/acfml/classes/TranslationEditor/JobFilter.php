<?php

namespace ACFML\TranslationEditor;

use ACFML\FieldGroup\FieldNamePatterns;
use ACFML\Strings\Config;
use ACFML\Strings\Factory as StringsFactory;
use ACFML\Strings\Package;
use ACFML\Strings\TranslationJobFilter;
use WPML\FP\Fns;
use WPML\FP\Str;
use WPML\FP\Obj;

class JobFilter implements \IWPML_Backend_Action, \IWPML_Frontend_Action, \IWPML_DIC_Action {

	const ACF_TOP_LEVEL_GROUP_ID    = 'acf';
	const ACF_TOP_LEVEL_GROUP_TITLE = 'Advanced Custom Fields (ACF)';

	const SPECIAL_LABELS = [
		'cpt'          => 'Post Type',
		'taxonomy'     => 'Taxonomy',
		'options-page' => 'Options Page',
	];

	/** @var array */
	private $jobToGroupId = [];

	/**
	 * @var FieldNamePatterns $fieldNamePatterns
	 */
	private $fieldNamePatterns;

	public function __construct( FieldNamePatterns $fieldNamePatterns ) {
		$this->fieldNamePatterns = $fieldNamePatterns;
	}

	public function add_hooks() {
		add_filter( 'wpml_tm_adjust_translation_fields', [ $this, 'addTitleAndGroupInfo' ], 10, 2 );
		add_filter( 'wpml_tm_adjust_translation_job', [ $this, 'reorderFields' ], 10, 2 );
	}

	/**
	 * @param array[]   $fields
	 * @param \stdClass $job
	 *
	 * @return array[]
	 */
	public function addTitleAndGroupInfo( $fields, $job ) {
		foreach ( $fields as &$field ) {
			$field = $this->processField( $field, $job );
		}

		return $fields;
	}

	/**
	 * @param array     $field
	 * @param \stdClass $job
	 *
	 * @return array
	 */
	private function processField( $field, $job ) {
		$fieldTitle                          = (string) Obj::prop( 'title', $field );
		$groupIdFromJob                      = $this->getGroupIdFromJob( $job );
		list( $groupId, , $namespace, $key ) = TranslationJobFilter::parseFieldName( $fieldTitle, $groupIdFromJob );
		$isSimpleLabel                       = $groupId && $namespace && $key;

		$matchSpecialLabels = function( $string ) {
			return wpml_collect( self::SPECIAL_LABELS )
				->keys()
				->first( Str::startsWith( Fns::__, $string ) );
		};

		if ( $isSimpleLabel ) {
			$label = Obj::prop( 'title', Config::get( $namespace, $key ) );
			$field = $this->handleFieldLabels( $field, $label, $fieldTitle, $groupId );
		} elseif ( $matchSpecialLabels( $fieldTitle ) ) {
			$prefix = $matchSpecialLabels( $fieldTitle );
			$field  = $this->handleSpecialLabels( $field, $prefix );
		} else {
			$field = $this->handleContent( $field, $job );
		}

		return $field;
	}

	/**
	 * @param array  $field
	 * @param string $label
	 * @param string $title
	 * @param int    $groupId
	 *
	 * @return array
	 */
	private function handleFieldLabels( $field, $label, $title, $groupId ) {
		$field['title'] = $label ?: $title;
		$field['group'] = [
			self::ACF_TOP_LEVEL_GROUP_ID => self::ACF_TOP_LEVEL_GROUP_TITLE,
		];

		$fieldGroup = acf_get_field_group( $groupId );

		$field['group'][ 'acf_labels_' . $groupId ] = sprintf( '%s Labels', $fieldGroup['title'] );

		return $field;
	}

	/**
	 * @param array  $field
	 * @param string $prefix
	 *
	 * @return array
	 */
	private function handleSpecialLabels( $field, $prefix ) {
		$field['group'] = [
			self::ACF_TOP_LEVEL_GROUP_ID => self::ACF_TOP_LEVEL_GROUP_TITLE,
			$prefix . '-labels'          => self::SPECIAL_LABELS[ $prefix ] . ' Labels',
		];
		$field['title'] = substr( $field['title'], strlen( $prefix ) );
		$field['title'] = preg_replace( '/-[0-9a-f]+$/', '', $field['title'] );
		$field['title'] = apply_filters( 'wpml_labelize_string', $field['title'] );
		$field['title'] = trim( $field['title'] );

		return $field;
	}

	/**
	 * @param array     $field
	 * @param \stdClass $job
	 *
	 * @return array
	 */
	private function handleContent( $field, $job ) {
		$fieldName   = Str::match( '/^field-(.*?)-\d+$/', $field['field_type'] );
		$customField = $fieldName ? $fieldName[1] : '';

		if ( $customField ) {
			return $this->handleCustomField( $field, $job, $customField );
		}

		return $field;
	}

	/**
	 * @param array     $field
	 * @param \stdClass $job
	 * @param string    $customField
	 *
	 * @return array
	 */
	private function handleCustomField( array $field, \stdClass $job, string $customField ) {
		$acfObject = get_field_object( $customField, $job->original_doc_id );

		if ( false !== $acfObject ) {
			$parentId   = $acfObject['parent'];
			$fieldGroup = acf_get_field_group( $parentId );

			return $this->handleAcfField( $field, $job, $acfObject, $fieldGroup );
		}

		return $field;
	}

	/**
	 * @param array       $field
	 * @param \stdClass   $job
	 * @param array       $acfObject
	 * @param array|false $fieldGroup
	 *
	 * @return array
	 */
	private function handleAcfField( array $field, \stdClass $job, array $acfObject, $fieldGroup ) {
		if ( ! $fieldGroup ) {
			return $this->handleAcfSubField( $field, $job, $acfObject, acf_get_field_group( $this->getGroupIdWithPatterns( $acfObject['name'] ) ) );
		}

		$field['title'] = $acfObject['label'];
		$field['group'] = [
			self::ACF_TOP_LEVEL_GROUP_ID => self::ACF_TOP_LEVEL_GROUP_TITLE,
			$fieldGroup['key']           => $fieldGroup['title'],
		];

		return $field;
	}

	/**
	 * @param array       $field
	 * @param \stdClass   $job
	 * @param array       $acfObject
	 * @param array|false $fieldGroup
	 *
	 * @return array
	 */
	private function handleAcfSubField( array $field, \stdClass $job, array $acfObject, $fieldGroup = false ) {
		$parent = $acfObject['parent'];
		$title  = $acfObject['label'];
		$index  = '';

		$isTopLevelField = function( $id ) {
			return ! get_post_parent( $id );
		};

		while ( ! $isTopLevelField( $parent ) ) {
			$parentName = Str::match( '/^(.*)_(\d+)_' . $acfObject['_name'] . '$/', $acfObject['name'] );
			if ( $parentName ) {
				$index = ' #' . ( $parentName[2] + 1 );
			} else {
				$parentName = Str::match( '/^(.*)_' . $acfObject['_name'] . '$/', $acfObject['name'] );
			}

			$parentObject = get_field_object( $parentName[1], $job->original_doc_id );
			$fieldTitle   = $parentObject['label'] . $index;
			$title        = $fieldTitle . ' / ' . $title;

			$parent    = $parentObject['parent'];
			$acfObject = $parentObject;
		}

		$field['title'] = $title;
		$field['group'] = [
			self::ACF_TOP_LEVEL_GROUP_ID => self::ACF_TOP_LEVEL_GROUP_TITLE,
		];

		if ( $fieldGroup && isset( $fieldGroup['key'], $fieldGroup['title'] ) ) {
			$field['group'][ $fieldGroup['key'] ] = $fieldGroup['title'];
		}

		return $field;
	}

	/**
	 * @param string $name
	 *
	 * @return int|null
	 */
	private function getGroupIdWithPatterns( string $name ) {
		return $this->fieldNamePatterns->findMatchingGroup( $name );
	}

	/**
	 * @param \stdClass $job
	 *
	 * @return int|null
	 */
	private function getGroupIdFromJob( $job ) {
		if ( ! array_key_exists( $job->original_doc_id, $this->jobToGroupId ) ) {
			if ( 'package_' . Package::KIND_SLUG === $job->original_post_type ) {
				$this->jobToGroupId[ $job->original_doc_id ] = (int) StringsFactory::createWpmlPackage( $job->original_doc_id )->name;
			} else {
				$this->jobToGroupId[ $job->original_doc_id ] = null;
			}
		}

		return $this->jobToGroupId[ $job->original_doc_id ];
	}

	/**
	 * @param array     $jobFields
	 * @param \stdClass $job
	 *
	 * @return array
	 */
	public function reorderFields( $jobFields, $job ) {
		$postType = Obj::prop( 'original_post_type', $job );
		if ( Str::startsWith( 'package', $postType ) ) {
			return $jobFields;
		}

		$postId   = Obj::prop( 'original_doc_id', $job );
		$metaKeys = get_field_objects( $postId, false );
		if ( ! $metaKeys ) {
			return $jobFields;
		}

		$orderedFields = $this->getOrderedFields( $postId, $metaKeys );
		$orderMap      = array_flip( $orderedFields );

		return wpml_collect( $jobFields )
			->sort( function( $a, $b ) use ( $orderMap ) {
				$keyA = $this->getKey( $a );
				$keyB = $this->getKey( $b );

				// Leave non ACF fields intact.
				if ( ! isset( $orderMap[ $keyA ] ) || ! isset( $orderMap[ $keyB ] ) ) {
					return 0;
				}

				return $orderMap[ $keyA ] - $orderMap[ $keyB ];
			} )
			->values()
			->all();
	}

	/**
	 * @param array $field
	 *
	 * @return string
	 */
	private function getKey( $field ) {
		$element = Obj::path( [ 'attributes', 'id' ], $field );

		return Str::pregReplace( [ '/^field-/', '/-0$/' ], '', $element );
	}

	/**
	 * @param string $postId
	 * @param array  $metaKeys
	 *
	 * @return string[]
	 */
	private function getOrderedFields( $postId, $metaKeys ) {
		$orderedFields = [];

		$iterate = function( $key, $value, $prefix = '' ) use ( &$orderedFields, &$iterate, $postId ) {
			if ( is_array( $value ) ) {
				foreach ( $value as $subKey => $subValue ) {
					$newPrefix = $prefix;
					if ( is_numeric( $subKey ) ) {
						$newPrefix .= '_' . $subKey;
					} elseif ( 'acf_fc_layout' !== $subKey ) {
						$field_object = get_field_object( $subKey, $postId, false, false );
						if ( $field_object ) {
								$newPrefix .= '_' . $field_object['name'];
						}
					}

					$iterate( $subKey, $subValue, $newPrefix );
				}
			} elseif ( 'acf_fc_layout' !== $key ) {
				$orderedFields[] = $prefix;
			}
		};

		foreach ( $metaKeys as $metaKey => $metaValue ) {
			$iterate( $metaKey, $metaValue['value'], $metaValue['name'] );
		}

		return $orderedFields;
	}

}
