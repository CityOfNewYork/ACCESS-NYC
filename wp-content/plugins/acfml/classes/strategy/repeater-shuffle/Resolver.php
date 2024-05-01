<?php

namespace ACFML\Repeater\Shuffle;

use WPML\FP\Obj;

class Resolver {

	/**
	 * @return OptionsPage|Post|Term|null
	 */
	public static function getStrategy() {
		global $pagenow;

		//phpcs:disable WordPress.Security.NonceVerification.Recommended,WordPress.CSRF.NonceVerification.NoNonceVerification,WordPress.VIP.SuperGlobalInputUsage.AccessDetected
		$is_repeater_update_on_term_edit  = isset( $_REQUEST['action'] ) && 'editedtag' === $_REQUEST['action'] && isset( $_REQUEST['acf'] );
		$is_repeater_display_on_term_edit = isset( $pagenow ) && 'term.php' === $pagenow;
		$is_repeater_update_on_post_edit  = isset( $_REQUEST['action'] ) && 'editpost' === $_REQUEST['action'] && isset( $_REQUEST['acf'] );
		$is_repeater_display_on_post_edit = isset( $pagenow ) && 'post.php' === $pagenow;
		$is_option_page_request           = function() {
			return is_admin() && function_exists( 'acf_get_options_page' ) && acf_get_options_page( Obj::prop( 'page', $_REQUEST ) );
		};

		if ( $is_repeater_update_on_term_edit || $is_repeater_display_on_term_edit ) {
			//phpcs:disable WordPress.Security.NonceVerification.Recommended
			if ( isset( $_REQUEST['taxonomy'] ) ) {
				return new Term( sanitize_text_field( wp_unslash( $_REQUEST['taxonomy'] ) ) );
			}
		} elseif ( $is_repeater_update_on_post_edit || $is_repeater_display_on_post_edit ) {
			return new Post();
		} elseif ( $is_option_page_request() ) {
			return new OptionsPage();
		}

		return null;
	}
}
