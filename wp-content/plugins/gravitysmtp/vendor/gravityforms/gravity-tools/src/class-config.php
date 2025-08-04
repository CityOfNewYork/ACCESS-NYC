<?php

namespace Gravity_Forms\Gravity_Tools;

use Gravity_Forms\Gravity_Tools\Config_Data_Parser;

/**
 * Base class for providing advanced functionality when localizing Config Data
 * for usage in Javascript.
 *
 * @package Gravity_Forms\Gravity_Tools
 */
abstract class Config {

	/**
	 * The Data Parser
	 *
	 * @since 1.0
	 *
	 * @var Config_Data_Parser
	 */
	protected $parser;

	/**
	 * The data for this config object.
	 *
	 * @since 1.0
	 *
	 * @var array
	 */
	protected $data;

	/**
	 * The object name for this config.
	 *
	 * @since 1.0
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * The ID of the script to localize the data to.
	 *
	 * @since 1.0
	 *
	 * @var string
	 */
	protected $script_to_localize;

	/**
	 * The priority of this config - can be used to control the order in
	 * which configs are processed in the Collection.
	 *
	 * @since 1.0
	 *
	 * @var int
	 */
	protected $priority = 0;

	/**
	 * Whether the config should enqueue it's data. Can also be handled by overriding the
	 * ::should_enqueue() method.
	 *
	 * @since 1.0
	 *
	 * @var bool
	 */
	protected $should_enqueue = true;

	/**
	 * Whether this config should overwrite previous values in the object.
	 *
	 * If set to "true", the object will be overwritten by the values provided here.
	 * If set to "false", the object will have its values merged with those defined here, recursively.
	 *
	 * @since 1.0
	 *
	 * @var bool
	 */
	protected $overwrite = false;

	/**
	 * Constructor
	 *
	 * @param Config_Data_Parser $parser
	 */
	public function __construct( Config_Data_Parser $parser ) {
		$this->parser = $parser;
	}

	/**
	 * Method to handle defining the data array for this config.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	abstract protected function data();

	/**
	 * Determine if the config should enqueue its data. If should_enqueue() is a method,
	 * call it and return the result. If not, simply return the (boolean) value of the property.
	 *
	 * @since 1.0
	 *
	 * @return bool
	 */
	public function should_enqueue() {
		if ( is_callable( $this->should_enqueue ) ) {
			return call_user_func( $this->should_enqueue );
		}

		return $this->should_enqueue;
	}

	/**
	 * Get the data for the config, passing it through a filter.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public function get_data() {
		if ( ( ! defined( 'GFORMS_DOING_MOCK' ) || ! GFORMS_DOING_MOCK ) && ! $this->should_enqueue() ) {
			return false;
		}

		/**
		 * Allows developers to modify the raw config data being sent to the Config Parser. Useful for
		 * adding in custom default/mock values for a given entry in the data, as well as modifying
		 * things like callbacks for dynamic data before it's parsed and localized.
		 *
		 * @since 1.0
		 *
		 * @param array  $data
		 * @param string $script_to_localize
		 *
		 * @return array
		 */
		$data = apply_filters( 'gform_config_data_' . $this->name(), $this->data(), $this->script_to_localize() );

		return $this->parser->parse( $data );
	}

	/**
	 * Get the name of the config's object.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public function name() {
		return $this->name;
	}

	/**
	 * Get the $priority for the config.
	 *
	 * @since 1.0
	 *
	 * @return int
	 */
	public function priority() {
		return $this->priority;
	}

	/**
	 * Get the script to localize.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public function script_to_localize() {
		return $this->script_to_localize;
	}

	/**
	 * Get whether the config should override previous values.
	 *
	 * @since 1.0
	 *
	 * @return bool
	 */
	public function should_overwrite() {
		return $this->overwrite;
	}

}