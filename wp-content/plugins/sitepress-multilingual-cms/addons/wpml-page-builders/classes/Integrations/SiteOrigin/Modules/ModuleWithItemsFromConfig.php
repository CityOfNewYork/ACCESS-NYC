<?php

namespace WPML\PB\SiteOrigin\Modules;

use WPML\FP\Fns;
use WPML\FP\Lst;
use WPML\FP\Obj;

class ModuleWithItemsFromConfig extends ModuleWithItems {

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
		return array_keys( $this->fieldDefinitions );
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

	/**
	 * @param array $settings
	 *
	 * @return array
	 */
	public function get_items( $settings ) {
		$path            = $this->get_items_field();
		$pathInFlatField = explode( '>', $path );
		$items           = Obj::path( $pathInFlatField, $settings );

		if ( is_null( $items ) ) {
			list( $parent, $path ) = explode( '>', $path, 2 );
			$settings              = $settings[ $parent ];
			$items                 = [];
			foreach ( $settings as $x => $setting ) {
				foreach ( $setting[ $path ] as $y => $item ) {
					$items = Obj::assocPath( [ $x . '>' . $y ], $item, $items );
				}
			}
		}

		return $items;
	}

}
