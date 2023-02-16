<?php

namespace OTGS\Installer\AdminNotices\Notices;

class Texts {

	protected static $repo;
	protected static $product;
	protected static $productURL;
	protected static $apiHost;
	protected static $communicationDetailsLink;
	protected static $supportLink;
	protected static $publishLink;
	protected static $learnMoreDevKeysLink;

	public static function notRegistered() {
		// translators: %s Product name
		$headingHTML = self::getHeadingHTML( __( 'You are using an unregistered version of %s and are not receiving compatibility and security updates', 'installer' ) );
		// translators: %s Product name
		$bodyHTML = self::getBodyHTML( __( '%s plugin must be registered in order to receive stability and security updates. Without these updates, the plugin may become incompatible with new versions of WordPress, which include security patches.', 'installer' ) ) .
		            self::inButtonAreaHTML( self::getNotRegisteredButtons() ) .
		            self::getDismissHTML( Account::NOT_REGISTERED );

		return self::insideDiv( 'register', $headingHTML . $bodyHTML );
	}

	public static function expired() {
		// translators: %s Product name
		$headingHTML = self::getHeadingHTML( __( 'You are using an expired %s account.', 'installer' ) );
		// translators: %s Product name
		$bodyHTML = self::getBodyHTML( __( "Your site is using an expired %s account, which means you won't receive updates. This can lead to stability and security issues.", 'installer' ) ) .
		            self::inButtonAreaHTML( self::getExpiredButtons() ) .
		            self::getDismissHTML( Account::EXPIRED );

		return self::insideDiv( 'expire', $headingHTML . $bodyHTML );
	}

	public static function developmentBanner() {
		// translators: %s Product url
		$dismissHTML = self::getDismissHTML( Account::DEVELOPMENT_MODE );
		$headingHTML = '<h2>' . esc_html( sprintf( __( 'This site is registered on %s as a development site.', 'installer' ), static::$productURL ) ) . '</h2>';
		// translators: %1$s is the text "update the site key" inside a link and %2$s is the text "Learn more" inside a link
		$bodyText = esc_html__( 'When this site goes live, remember to %1$s from "development" to "production" to remove this message. %2$s', 'installer' );
		$bodyHTML = '<p>' . sprintf(
				$bodyText,
				self::getPublishLinkHTML( __( 'update the site key', 'installer' ) ),
				self::getLearnMoveDevKeysLinkHTML( __( 'Learn more', 'installer' ) )
			) . '</p>';

		return self::insideDiv( 'notice', $dismissHTML . $headingHTML . $bodyHTML );
	}

	public static function refunded() {
		// translators: %s Product name
		$headingHTML = self::getHeadingHTML( __( 'Remember to remove %s from this website', 'installer' ) );
		// translators: %s Product name
		$body = self::getBodyHTML( __( 'This site is using the %s plugin, which has not been paid for. After receiving a refund, you should remove this plugin from your sites. Using unregistered plugins means you are not receiving stability and security updates and will ultimately lead to problems running the site.', 'installer' ) ) .
		        self::inButtonAreaHTML( self::getRefundedButtons() );

		return self::insideDiv( 'refund', $headingHTML . $body );
	}

	public static function connectionIssues() {
		// translators: %1$s Product name %2$s host name (ex. wpml.org)
		$headingHTML = self::getConnectionIssueHeadingHTML( __( '%1$s plugin cannot connect to %2$s', 'installer' ) );

		// translators: %1$s Product name %2$s host name (ex. wpml.org)
		$body = self::getConnectionIssueBodyHTML( __( '%1$s needs to connect to its server to check for new releases and security updates. Something in the network or security settings is preventing this. Please allow outgoing communication to %2$s to remove this notice.', 'installer' ) ) .
		        self::inLinksAreaHTML(
			        __( 'Need help?', 'installer' ),
			        // translators: %1$s is `communication error details` %2$s is ex. wpml.org technical support
			        __( 'See the %1$s and let us know in %2$s.', 'installer' ),
			        self::getCommunicationDetailsLinkHTML( __( 'communication error details', 'installer' ) ),
			        // translators: %s is host name (ex. wpml.org)
			        self::getSupportLinkHTML( __( '%s technical support', 'installer' ) )
		        );

		return self::insideDiv( 'connection-issues', $headingHTML . $body );
	}

	public static function pluginActivatedRecommendation( $parameters ) {
		$heading_html = self::getHeadingHTML( $parameters['recommendation_notification'] );

		$body_text = sprintf(
			__( 'Please install %s to allow translating %s.', 'installer' ),
			strip_tags( $parameters['glue_plugin_name'] ),
			$parameters['glue_check_name']
		);

		$body_html = self::getBodyHTML( $body_text ) .
		             self::inButtonAreaHTML( self::getRecommendationButtons( $parameters ) );

		return self::insideDiv( 'plugin-recommendation', $heading_html . $body_html );
	}

