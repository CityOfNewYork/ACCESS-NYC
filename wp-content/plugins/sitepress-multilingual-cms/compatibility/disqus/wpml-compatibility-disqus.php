<?php

class WPML_Compatibility_Disqus implements IWPML_Action {

	const LANGUAGE_NOT_SUPPORTED = '';

	/** @var SitePress */
	private $sitepress;

	/**
	 * @param SitePress $sitepress
	 */
	public function __construct( SitePress $sitepress ) {
		$this->sitepress = $sitepress;
	}


	public function add_hooks() {
		add_action( 'wp_footer', array( $this, 'set_language' ) );
	}

	public function set_language() {
		if ( is_singular() ) {
			$current_language = $this->get_current_lang();

			if ( $current_language !== self::LANGUAGE_NOT_SUPPORTED ) {
				echo '
	            <script type="text/javascript">
	            	/**
	            	* We define our custom disqus configs here. This function is invoked from:
	            	* /disqus-comment-system/public/js/comment_embed.js by variable `disqus_config_custom`
					*/
	                var disqus_config = function () {
	                	this.language = "' . $current_language . '";
	                };
	            </script>';
			}
		};
	}

	/**
	 * @return string
	 */
	private function get_current_lang() {
		$current_language = $this->sitepress->get_current_language();
		$map              = $this->get_lang_map();

		return isset( $map[ $current_language ] ) ? $map[ $current_language ] : $current_language;
	}

	/**
	 * @return array
	 */
	private function get_lang_map() {
		$map = array(
			'bs'      => self::LANGUAGE_NOT_SUPPORTED,
			'de'      => 'de_formal',
			'es'      => 'es_ES',
			'ga'      => self::LANGUAGE_NOT_SUPPORTED,
			'hi'      => self::LANGUAGE_NOT_SUPPORTED,
			'is'      => 'id',
			'ku'      => self::LANGUAGE_NOT_SUPPORTED,
			'mn'      => self::LANGUAGE_NOT_SUPPORTED,
			'mo'      => self::LANGUAGE_NOT_SUPPORTED,
			'mt'      => self::LANGUAGE_NOT_SUPPORTED,
			'ne'      => self::LANGUAGE_NOT_SUPPORTED,
			'pa'      => self::LANGUAGE_NOT_SUPPORTED,
			'pt-br'   => 'pt_BR',
			'pt-pt'   => 'PT_EU',
			'qu'      => self::LANGUAGE_NOT_SUPPORTED,
			'so'      => self::LANGUAGE_NOT_SUPPORTED,
			'sr'      => 'sr_CYRL',
			'sv'      => 'sv_SE',
			'ta'      => self::LANGUAGE_NOT_SUPPORTED,
			'uz'      => self::LANGUAGE_NOT_SUPPORTED,
			'yi'      => self::LANGUAGE_NOT_SUPPORTED,
			'zh-hans' => 'zh',
			'zh-hant' => 'zh_AHNT',
			'zu'      => 'af',
		);

		return apply_filters( 'wpml_disqus_language_map', $map );
	}
}
