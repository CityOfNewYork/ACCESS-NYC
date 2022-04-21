<?php

namespace WPML\PB\Elementor\Modules;

use WPML\FP\Obj;

class ModuleWithItemsFromConfig extends \WPML_Elementor_Module_With_Items {

	/** @var array $fields */
	private $fields = [];

	/** @var array $fieldDefinitions */
	private $fieldDefinitions = [];

	/** @var string $itemsField */
	private $itemsField;

	/**
	 * @param string $itemsField
	 * @param array  $config
	 */
	public function __construct( $itemsField, array $config ) {
		$this->itemsField = $itemsField;
		$this->init( $config );
	}

	private function init( array $config ) {
		foreach ( $config as $key => $fieldConfig ) {
			$field = Obj::prop( 'field', $fieldConfig );
			$keyOf = is_string( $key ) ? $key : null;

			if ( $keyOf ) {
				$this->fields[ $keyOf ] = [ $field ];
			} else {
				$this->fields[] = $field;
			}

			$this->fieldDefinitions[ $field ] = $fieldConfig;
		}
	}

	private function getFieldData( $field, $key ) {
		return Obj::path( [ $field, $key ], $this->fieldDefinitions );
	}

	/**
	 * @inheritDoc
	 */
	public function get_title( $field ) {
		return $this->getFieldData( $field, 'type' );
	}

	/**
	 * @inheritDoc
	 */
	public function get_fields() {
		return $this->fields;
	}

	/**
	 * @inheritDoc
	 */
	public function get_editor_type( $field ) {
		return $this->getFieldData( $field, 'editor_type' );
	}

	/**
	 * @inheritDoc
	 */
	public function get_items_field() {
		return $this->itemsField;
	}
}
