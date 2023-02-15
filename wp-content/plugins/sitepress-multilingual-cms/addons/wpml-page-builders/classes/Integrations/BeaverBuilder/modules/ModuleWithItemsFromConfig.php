<?php

namespace WPML\PB\BeaverBuilder\Modules;

use WPML\FP\Fns;
use WPML\FP\Lst;
use WPML\FP\Obj;

class ModuleWithItemsFromConfig extends \WPML_Beaver_Builder_Module_With_Items {

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
		$keyByField             = Fns::converge( Lst::zipObj(), [ Lst::pluck( 'field' ), Fns::identity() ] );
		$this->fieldDefinitions = $keyByField( $config );
	}

	/**
	 * @inheritDoc
	 */
	public function get_title( $field ) {
		return Obj::path( [ $field, 'type' ], $this->fieldDefinitions );
	}

	/**
	 * @inheritDoc
	 */
	public function get_fields() {
		return array_keys( $this->fieldDefinitions );
	}

	/**
	 * @inheritDoc
	 */
	public function get_editor_type( $field ) {
		return Obj::path( [ $field, 'editor_type' ], $this->fieldDefinitions );
	}

	/**
	 * @inheritDoc
	 */
	public function &get_items( $settings ) {
		return $settings->{$this->itemsField};
	}
}
