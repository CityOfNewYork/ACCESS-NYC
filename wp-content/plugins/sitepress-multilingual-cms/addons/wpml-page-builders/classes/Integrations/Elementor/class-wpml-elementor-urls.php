<?php

class WPML_Elementor_URLs implements IWPML_Action {

	/** @var WPML_Translation_Element_Factory */
	private $element_factory;

	/** @var IWPML_URL_Converter_Strategy */
	private $language_converter;

	/** @var IWPML_Current_Language  */
	private $current_language;

	public function __construct(
		WPML_Translation_Element_Factory $element_factory,
		IWPML_URL_Converter_Strategy $language_converter,
		IWPML_Current_Language $current_language
	) {
		$this->element_factory    = $element_factory;
		$this->language_converter = $language_converter;
		$this->current_language = $current_language;
	}

	public function add_hooks() {
		add_filter( 'elementor/document/urls/edit', array( $this, 'adjust_edit_with_elementor_url' ), 10, 2 );
		add_filter( 'wpml_is_pagination_url_in_post', [ $this, 'is_pagination_url' ], 10, 3 );
		add_filter( 'paginate_links', [ $this, 'fix_pagination_link_with_language_param' ], 10, 1 );
	}

	public function adjust_edit_with_elementor_url( $url, $elementor_document ) {
		$post = $elementor_document->get_main_post();

		$post_element  = $this->element_factory->create_post( $post->ID );
		$post_language = $post_element->get_language_code();

		if ( ! $post_language ) {
			$post_language = $this->current_language->get_current_language();
		}

		return $this->language_converter->convert_admin_url_string( $url, $post_language );
	}

	/**
	 * Check if the given URL is the pagination inside the post.
	 *
	 * @param bool $is_pagination_url_in_post
	 * @param string $url URL to check
	 * @param string $post_name Current post name
	 *
	 * @return bool
	 */
	public function is_pagination_url( $is_pagination_url_in_post, $url, $post_name ) {

                $post_name = preg_quote( $post_name, '/' );

		return $is_pagination_url_in_post
		       || (
			       WPML_Elementor_Data_Settings::is_edited_with_elementor( get_the_ID() )
			       && (
				       preg_match_all( "/{$post_name}\/([\d]*)\/$/", $url )
				       || preg_match_all( "/{$post_name}\/([\d]*)\/\?lang=([a-zA-Z_-]*)$/", $url )
			       )
		       );
	}

	public function fix_pagination_link_with_language_param( $link ) {
		$post = get_post( get_the_ID() );
		if (
			$post
			&& WPML_Elementor_Data_Settings::is_edited_with_elementor( $post->ID )
			&& preg_match_all( "/{$post->post_name}\/\?lang=([a-zA-Z_-]*)\/([\d]*)\/$/", $link )
		) {
			$link = $this->language_converter->convert_url_string(
				preg_replace( "/\?lang=([a-zA-Z_-]*)\//", '', $link ),
				$this->current_language->get_current_language()
			);
		}

		return $link;
	}
}
