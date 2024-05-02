<?php

interface WPML_ST_Translations_File_Dictionary_Storage {

	public function save( WPML_ST_Translations_File_Entry $file );

	/**
	 * @param null|string       $path
	 * @param null|string|array $status
	 *
	 * @return WPML_ST_Translations_File_Entry[]
	 */
	public function find( $path = null, $status = null );
}
