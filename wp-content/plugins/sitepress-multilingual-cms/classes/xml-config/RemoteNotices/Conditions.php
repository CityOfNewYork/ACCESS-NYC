<?php

namespace WPML\XMLConfig\RemoteNotices;

class Conditions {

	/** @var \WPML_Active_Plugin_Provider $activePlugins */
	private $activePlugins;

	/** @var null|string[] $activePluginNames */
	private $activePluginNames;

	/** @var null|string $themeName */
	private $themeName;

	/** @var null|string $themeParentName */
	private $themeParentName;

	public function __construct( \WPML_Active_Plugin_Provider $activePlugins ) {
		$this->activePlugins = $activePlugins;
	}

	/**
	 * @param array $conditions
	 *
	 * @return bool
	 */
	public function meetConditions( $conditions ) {
		$relation = $conditions['relation'] ?? 'AND';

		$results = array_merge(
			self::getPluginResults( $conditions['plugin'] ?? [] ),
			self::getThemeResults( $conditions['theme'] ?? [] )
		);

		if ( isset( $conditions['conditions'] ) && is_array( $conditions['conditions'] ) ) {
			foreach ( $conditions['conditions'] as $conditions ) {
				$results = array_merge(
					$results,
					[ $this->meetConditions( $conditions ) ]
				);
			}
		}

		if ( empty( $results ) ) {
			return true; // No condition required.
		}

		if ( 'AND' === $relation ) {
			return ! in_array( false, $results, true );
		} else {
			return in_array( true, $results, true );
		}
	}

	/**
	 * @param array $plugins
	 *
	 * @return bool[]
	 */
	private function getPluginResults( $plugins ) {
		return array_map( [ $this, 'isPluginActive' ], $plugins );
	}

	/**
	 * @param string $pluginName
	 *
	 * @return bool
	 */
	private function isPluginActive( $pluginName ) {
		if ( null === $this->activePluginNames ) {
			$this->activePluginNames = $this->activePlugins->get_active_plugin_names();
		}

		return in_array( $pluginName, $this->activePluginNames, true );
	}

	/**
	 * @param array $themes
	 *
	 * @return bool[]
	 */
	private function getThemeResults( $themes ) {
		if ( ! $themes ) {
			return [];
		}

		if ( null === $this->themeName ) {
			$this->themeName       = wp_get_theme()->get( 'Name' );
			$this->themeParentName = wp_get_theme()->get( 'Template' );
		}

		$hasTheme = in_array( $this->themeName, $themes, true )
			|| in_array( $this->themeParentName, $themes, true );

		return [ $hasTheme ];
	}
}
