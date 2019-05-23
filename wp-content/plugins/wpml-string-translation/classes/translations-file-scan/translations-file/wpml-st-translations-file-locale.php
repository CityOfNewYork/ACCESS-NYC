<?php

class WPML_ST_Translations_File_Locale {

	const PATTERN_SEARCH_LANG_MO   = '#[-]?([a-z]+[_A-Z]*)\.mo$#i';
	const PATTERN_SEARCH_LANG_JSON = '#DOMAIN_PLACEHOLDER([a-z]+[_A-Z]*)-[-_a-z0-9]+\.json$#i';

	/** @var string $filepath */
	private $filepath;

	/** @var string $domain */
	private $domain;

	/**
	 * @param string $filepath
	 * @param string $domain
	 */
	public function __construct( $filepath, $domain ) {
		$this->filepath = $filepath;
		$this->domain   = $domain;
	}

	/**
	 * It extracts language code from mo file path, examples
	 * '/wp-content/languages/admin-pl_PL.mo' => 'pl'
	 * '/wp-content/plugins/sitepress/sitepress-hr.mo' => 'hr'
	 * '/wp-content/languages/fr_FR-4gh5e6d3g5s33d6gg51zas2.json' => 'fr_FR'
	 * '/wp-content/plugins/my-plugin/languages/-my-plugin-fr_FR-my-handler.json' => 'fr_FR'
	 *
	 * @return string
	 * @throws RuntimeException
	 */
	public function get() {
		switch( $this->get_extension() ) {
			case 'mo':
				$search = self::PATTERN_SEARCH_LANG_MO;
				break;

			case 'json':
				$original_domain = $this->get_original_domain_for_json();
				$domain_replace  = 'default' === $original_domain ? '' : $original_domain . '-';
				$search          = str_replace( 'DOMAIN_PLACEHOLDER', $domain_replace, self::PATTERN_SEARCH_LANG_JSON );
				break;

			default:
				throw new RuntimeException( 'Unable to parse the language from the translations file ' . $this->filepath );
		}

		$i = preg_match( $search, $this->filepath, $matches );
		if ( $i && isset( $matches[1] ) ) {
			return $matches[1];
		}

		throw new RuntimeException( 'Language of ' . $this->filepath. ' cannot be recognized' );
	}

	/** @return string|null */
	private function get_extension() {
		$pathinfo  = pathinfo( $this->filepath );
		return isset( $pathinfo['extension'] ) ? $pathinfo['extension'] : null;
	}

	/**
	 * We need the original domain name to refine the regex pattern.
	 * Unfortunately, the domain is concatenated with the script handler
	 * in the import queue table. That's why we need to retrieve the original
	 * domain from the registration domain and the filepath.
	 *
	 * @return string
	 */
	private function get_original_domain_for_json() {
		$filename = basename( $this->filepath );

		/**
		 * Case of WP JED files:
		 * - filename: de_DE-73f9977556584a369800e775b48f3dbe.json
		 * - domain: default-some-script
		 * => original_domain: default
		 */
		if ( 0 === strpos( $this->domain, 'default' ) && 0 !== strpos( $filename, 'default' ) ) {
			return 'default';
		}

		/**
		 * Case of 3rd part JED files:
		 * - filename: plugin-domain-de_DE-script-handler.json
		 * - domain: plugin-domain-script-handler
		 * => original_domain: plugin-domain
		 */
		$filename_parts        = explode( '-', $filename );
		$domain_parts          = explode( '-', $this->domain );
		$original_domain_parts = array();

		foreach ( $domain_parts as $i => $part ) {
			if ( $filename_parts[ $i ] !== $part ) {
				break;
			}

			$original_domain_parts[] = $part;
		}

		return implode( '-', $original_domain_parts );
	}
}
