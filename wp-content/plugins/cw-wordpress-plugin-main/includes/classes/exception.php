<?php
/**
 * GatherContent Plugin
 *
 * @package GatherContent Plugin
 */

namespace GatherContent\Importer;

/**
 * GC specific exception class w/ data property.
 *
 * @since 3.0.0
 */
class Exception extends \Exception {

	/**
	 * Additional data for the exception.
	 *
	 * @var mixed
	 */
	protected $data;

	/**
	 * Constructor. Handles assigning the data property.
	 *
	 * @param string $message Exception message.
	 * @param int $code Exception code.
	 * @param mixed $data Additional data.
	 *
	 * @since 3.0.0
	 *
	 */
	public function __construct( $message, $code, $data = null ) {
		parent::__construct( $message, $code );
		if ( null !== $data ) {
			$this->data = $data;
		}
	}

	/**
	 * Fetch the Exception data.
	 *
	 * @return mixed Exception data
	 * @since  3.0.0
	 *
	 */
	public function get_data() {
		return $this->data;
	}
}