	/**
	 * @param string $type The type is used as a suffix of the `otgs-installer-notice-` CSS class.
	 * @param string $html An unescaped HTML string but with escaped data (e.g. attributes, URLs, or strings in the HTML produced from any input).
	 *
	 * @return string
	 */
	protected static function insideDiv( $type, $html ) {
		$classes = [
			'notice',
			'otgs-installer-notice',
			'otgs-installer-notice-' . esc_attr( static::$repo ),
			'otgs-installer-notice-' . esc_attr( $type ),
		];

		$notDismissable = [ 'refund', 'connection-issues', 'development' ];
		if ( ! in_array( $type, $notDismissable ) ) {
			$classes[] = 'otgs-is-dismissible';
		}

		return '<div class="' . implode( ' ', $classes ) . '">' .
		       '<div class="otgs-installer-notice-content">' .
		       $html .
		       '</div>' .
		       '</div>';
	}

	private static function getRecommendationButtons( $parameters ) {

		$installButton = __( "Install and activate", 'installer' );
		$dismiss       = __( "Ignore and don't ask me again", 'installer' );

		return self::getRecommendationInstallButtonHTML( $installButton, $parameters ) .
		       self::getRecommendationDismissHTML( $dismiss, $parameters );
	}

	/**
	 * @return string
	 */
	protected static function getNotRegisteredButtons() {
		$registerUrl = \WP_Installer::menu_url();
		$register    = __( 'Register', 'installer' );
		$stagingSite = __( 'This is a development / staging site', 'installer' );

		return self::getPrimaryButtonHTML( $registerUrl, $register ) .
		       self::getStagingButtonHTML( $stagingSite );
	}

	/**
	 * @return string
	 */
	protected static function getExpiredButtons() {
		$checkOrderStatusUrl = \WP_Installer::menu_url() . '&validate_repository=' . static::$repo;
		$accountButton       = __( 'Extend your subscription', 'installer' );
		$checkButton         = __( 'Check my order status', 'installer' );
		$statusText          = __( 'Got renewal already?', 'installer' );
		$productUrl          = \WP_Installer::instance()->get_product_data( static::$repo, 'url' );

		return self::getPrimaryButtonHTML( $productUrl . '/account', $accountButton ) .
		       self::getStatusHTML( $statusText ) .
		       self::getRefreshButtonHTML( $checkOrderStatusUrl, $checkButton );
	}

	/**
	 * @return string
	 */
	private static function getRefundedButtons() {
		$checkOrderStatusUrl = \WP_Installer::menu_url() . '&validate_repository=' . static::$repo;
		$checkButton         = __( 'Check my order status', 'installer' );
		$status              = __( 'Bought again?', 'installer' );

		return self::getStatusHTML( $status ) .
		       self::getPrimaryButtonHTML( $checkOrderStatusUrl, $checkButton );
	}

	/**
	 * @param string $notice_type The method takes care of escaping the string.
	 *
	 * @return string
	 */
	protected static function getDismissHTML( $notice_type ) {
		return '<span class="installer-dismiss-nag notice-dismiss" ' . self::getDismissedAttributes( $notice_type ) . '>'
		       . '<span class="screen-reader-text">' . esc_html__( 'Dismiss', 'installer' ) . '</span></span>';
	}

	/**
	 * @param string $notice_type The method takes care of escaping the string.
	 *
	 * @return string
	 */
	private static function getDismissedAttributes( $notice_type, $noticeId = null ) {
		$dismissedAttributes = 'data-repository="' . esc_attr( static::$repo ) . '" data-notice-type="' . esc_attr( $notice_type ) . '"';
		if ( $noticeId ) {
			$dismissedAttributes .= '" data-notice-plugin-slug="' . esc_attr( $noticeId ) . '"';
		}

		return $dismissedAttributes;
	}

	/**
	 * @param string $url The method takes care of escaping the string.
	 * @param string $text The method takes care of escaping the string.
	 *
	 * @return string
	 */
	protected static function getPrimaryButtonHTML( $url, $text ) {
		return '<a class="otgs-installer-notice-status-item otgs-installer-notice-status-item-btn" href="' . esc_url( $url ) . '">' . esc_html( $text ) . '</a>';
	}

	/**
	 * @param string $url The method takes care of escaping the string.
	 * @param string $text The method takes care of escaping the string.
	 *
	 * @return string
	 */
	protected static function getRecommendationInstallButtonHTML( $text, $parameters ) {
		return
			wp_nonce_field( 'recommendation_success_nonce', 'recommendation_success_nonce' ) .
			'<input type="hidden" id="originalPluginData" value="' . base64_encode( json_encode( [
				'slug'          => $parameters['glue_check_slug'],
				'repository_id' => $parameters['repository_id'],
			] ) ) . '">' .
			'<button class="js-install-recommended otgs-installer-notice-status-item otgs-installer-notice-status-item-btn" value="' . base64_encode( json_encode( $parameters['download_data'] ) ) . '">' . esc_html( $text ) . '</button><span class="spinner"></span>';
	}

