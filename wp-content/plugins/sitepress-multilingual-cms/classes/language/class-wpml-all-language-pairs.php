<?php

class WPML_All_Language_Pairs {

	public static function get() {

		$languages = array_keys( \WPML\Element\API\Languages::getActive() );

		$lang_pairs = array();

		foreach ( $languages as $from_lang ) {
			$lang_pairs[ $from_lang ] = array();
			foreach ( $languages as $to_lang ) {
				if ( $from_lang !== $to_lang ) {
					$lang_pairs[ $from_lang ][] = $to_lang;
				}
			}
		}

		return $lang_pairs;
	}
}
