<?php

interface WPML_ST_Translations_File_Components_Find {
	/**
	 * @param string $file
	 *
	 * @return string|null
	 */
	public function find_id( $file );
}
