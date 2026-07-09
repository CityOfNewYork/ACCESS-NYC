<?php

namespace ACFML\Strings\Traversable;

use ACFML\Strings\Transformer\Transformer;
use WPML\FP\Obj;

// phpcs:ignore PHPCompatibility.Interfaces.InternalInterfaces.traversableFound
abstract class Entity implements Traversable {

	/** @var array $data */
	protected $data = [];

	/** @var string $idKey */
	protected $idKey = 'key';

	public function __construct( array $data, array $context = [] ) {
		$this->data = $this->prepareData( $data, $context );
	}

	/**
	 * Turn the provided data into a transformable array, if needed.
	 *
	 * @param  array $data
	 * @param  array $context
	 *
	 * @return array
	 */
	protected function prepareData( $data, $context ) {
		return $data;
	}

	/**
	 * @param Transformer $transformer
	 * @param string|null $context
	 *
	 * @return array
	 */
	public function traverse( Transformer $transformer, $context = null ) {
		foreach ( $this->getFilteredConfig( $context ) as $config ) {
			$key = $config['key'];

			if ( isset( $this->data[ $key ] ) ) {
				$stringData         = $this->getStringData( $config );
				$this->data[ $key ] = $this->transform( $transformer, $this->data[ $key ], $stringData );
			}
		}

		return $this->data;
	}

	/**
	 * @param Transformer $transformer
	 * @param string      $value
	 * @param array       $config
	 *
	 * @return string
	 */
	protected function transform( Transformer $transformer, $value, $config ) {
		return $transformer->transform( $value, $config );
	}

	/**
	 * @return array
	 */
	abstract protected function getConfig();

	/**
	 * @param string|null $context
	 *
	 * @return array
	 */
	protected function getFilteredConfig( $context = null ) {
		$config = $this->getConfig();

		if ( ! $context ) {
			return $config;
		}

		return wpml_collect( $config )
			->filter( function( $configItem ) use ( $context ) {
				return Obj::prop( 'context', $configItem ) && in_array( $context, Obj::prop( 'context', $configItem ), true );
			} )
			->values()
			->toArray();
	}

	/**
	 * @param array $config
	 *
	 * @return array
	 */
	protected function getStringData( $config ) {
		return array_merge( $config, [ 'id' => $this->data[ $this->idKey ] ] );
	}
}
