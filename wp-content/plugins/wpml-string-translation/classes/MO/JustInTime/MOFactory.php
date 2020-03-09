<?php

namespace WPML\ST\MO\JustInTime;

use WPML\ST\MO\LoadedMODictionary;

class MOFactory {

	/** @var LoadedMODictionary $loaded_mo_dictionary */
	private $loaded_mo_dictionary;

	public function __construct( LoadedMODictionary $loaded_mo_dictionary ) {
		$this->loaded_mo_dictionary = $loaded_mo_dictionary;
	}

	/**
	 * We need to rely on the loaded dictionary rather than `$GLOBALS['l10n]`
	 * because a domain could have been loaded in a language that
	 * does not have a MO file and so it won't be added to the `$GLOBALS['l10n]`.
	 *
	 * @param string $locale
	 * @param array  $excluded_domains
	 * @param array  $cachedMoObjects
	 *
	 * @return array
	 */
	public function get( $locale, array $excluded_domains, array $cachedMoObjects ) {
		$mo_objects = [
			'default' => isset( $cachedMoObjects['default'] )
				? $cachedMoObjects['default']
				: new DefaultMO( $this->loaded_mo_dictionary, $locale ),
		];

		$excluded_domains[] = 'default';

		foreach ( $this->loaded_mo_dictionary->getDomains( $excluded_domains ) as $domain ) {
			$mo_objects[ $domain ] = isset( $cachedMoObjects[ $domain ] )
				? $cachedMoObjects[ $domain ]
				: new MO( $this->loaded_mo_dictionary, $locale, $domain );
		}

		return $mo_objects;
	}
}
