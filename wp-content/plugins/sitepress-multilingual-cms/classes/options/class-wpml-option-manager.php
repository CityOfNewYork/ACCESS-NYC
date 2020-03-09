<?php
/**
 * @author OnTheGo Systems
 */

namespace WPML\WP;

class OptionManager implements \IWPML_Backend_Action {

	private $group_keys_key = 'WPML_Group_Keys';

	public function add_hooks() {
		add_filter( 'wpml_reset_options', array( $this, 'reset_options' ) );
	}

	/**
	 * Get a WordPress option that is stored by group.
	 *
	 * @param string $group
	 * @param string $key
	 * @param mixed $default
	 *
	 * @return mixed
	 */
	public function get( $group, $key, $default = false ) {
		$data =  get_option( $this->get_key( $group ), array() );
		return isset( $data[ $key ] ) ? $data[ $key ] : $default;
	}

	/**
	 * Save a WordPress option that is stored by group
	 * The value is then stored by key in the group.
	 *
	 * eg. set( 'TM-wizard', 'complete', 'true' ) will create or add to the option WPML(TM-wizard)
	 * The dat in the option will then be an array of items stored by key.
	 *
	 * @param string $group
	 * @param string $key
	 * @param mixed $value
	 * @param bool $autoload
	 */
	public function set( $group, $key, $value, $autoload = true ) {
		$group_key = $this->get_key( $group );

		$data =  get_option( $group_key, array() );
		$data[ $key ] = $value;
		update_option( $group_key, $data, $autoload );

		$this->store_group_key( $group_key );
	}

	/**
	 * @param string $group
	 *
	 * @return string
	 */
	private function get_key( $group ) {
		return 'WPML(' . $group . ')';
	}

	/**
	 * @param $group_key
	 */
	private function store_group_key( $group_key ) {
		$group_keys = get_option( $this->group_keys_key, array() );
		$group_keys[] = $group_key;
		update_option( $this->group_keys_key, array_unique( $group_keys ) );
	}

	/**
	 * Returns all the options that need to be deleted on WPML reset.
	 *
	 * @param array $options
	 *
	 * @return array
	 */
	public function reset_options( $options ) {
		$options[] = $this->group_keys_key;
		return array_merge( $options, get_option( $this->group_keys_key, array() ) );
	}
}
