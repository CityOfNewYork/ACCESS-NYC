<?php

namespace WPML\User\LanguagePairs;

interface ILanguagePairs {

	/**
	 * Language pairs are returned in an array of the form
	 * array( $from_lang => array( $to_lang_1, $to_lang_2 )
	 *
	 * For example:
	 * array(
	 * 	'en' => array( 'de', 'fr' ),
	 * 	'fr' => array( 'en' ),
	 * )
	 *
	 * @param int $userId
	 *
	 * @return array{string:string[]}
	 */
	public function get( $userId );
}