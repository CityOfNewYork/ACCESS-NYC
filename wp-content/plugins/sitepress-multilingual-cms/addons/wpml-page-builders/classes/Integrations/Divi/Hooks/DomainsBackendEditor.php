<?php

namespace WPML\Compatibility\Divi\Hooks;

use WPML\FP\Obj;
use WPML\LIB\WP\Hooks;
use function WPML\FP\spreadArgs;
use WPML\PB\Helper\LanguageNegotiation;

class DomainsBackendEditor implements \IWPML_Backend_Action {

	public function add_hooks() {
		if ( LanguageNegotiation::isUsingDomains()
			 && self::isPostEditor()
			 && self::getDomainByCurrentPostLanguage() !== $_SERVER['HTTP_HOST']
		) {
			Hooks::onAction( 'admin_notices' )
				->then( spreadArgs( [ $this, 'displayNotice' ] ) );
		}
	}

	public function displayNotice() {
		$url = ( is_ssl() ? 'https://' : 'http://' ) . self::getDomainByCurrentPostLanguage() . $_SERVER['REQUEST_URI'];
		?>
		<div class="notice notice-warning is-dismissible">
			<p>
				<?php
				echo sprintf(
					// translators: placeholders are opening and closing <a> tag.
					esc_html__( "It is not possible to use Divi's backend builder to edit a post in a different language than your domain. Please use Divi's frontend builder to edit this post or %1\$s switch to the correct domain %2\$s to use the backend builder.", 'sitepress' ),
					sprintf( '<a href="%s">', esc_url( $url ) ),
					'</a>'
				);
				?>
			</p>
			<button type="button" class="notice-dismiss">
				<span class="screen-reader-text">Dismiss this notice.</span>
			</button>
		</div>
		<?php
	}

	/**
	 * @return bool
	 */
	private static function isPostEditor() {
		global $pagenow;

		return 'post.php' === $pagenow
			&& self::getPostId();
	}

	/**
	 * @return int
	 */
	private static function getPostId() {
		/* phpcs:ignore WordPress.CSRF.NonceVerification.NoNonceVerification */
		return (int) Obj::prop( 'post', $_GET );
	}

	/**
	 * @return string|null
	 */
	private static function getDomainByCurrentPostLanguage() {
		$postDetails = apply_filters( 'wpml_post_language_details', null, self::getPostId() );
		$language    = Obj::prop( 'language_code', $postDetails );

		return LanguageNegotiation::getDomainByLanguage( $language );
	}
}
