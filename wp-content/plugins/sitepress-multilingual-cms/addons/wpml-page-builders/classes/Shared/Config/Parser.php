<?php

namespace WPML\PB\Config;

use WPML\FP\Fns;
use WPML\FP\Lst;
use WPML\FP\Maybe;
use WPML\FP\Obj;

class Parser {

	/** @var string $configRoot */
	private $configRoot;

	/** @var string $defaultConditionKey */
	private $defaultConditionKey;

	public function __construct( $configRoot, $defaultConditionKey ) {
		$this->configRoot          = $configRoot;
		$this->defaultConditionKey = $defaultConditionKey;
	}

	/**
	 * Receives a raw config array (from XML) and convert it into
	 * a page builder configuration array.
	 *
	 * @see WPML_Elementor_Translatable_Nodes::get_nodes_to_translate()
	 *
	 * @param array $allConfig
	 *
	 * @return array
	 */
	public function extract( array $allConfig ) {
		$pbConfig   = [];
		$allWidgets = Obj::pathOr( [], [ 'wpml-config', $this->configRoot, 'widget' ], $allConfig );

		foreach ( $allWidgets as $widget ) {
			$widgetName = Obj::path( ['attr', 'name'], $widget );

			$pbConfig[ $widgetName ] = [
				'conditions' => $this->parseConditions( $widget, $widgetName ),
				'fields'     => $this->parseFields(  Obj::pathOr( [], ['fields', 'field'], $widget ) ),
			];

			$fieldsInItems = Obj::prop( 'fields-in-item', $widget );

			if ( $fieldsInItems ) {
				$pbConfig[ $widgetName ]['fields_in_item'] = [];
				$fieldsInItems                             = $this->normalize( $fieldsInItems );

				foreach ( $fieldsInItems as $fieldsInItem ) {
					$itemOf                                               = Obj::path( [ 'attr', 'items_of' ], $fieldsInItem );
					$pbConfig[ $widgetName ]['fields_in_item'][ $itemOf ] = $this->parseFields( Obj::propOr( [], 'field', $fieldsInItem ) );
				}
			}

			$integrationClasses = $this->parseIntegrationClasses( $widget );

			if ( $integrationClasses ) {
				$pbConfig[ $widgetName ]['integration-class'] = $integrationClasses;
			}
		}

		return $pbConfig;
	}

	/**
	 * @param array  $widget
	 * @param string $widgetName
	 *
	 * @return array
	 */
	private function parseConditions( array $widget, $widgetName ) {
		$makePair = function( $condition ) {
			return [ Obj::pathOr( $this->defaultConditionKey, ['attr', 'key'], $condition ), $condition['value'] ];
		};

		return Maybe::fromNullable( Obj::path( ['conditions', 'condition'], $widget ) )
			->map( [ $this, 'normalize' ] )
			->map( Fns::map( $makePair ) )
			->map( Lst::fromPairs() )
			->getOrElse( [ $this->defaultConditionKey => $widgetName ] );
	}

	/**
	 * @param array $rawFields
	 *
	 * @return array
	 */
	private function parseFields( array $rawFields ) {
		$parsedFields = [];

		foreach ( $this->normalize( $rawFields ) as $field ) {
			$key     = Obj::path( [ 'attr', 'key_of' ], $field );
			$fieldId = Obj::path( [ 'attr', 'field_id' ], $field );

			$parsedField = [
				'field'       => $field['value'],
				'type'        => Obj::pathOr( $field['value'], [ 'attr', 'type' ], $field ),
				'editor_type' => Obj::pathOr( 'LINE', [ 'attr', 'editor_type' ], $field ),
			];

			if ( $fieldId ) {
				$parsedField['field_id'] = $fieldId;
			}

			if ( $key ) {
				$parsedFields[ $key ] = $parsedField;
			} else {
				$parsedFields[] = $parsedField;
			}
		}

		return $parsedFields;
	}

	/**
	 * @param array $widget
	 *
	 * @return array
	 */
	private function parseIntegrationClasses( array $widget ) {
		return Maybe::fromNullable( Obj::path( [ 'integration-classes', 'integration-class' ], $widget ) )
			->map( [ $this, 'normalize' ] )
			->map( Lst::pluck( 'value' ) )
			->getOrElse( [] );
	}

	/**
	 * If a sequence has only one element, we will wrap it
	 * in order to have the same data shape as for multiple elements.
	 *
	 * @param array $partialConfig
	 *
	 * @return array
	 */
	public function normalize( array $partialConfig ) {
		$isAssocArray = count( array_filter( array_keys( $partialConfig ), 'is_string' ) ) > 0;

		return $isAssocArray ? [ $partialConfig ] : $partialConfig;
	}
}
