<?php

namespace Gravity_Forms\Gravity_SMTP\Email_Management;

class Managed_Email {

	/**
	 * @var string
	 */
	protected $key;

	/**
	 * @var string
	 */
	protected $label;

	/**
	 * @var string
	 */
	protected $description;

	/**
	 * @var string
	 */
	protected $category;

	/**
	 * @var callable
	 */
	protected $disable_callback;

	public function __construct( $key, $label, $description, $category, $disable_callback ) {
		$this->key              = $key;
		$this->label            = $label;
		$this->description      = $description;
		$this->category         = $category;
		$this->disable_callback = $disable_callback;
	}

	/**
	 * @return string
	 */
	public function get_option_key() {
		return sprintf( 'gravitysmtp_email_stopper_' . $this->key );
	}

	/**
	 * @return string
	 */
	public function key() {
		return $this->key;
	}

	/**
	 * @return string
	 */
	public function label() {
		return $this->label;
	}

	/**
	 * @return string
	 */
	public function category() {
		return $this->category;
	}

	/**
	 * @return string
	 */
	public function description() {
		return $this->description;
	}

	/**
	 * @return callable
	 */
	public function trigger_disable_callback() {
		return call_user_func( $this->disable_callback );
	}
}