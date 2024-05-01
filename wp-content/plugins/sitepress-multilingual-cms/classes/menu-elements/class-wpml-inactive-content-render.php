<?php

class WPML_Inactive_Content_Render extends WPML_Twig_Template_Loader {

	const TEMPLATE = 'inactive-content.twig';

	/** @var WPML_Inactive_Content $inactive_content */
	private $inactive_content;

	public function __construct( WPML_Inactive_Content $inactive_content, array $paths ) {
		$this->inactive_content = $inactive_content;
		parent::__construct( $paths );
	}

	public function render() {
		$model = array(
			'content' => $this->inactive_content,
			'strings' => [
				'title'                     => __( "You deactivated the following language(s) from your site, but there are still some existing translations saved in your database.", 'sitepress' ),
				'more_info'                 => __( "If you don't plan to activate this language again, you can delete the associated content from your database.", "sitepress" ),
				'language'                  => __( 'Language', 'sitepress' ),
				'delete_translated_content' => __( 'Delete content', 'sitepress' )
			],
		);

		return $this->get_template()->show( $model, self::TEMPLATE );
	}
}
