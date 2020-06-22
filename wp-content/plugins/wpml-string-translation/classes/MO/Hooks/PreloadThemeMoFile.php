<?php

namespace WPML\ST\MO\Hooks;

use WPML\Collect\Support\Collection;

class PreloadThemeMoFile implements \IWPML_Action {
	/** @var \SitePress */
	private $sitepress;

	/** @var \wpdb */
	private $wpdb;

	public function __construct( \SitePress $sitepress, \wpdb $wpdb ) {
		$this->sitepress = $sitepress;
		$this->wpdb      = $wpdb;
	}


	public function add_hooks() {
		$domains = $this->sitepress->get_setting( 'gettext_theme_domain_name' );
		$domains = \wpml_collect( array_map( 'trim', explode( ',', $domains ) ) );

		if ( (bool) $this->sitepress->get_setting( 'theme_localization_load_textdomain' ) && $domains->count() ) {

			\wpml_collect( $domains )->each( function ( $domain ) {
				$this->getListOfFiles( $domain, get_locale() )->map( function ( $file ) use ( $domain ) {
					load_textdomain( $domain, $file );
				} );
			} );
		}
	}

	/**
	 * @param string $domain
	 * @param string $locale
	 *
	 * @return Collection
	 */
	private function getListOfFiles( $domain, $locale ) {
		$sql = "
			SELECT file_path
			FROM {$this->wpdb->prefix}icl_mo_files_domains
			WHERE domain = %s and file_path LIKE %s
		";

		$sql = $this->wpdb->prepare( $sql, $domain, '%' . $locale . '%' );

		return \wpml_collect( $this->wpdb->get_row( $sql ) );
	}
}