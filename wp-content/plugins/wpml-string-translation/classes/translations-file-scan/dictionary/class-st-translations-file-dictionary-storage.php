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

	/**
	 * Find all unique component ids.
	 *
	 * @param string|null    $componentType Component type (e.g. "mo", "po", etc.).
	 * @param string[]|array $fileExtensions File extensions (e.g. array("po", "mo")).
	 *
	 * @return string[]
	 */
	public function findAllUniqueComponentIds( ?string $componentType = null, array $fileExtensions = [] ): array;
}
