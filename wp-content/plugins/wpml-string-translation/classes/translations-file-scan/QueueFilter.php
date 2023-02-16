<?php

namespace WPML\ST\TranslationFile;

use WPML_ST_Translations_File_Entry;

class QueueFilter {
	/** @var array */
	private $plugins;

	/** @var array */
	private $themes;

	/** @var array */
	private $other;

	/**
	 * @param array $plugins
	 * @param array $themes
	 * @param array $other
	 */
	public function __construct( array $plugins, array $themes, array $other ) {
		$this->plugins = $plugins;
		$this->themes  = $themes;
		$this->other   = $other;
	}

	/**
	 * @param WPML_ST_Translations_File_Entry $file
	 *
	 * @return bool
	 */
	public function isSelected( WPML_ST_Translations_File_Entry $file ) {
		$getResourceName = EntryQueries::getResourceName();
		$resourceName    = $getResourceName( $file );

		switch ( $file->get_component_type() ) {
			case 'plugin':
				return in_array( $resourceName, $this->plugins );

			case 'theme':
				return in_array( $resourceName, $this->themes );

			case 'other':
				return in_array( $resourceName, $this->other );
		}

		return false;
	}
}