	/**
	 * @param string $url The method takes care of escaping the string.
	 * @param string $text The method takes care of escaping the string.
	 *
	 * @return string
	 */
	protected static function getRefreshButtonHTML( $url, $text ) {
		return '<a class="otgs-installer-notice-status-item otgs-installer-notice-status-item-link otgs-installer-notice-status-item-link-refresh" href="' . esc_url( $url ) . '">' . esc_html( $text ) . '</a>';
	}

	/**
	 * @param string $text The method takes care of escaping the string.
	 *
	 * @return string
	 */
	protected static function getStatusHTML( $text ) {
		return '<p class="otgs-installer-notice-status-item">' . esc_html( $text ) . '</p>';
	}

	/**
	 * @param string $text The method takes care of escaping the string.
	 *
	 * @return string
	 */
	protected static function getRecommendationDismissHTML( $text, $parameters ) {
		return '<a class="installer-dismiss-nag otgs-installer-notice-status-item-link" ' . self::getDismissedAttributes( Recommendation::PLUGIN_ACTIVATED, $parameters['glue_check_slug'] ) . ' href="#">'
		       . esc_html( $text ) . '</a>';
	}

	/**
	 * @param string $html An unescaped HTML string but with escaped data (e.g. attributes, URLs, or strings in the HTML produced from any input).
	 *
	 * @return string
	 */
	private static function inButtonAreaHTML( $html ) {
		return '<div class="otgs-installer-notice-status">' . $html . '</div>';

	}

	/**
	 * @param string $text
	 *
	 * @return string
	 */
	private static function inLinksAreaHTML( $title, $text, $communicationDetails, $supportLink ) {
		return '<div class="otgs-installer-notice-status">
					<p class="otgs-installer-notice-status-item">' . esc_html( $title ) . '</p>
					<p class="otgs-installer-notice-status-item">' . sprintf( esc_html( $text ), $communicationDetails, $supportLink ) . '</p>
				</div>';
	}

	/**
	 * @param string $text The method takes care of escaping the string.
	 *                      If the string contains a placeholder, it will be replaced with the value of `static::$product`.
	 *
	 * @return string
	 */
	protected static function getHeadingHTML( $text ) {
		return '<h2>' . esc_html( sprintf( $text, static::$product ) ) . '</h2>';
	}

	/**
	 * @param string $text
	 *
	 * @return string
	 */
	protected static function getConnectionIssueHeadingHTML( $text ) {
		return '<h2>' . esc_html( sprintf( $text, static::$product, static::$apiHost ) ) . '</h2>';
	}

	/**
	 * @param string $text The method takes care of escaping the string.
	 *                      If the string contains a placeholder, it will be replaced with the value of `static::$product`.
	 *
	 * @return string
	 */
	protected static function getBodyHTML( $text ) {
		return '<p>' . esc_html( sprintf( $text, static::$product ) ) . '</p>';
	}

	/**
	 * @param string $text The method takes care of escaping the string.
	 *                      If the string contains a placeholder, it will be replaced with the value of `static::$product`.
	 *
	 * @return string
	 */
	protected static function getConnectionIssueBodyHTML( $text ) {
		return '<p>' . esc_html( sprintf( $text, static::$product, static::$apiHost ) ) . '</p>';
	}

	/**
	 * @param string $text The method takes care of escaping the string.
	 *
	 * @return string
	 */
	private static function getStagingButtonHTML( $text ) {
		return '<a class="otgs-installer-notice-status-item otgs-installer-notice-status-item-link installer-dismiss-nag" ' . self::getDismissedAttributes( Account::NOT_REGISTERED ) . '>' . esc_html( $text ) . '</a>';
	}

	private static function getCommunicationDetailsLinkHTML( $text ) {
		return '<a href="' . esc_url( admin_url( static::$communicationDetailsLink ) ) . '">' . esc_html( $text ) . '</a>';
	}

	private static function getSupportLinkHTML( $text ) {
		return '<a href="' . esc_url( static::$supportLink ) . '">' . esc_html( sprintf( $text, static::$product ) ) . '</a>';
	}

	/**
	 * @param string $text
	 *
	 * @return string
	 */
	private static function getPublishLinkHTML( $text ) {
		$publishLink = static::$publishLink . \WP_Installer::instance()->get_site_key( static::$repo );

		return self::makeLink( $publishLink, $text );
	}

	private static function getLearnMoveDevKeysLinkHTML( $text ) {
		return self::makeLink( static::$learnMoreDevKeysLink, $text );
	}

	private static function makeLink( $url, $text ) {
		return '<a href="' . esc_url( $url ) . '" target="_blank">' . esc_html( $text ) . '</a>';
	}
}
