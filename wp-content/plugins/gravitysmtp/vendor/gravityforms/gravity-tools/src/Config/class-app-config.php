<?php

namespace Gravity_Forms\Gravity_Tools\Config;

use Gravity_Forms\Gravity_Tools\Config_Collection;
use Gravity_Forms\Gravity_Tools\Config;

/**
 * Config items for app registration, including helper methods.
 */
class App_Config extends Config {

	/**
	 * The name of the app, in slug form.
	 *
	 * @var string $app_name
	 */
	private $app_name;

	/**
	 * The condition for enqueuing the app data.
	 *
	 * @var bool|callable $display_condition
	 */
	private $display_condition;

	/**
	 * The CSS assets to enqueue.
	 *
	 * @var array $css_asset
	 */
	private $css_asset;

	/**
	 * The relative path to the chunk in JS.
	 *
	 * @var string $chunk
	 */
	private $chunk;

	/**
	 * The root element to use to load the app.
	 *
	 * @var string $root_element
	 */
	private $root_element;

	/**
	 * Set the data for this app config.
	 *
	 * @since 1.0
	 *
	 * @param $data
	 */
	public function set_data( $data ) {
		$this->name               = $data['object_name'];
		$this->script_to_localize = $data['script_name'];
		$this->app_name           = $data['app_name'];

		$this->display_condition = $data['enqueue'];
		$this->chunk             = $data['chunk'];
		$this->root_element      = $data['root_element'];
	}

	/**
	 * Whether we should enqueue this data.
	 *
	 * @since 1.0
	 *
	 * @return bool|mixed
	 */
	public function should_enqueue() {
		return is_admin();
	}

	/**
	 * Config data.
	 *
	 * @since 1.0
	 *
	 * @return array[]
	 */
	public function data() {
		return array(
			'apps' => array(
				$this->app_name => array(
					'should_display' => is_callable( $this->display_condition ) ? call_user_func( $this->display_condition ) : $this->display_condition,
					'chunk_path'     => $this->chunk,
					'root_element'   => $this->root_element,
				),
			),
		);
	}
}
