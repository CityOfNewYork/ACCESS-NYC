<?php

namespace WPML\BrowserLanguageRedirect;

use WPML\LIB\WP\App\Resources;
use WPML\LIB\WP\Nonce;

class Dialog implements \IWPML_Backend_Action {
	const ACCEPTED      = 'accepted';
	const USER_META     = 'wpml-browser-redirect-dialog';
	const ACCEPT_ACTION = 'accept_wpml_browser_language_redirect_message';
	const NONCE_KEY     = 'wpml-browser-language-redirect-message';

	public function add_hooks() {
		add_action( 'admin_notices', [ $this, 'print_dialog_container' ] );
		add_action( 'admin_head', [ $this, 'enqueue_res' ] );
		add_action( 'wp_ajax_' . self::ACCEPT_ACTION, [ $this, 'accept' ] );
	}

	public function enqueue_res() {
		if ( $this->should_print_dialog() ) {
			Resources::enqueue(
				'browser-language-redirect-dialog',
				ICL_PLUGIN_URL,
				WPML_PLUGIN_PATH,
				ICL_SITEPRESS_VERSION,
				null,
				[
					'name' => 'wpmlBrowserLanguageRedirectDialog',
					'data' => [
						'endpoint' => self::ACCEPT_ACTION,
					],
				]
			);
		}
	}

	public function print_dialog_container() {
		if ( $this->should_print_dialog() ) {
			?>
			<div id="browser-language-redirect-dialog"></div>
			<?php
		}
	}

	public function accept() {
		if ( Nonce::verify( self::NONCE_KEY, wpml_collect( $_POST ) ) ) {
			update_user_meta( get_current_user_id(), self::USER_META, self::ACCEPTED );
		}
	}

	private function should_print_dialog() {
		return ! $this->is_accepted() && $this->is_languages_page();
	}

	private function is_accepted() {
		return get_user_meta( get_current_user_id(), self::USER_META, true ) === self::ACCEPTED;
	}

	private function is_languages_page() {
		return isset( $_GET['page'] ) && WPML_PLUGIN_FOLDER . '/menu/languages.php' === $_GET['page'];
	}
}
