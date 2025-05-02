<?php

class WPML_Display_As_Translated_Default_Lang_Messages_View {

	const TEMPLATE = 'default-language-change.twig';

	/**
	 * @var WPML_Twig_Template
	 */
	private $template_service;

	public function __construct( WPML_Twig_Template $template_service ) {
		$this->template_service = $template_service;
	}

	/**
	 * @param string $prev_default_lang
	 * @param string $default_lang
	 */
	public function display( $prev_default_lang, $default_lang ) {
		echo $this->template_service->show( $this->get_model( $prev_default_lang, $default_lang ), self::TEMPLATE );
	}

	/**
	 * @param string $prev_default_lang
	 * @param string $default_lang
	 *
	 * @return array
	 */
	private function get_model( $prev_default_lang, $default_lang ) {
		$doc_link = 'https://wpml.org/documentation/translating-your-contents/displaying-untranslated-content-on-pages-in-secondary-languages/manage-archive-listings-default-language-change/';

		return array(
			'before_message'   => __( 'You have post types set to fallback to the default language. Changing the default language may impact how archive listings display untranslated posts.', 'sitepress' ),
			'after_message'    => __( 'Some content may appear gone due to the default language change. See ', 'sitepress' ),
			'before_help_text' => __( 'Learn more', 'sitepress' ),
			'before_help_link' => $doc_link . '?utm_source=plugin&utm_medium=gui&utm_campaign=default-language-change-information',
			'after_help_text'  => __( 'how to ensure your archive listing continues to display all posts', 'sitepress' ),
			'after_help_link'  => $doc_link . '?utm_source=plugin&utm_medium=gui&utm_campaign=after-default-language-change#preparing-for-default-language-change',
			'got_it'           => __( 'Got it', 'sitepress' ),
			'lang_has_changed' => $prev_default_lang !== $default_lang,
		);
	}
}
