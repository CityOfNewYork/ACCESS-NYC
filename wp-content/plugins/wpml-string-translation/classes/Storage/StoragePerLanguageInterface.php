<?php

namespace WPML\ST\Storage;

interface StoragePerLanguageInterface {
	/* Defined value to allow for null/false values to be stored. */
	const NOTHING = '___NOTHING___';

	// Allow to store a value for all languages.
	const GLOBAL_GROUP = '__ALL_LANG__';


	/**
	 * @param string $lang
	 *
	 * @return mixed Returns self::NOTHING if there is nothing stored.
	 */
	public function get( $lang );


	/**
	 * @param string $lang
	 * @param mixed  $value
	 */
	public function save( $lang, $value );


	/**
	 * @param string $lang
	 */
	public function delete( $lang );


}
