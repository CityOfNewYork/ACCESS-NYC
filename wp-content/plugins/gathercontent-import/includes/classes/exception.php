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
	 * @since 3.0.0
	 *
	 * @param strin $message Exception message.
	 * @param int   $code    Exception code.
	 * @param mixed $data    Additional data.
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
	 * @since  3.0.0
	 *
	 * @return mixed Exception data
	 */
	public function get_data() {
		return $this->data;
	}
}

