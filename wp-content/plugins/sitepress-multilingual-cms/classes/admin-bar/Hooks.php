<?php

namespace WPML\TM\AdminBar;

class Hooks implements \IWPML_Frontend_Action, \IWPML_DIC_Action {

	/** @var \WPML_Post_Translation */
	private $postTranslations;

	public function __construct( \WPML_Post_Translation $postTranslations ) {
		$this->postTranslations = $postTranslations;
	}

	public function add_hooks() {
		if ( is_user_logged_in() ) {
			add_action( 'admin_bar_menu', [ $this, 'addTranslateMenuItem' ], 80 );
			add_action( 'wp_enqueue_scripts', [ $this, 'enqueueScripts' ] );
		}
	}

	public function addTranslateMenuItem( \WP_Admin_Bar $wpAdminMenu ) {
		global $wp_the_query;

		$queriedObject = $wp_the_query->get_queried_object();

		if ( ! empty( $queriedObject ) && ! empty( $queriedObject->post_type ) ) {
			wpml_tm_load_status_display_filter();

			$trid = $this->postTranslations->get_element_trid( $queriedObject->ID );
			if ( $trid ) {
				$originalID = $this->postTranslations->get_original_post_ID( $trid );
				$lang       = $this->postTranslations->get_element_lang_code( $queriedObject->ID );

				$translateLink = apply_filters( 'wpml_link_to_translation', '', $originalID, $lang, $trid );

				if ( $translateLink ) {
					$img = '<img class="ab-icon" src="' . ICL_PLUGIN_URL . '/res/img/icon16.png">';
					$wpAdminMenu->add_menu(
						[
							'id'    => 'translate',
							'title' => $img . __( 'Edit Translation', 'wpml-translation-management' ),
							'href'  => admin_url() . $translateLink,
						]
					);
				}
			}
		}
	}

	public function enqueueScripts() {
		wp_enqueue_style( 'wpml-tm-admin-bar', WPML_TM_URL . '/res/css/admin-bar-style.css', array(), ICL_SITEPRESS_VERSION );
	}
}
