<?php

class WPML_ST_Translations_File_Locale {

	const PATTERN_SEARCH_LANG_JSON = '#DOMAIN_PLACEHOLDER(LOCALES_PLACEHOLDER)-[-_a-z0-9]+\.json$#i';

	/** @var \SitePress */
	private $sitepress;

	/** @var \WPML_Locale */
	private $locale;

	/**
	 * @param SitePress   $sitepress
	 * @param WPML_Locale $locale
	 */
	public function __construct( SitePress $sitepress, WPML_Locale $locale ) {
		$this->sitepress = $sitepress;
		$this->locale    = $locale;
	}


	/**
	 * It extracts language code from mo file path, examples
	 * '/wp-content/languages/admin-pl_PL.mo' => 'pl'
	 * '/wp-content/plugins/sitepress/sitepress-hr.mo' => 'hr'
	 * '/wp-content/languages/fr_FR-4gh5e6d3g5s33d6gg51zas2.json' => 'fr_FR'
	 * '/wp-content/plugins/my-plugin/languages/-my-plugin-fr_FR-my-handler.json' => 'fr_FR'
	 *
	 * @param string $filepath
	 * @param string $domain
	 *
	 * @return string
	 */
	public function get( $filepath, $domain ) {
		switch ( $this->get_extension( $filepath ) ) {
			case 'mo':
				return $this->get_from_mo_file( $filepath );
			case 'json':
				return $this->get_from_json_file( $filepath, $domain );
			default:
				return '';
		}
	}

	/**
	 * @param string $filepath
	 *
	 * @return string|null
	 */
	private function get_extension( $filepath ) {
		return wpml_collect( pathinfo( $filepath ) )->get( 'extension', null );
	}

	/**
	 * @param string $filepath
	 *
	 * @return string
	 */
	private function get_from_mo_file( $filepath ) {
		return $this->get_locales()
					->first(
						function ( $locale ) use ( $filepath ) {
							return strpos( $filepath, $locale . '.mo' );
						},
						''
					);
	}

	/**
	 * @param string $filepath
	 * @param string $domain
	 *
	 * @return string
	 */
	private function get_from_json_file( $filepath, $domain ) {
		$original_domain = $this->get_original_domain_for_json( $filepath, $domain );
		$domain_replace  = 'default' === $original_domain ? '' : $original_domain . '-';
		$locales         = $this->get_locales()->implode( '|' );

		$searches['native-file'] = '#' . $domain_replace . '(' . $locales . ')-[-_a-z0-9]+\.json$#i';
		$searches['wpml-file']   = '#' . $domain . '-(' . $locales . ').json#i';

		foreach ( $searches as $search ) {
			if ( preg_match( $search, $filepath, $matches ) && isset( $matches[1] ) ) {
				return $matches[1];
			}
		}

		return '';
	}

	/**
	 * We need the original domain name to refine the regex pattern.
	 * Unfortunately, the domain is concatenated with the script handler
	 * in the import queue table. That's why we need to retrieve the original
	 * domain from the registration domain and the filepath.
	 *
	 * @param string $filepath
	 * @param string $domain
	 *
	 * @return string
	 */
	private function get_original_domain_for_json( $filepath, $domain ) {
		$filename = basename( $filepath );

		/**
		 * Case of WP JED files:
		 * - filename: de_DE-73f9977556584a369800e775b48f3dbe.json
		 * - domain: default-some-script
		 * => original_domain: default
		 */
		if ( 0 === strpos( $domain, 'default' ) && 0 !== strpos( $filename, 'default' ) ) {
			return 'default';
		}

		/**
		 * Case of 3rd part JED files:
		 * - filename: plugin-domain-de_DE-script-handler.json
		 * - domain: plugin-domain-script-handler
		 * => original_domain: plugin-domain
		 */
		$filename_parts        = explode( '-', $filename );
		$domain_parts          = explode( '-', $domain );
		$original_domain_parts = array();

		foreach ( $domain_parts as $i => $part ) {
			if ( $filename_parts[ $i ] !== $part ) {
				break;
			}

			$original_domain_parts[] = $part;
		}

		return implode( '-', $original_domain_parts );
	}

	/**
	 * @return \WPML\Collect\Support\Collection
	 */
	private function get_locales() {
		return \wpml_collect( $this->sitepress->get_active_languages() )
			->keys()
			->map( [ $this->locale, 'get_locale' ] );
	}
}
