<?php

namespace Gravity_Forms\Gravity_Tools;

use Gravity_Forms\Gravity_Tools\Config;

/**
 * Collection to hold Config items and provide their structured data when needed.
 *
 * @package Gravity_Forms\Gravity_Tools
 */
class Config_Collection {

	/**
	 * @var Config[] $configs
	 */
	private $configs = array();

	/**
	 * Add a config to the collection.
	 *
	 * @param Config $config
	 */
	public function add_config( Config $config ) {
		$this->configs[] = $config;
	}

	/**
	 * Handle outputting the config data.
	 *
	 * If $localize is true, data is actually localized via `wp_localize_script`, otherwise
	 * data is simply returned as an array.
	 *
	 * @since 1.0
	 *
	 * @param bool $localize Whether to localize the data, or simply return it.
	 *
	 * @return array
	 */
	public function handle( $localize = true ) {
		$scripts          = $this->get_configs_by_script();
		$data_to_localize = array();

		foreach ( $scripts as $script => $items ) {
			$item_data        = $this->localize_data_for_script( $script, $items, $localize );
			$data_to_localize = array_merge( $data_to_localize, $item_data );
		}

		return $data_to_localize;
	}

	/**
	 * Localize the data for the given script.
	 *
	 * @since 1.0
	 *
	 * @param string      $script
	 * @param Config[] $items
	 */
	private function localize_data_for_script( $script, $items, $localize = true ) {
		$data = array();

		foreach ( $items as $name => $configs ) {
			$localized_data = $this->get_merged_data_for_object( $configs );

			/**
			 * Allows users to filter the data localized for a given script/resource.
			 *
			 * @since 1.0
			 *
			 * @param array  $localized_data The current localize data
			 * @param string $script         The script being localized
			 * @param array  $configs        An array of $configs being applied to this script
			 *
			 * @return array
			 */
			$localized_data = apply_filters( 'gform_localized_script_data_' . $name, $localized_data, $script, $configs );

			$data[ $name ] = $localized_data;

			if ( $localize ) {
				wp_localize_script( $script, $name, $localized_data );
			}
		}

		return $data;
	}

	/**
	 * Get the merged data object for the applicable configs. Will process each config by its
	 * $priority property, overriding or merging values as needed.
	 *
	 * @since 1.0
	 *
	 * @param Config[] $configs
	 */
	private function get_merged_data_for_object( $configs ) {
		// Squash warnings for PHP < 7.0 when running tests.
		@usort( $configs, array( $this, 'sort_by_priority' ) );

		$data = array();

		foreach ( $configs as $config ) {

			// Config is set to overwrite data - simply return its value without attempting to merge.
			if ( $config->should_overwrite() ) {
				$data = $config->get_data();
				continue;
			}

			// Config should be merged - loop through each key and attempt to recursively merge the values.
			foreach ( $config->get_data() as $key => $value ) {
				$existing = isset( $data[ $key ] ) ? $data[ $key ] : null;

				if ( is_null( $existing ) || ! is_array( $existing ) || ! is_array( $value ) ) {
					$data[ $key ] = $value;
					continue;
				}

				$data[ $key ] = array_merge_recursive( $existing, $value );
			}
		}

		return $data;
	}

	/**
	 * Get the appropriate configs, organized by the script they belong to.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	private function get_configs_by_script() {
		$data_to_localize = array();

		foreach ( $this->configs as $config ) {
			if ( ( ! defined( 'GFORMS_DOING_MOCK' ) || ! GFORMS_DOING_MOCK ) && ! $config->should_enqueue() ) {
				continue;
			}

			$data_to_localize[ $config->script_to_localize() ][ $config->name() ][] = $config;
		}

		return $data_to_localize;
	}

	/**
	 * usort() callback to sort the configs by their $priority.
	 *
	 * @param Config $a
	 * @param Config $b
	 *
	 * @return int
	 */
	public function sort_by_priority( Config $a, Config $b ) {
		if ( $a->priority() === $b->priority() ) {
			return 0;
		}

		return $a->priority() < $b->priority() ? - 1 : 1;
	}
}