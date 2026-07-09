<?php

namespace ACFML\Strings\Traversable;

use ACFML\Strings\Transformer\Transformer;

// phpcs:ignore PHPCompatibility.Interfaces.InternalInterfaces.traversableFound
abstract class Entity implements Traversable {

	/** @var array $data */
	protected $data = [];

	/** @var string $idKey */
	protected $idKey = 'ID';

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
	 *
	 * @return array
	 */
	public function traverse( Transformer $transformer ) {
		foreach ( $this->getConfig() as $config ) {
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
	 * @param array $config
	 *
	 * @return array
	 */
	protected function getStringData( $config ) {
		return array_merge( $config, [ 'id' => $this->data[ $this->idKey ] ] );
	}
}
