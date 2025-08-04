<?php

namespace Gravity_Forms\Gravity_Tools\License;

/**
 * Class API_Response
 *
 * An abstracted Response class used to standardize the responses we send back from an API Connector. Includes
 * standardized serialization and JSON methods to support saving the class to the Database.
 *
 * @since 2.5
 *
 * @package Gravity_Forms\Gravity_Tools\License
 */
abstract class API_Response implements \JsonSerializable, \Serializable {

	/**
	 * The data for this response.
	 *
	 * @var array $data
	 */
	protected $data = array();

	/**
	 * The status for this response.
	 *
	 * @var array $status
	 */
	protected $status = array();

	/**
	 * The errors (if any) for this response.
	 *
	 * @var array $errors
	 */
	protected $errors = array();

	/**
	 * The meta data (if any) for this response.
	 *
	 * @var array $meta
	 */
	protected $meta = array();

	/**
	 * Set the status for the response.
	 *
	 * @since 1.0
	 *
	 * @param $status
	 */
	protected function set_status( $status ) {
		$this->status = $status;
	}

	/**
	 * Add data item.
	 *
	 * @since 1.0
	 *
	 * @param $item
	 */
	protected function add_data_item( $item ) {
		$this->data[] = $item;
	}

	/**
	 * Add an error message.
	 *
	 * @since 1.0
	 *
	 * @param $error_message
	 */
	protected function add_error( $error_message ) {
		$this->errors[] = $error_message;
	}

	/**
	 * Add a meta item to the response.
	 *
	 * @since 1.0
	 *
	 * @param $key
	 * @param $value
	 */
	protected function add_meta_item( $key, $value ) {
		$this->meta[ $key ] = $value;
	}

	/**
	 * Get the data for this response
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public function get_data() {
		return $this->data;
	}

	/**
	 * Get any errors on this response.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public function get_errors() {
		return $this->errors;
	}

	/**
	 * Get the response status.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public function get_status() {
		return $this->status;
	}

	/**
	 * Get the response meta.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public function get_meta() {
		return $this->meta;
	}

	/**
	 * Determine if the response has any errors.
	 *
	 * @since 1.0
	 *
	 * @return bool
	 */
	public function has_errors() {
		return ! empty( $this->errors );
	}

	/**
	 * Get a specific piece of the data.
	 *
	 * @since 1.0
	 *
	 * @param $name
	 * @param int $index
	 *
	 * @return mixed|null
	 */
	public function get_data_value( $name, $index = 0 ) {
		if ( ! isset( $this->data[ $index ][ $name ] ) ) {
			return null;
		}

		return $this->data[ $index ][ $name ];
	}

	/**
	 * Standardization of the class when serialized and unserialized. Useful for standardizing how it
	 * is stored in the Database.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public function serialize() {
		return serialize( $this->__serialize() );
	}

	/**
	 * Prepares the object for serializing.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public function __serialize() {
		return array(
			'data'   => $this->data,
			'errors' => $this->errors,
			'status' => $this->status,
			'meta'   => $this->meta,
		);
	}

	/**
	 * Hydrate the Response data when unserializing.
	 *
	 * @since 1.0
	 *
	 * @param string $serialized
	 */
	public function unserialize( $serialized ) {
		$this->__unserialize( unserialize( $serialized ) );
	}

	/**
	 * Hydrates the object when unserializing.
	 *
	 * @since 1.0
	 *
	 * @param array $data The unserialized data.
	 *
	 * @return void
	 */
	public function __unserialize( $data ) {
		$this->data   = $data['data'];
		$this->errors = $data['errors'];
		$this->status = $data['status'];
		$this->meta   = $data['meta'];
	}

	/**
	 * Process data for JSON Encoding.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	#[\ReturnTypeWillChange]
	public function jsonSerialize() {

		$response = array();

		$response['status'] = $this->status;
		$response['meta']   = $this->meta;

		if ( empty( $this->errors ) ) {
			$response['data'] = $this->data;
		}

		if ( ! empty( $this->errors ) ) {
			$response['errors'] = $this->errors;
		}

		return $response;
	}

}