<?php

use \WPML\FP\Wrapper;
use function \WPML\FP\invoke;

class WPML_PB_Update_Post {

	private $package_data;
	/** @var  IWPML_PB_Strategy $strategy */
	private $strategy;
	/** @var  wpdb $wpdb */
	private $wpdb;
	/** @var  SitePress $sitepress */
	private $sitepress;

	public function __construct( $wpdb, $sitepress, $package_data, IWPML_PB_Strategy $strategy ) {
		$this->wpdb         = $wpdb;
		$this->sitepress    = $sitepress;
		$this->package_data = $package_data;
		$this->strategy     = $strategy;
	}

	public function update() {

		$package           = $this->package_data['package'];
		$original_post_id  = $package->post_id;
		$post              = get_post( $original_post_id );
		$element_type      = 'post_' . $post->post_type;
		$trid              = $this->sitepress->get_element_trid( $original_post_id, $element_type );
		$post_translations = $this->sitepress->get_element_translations( $trid, $element_type, false, true );

		$languages = $this->package_data['languages'];

		$string_translations = $package->get_translated_strings( array() );

		foreach ( $languages as $lang ) {
			if ( isset( $post_translations[ $lang ] ) ) {
				$this->update_post( $post_translations[ $lang ]->element_id, $post, $string_translations, $lang );
			}
		}
	}

	/**
	 * @param string $content
	 * @param string $lang
	 *
	 * @return string
	 */
	public function update_content( $content, $lang ) {
		return Wrapper::of( $this->strategy )
		              ->map( invoke( 'get_content_updater' ) )
		              ->map( invoke( 'update_content' )->with(
			              $content,
			              $this->package_data['package']->get_translated_strings( [] ),
			              $lang
		              ) )
		              ->get();
	}

	private function update_post( $translated_post_id, $original_post, $string_translations, $lang ) {
		$content_updater = $this->strategy->get_content_updater();
		$content_updater->update( $translated_post_id, $original_post, $string_translations, $lang );
	}

}
