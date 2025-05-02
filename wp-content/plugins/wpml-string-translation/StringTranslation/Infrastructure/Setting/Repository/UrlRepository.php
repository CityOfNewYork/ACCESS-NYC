<?php

namespace WPML\StringTranslation\Infrastructure\Setting\Repository;

use WPML\FP\Str;
use WPML\StringTranslation\Application\Setting\Repository\UrlRepositoryInterface;

class UrlRepository implements UrlRepositoryInterface {

	/** @var \SitePress */
	private $sitepress;

	/** @var null|boolean */
	private $isToAdminPanelRequest;

	/** @var null|boolean */
	private $isFrontendRequest;

	/** @var null|boolean */
	private $isRestRequestFromAdminPanel;

	/** @var null|boolean */
	private $isRestRequestFromFrontend;

	/** @var null|boolean */
	private $isAjaxRequestFromFrontend;

	/** @var null|boolean */
	private $isAjaxRequestFromAdminPanel;

	/** @var null|boolean */
	private $requestIsAjax;

	/** @var null|boolean */
	private $requestIsRest;

	/** @var null|boolean */
	private $requestRefererUrl;

	/**
	 * @param \SitePress $sitepress
	 */
	public function __construct(
		$sitepress
	) {
		$this->sitepress = $sitepress;
	}

	public function isCurrentPageWpmlDashboard(): bool {
		$url                  = urldecode( $this->getRequestUrl() );
		$wpmlDashboardPageUrl = 'page=tm/menu/main.php';

		return Str::includes( $wpmlDashboardPageUrl, $url );
	}

	public function isCurrentPageStDashboard(): bool {
		$url   = urldecode( $this->getRequestUrl() );
		$stUrl = 'wpml-string-translation/menu/string-translation.php';

		return Str::includes( $stUrl, $url );
	}

	public function isCurrentPageThemeAndPluginsLocalization(): bool {
		$url = urldecode( $this->getRequestUrl() );
		$locUrl = 'sitepress-multilingual-cms/menu/theme-localization.php';

		return Str::includes( $locUrl, $url );
	}

	public function isCurrentPageTroubleshooting(): bool {
		$url   = urldecode( $this->getRequestUrl() );
		$stUrl = 'sitepress-multilingual-cms/menu/troubleshooting.php';

		return Str::includes( $stUrl, $url );
	}

	public function makeUrlHash( string $requestUrl, string $prefix = '' ): string {
		$prefix .= $this->sitepress->get_current_language();
		return md5( $prefix . explode( '?', $requestUrl )[0] );
	}

	/**
	 * Catches if current url is admin url like /wp-admin/admin.php or /wp-admin/admin-ajax.php.
	 */
	public function isToAdminPanelRequest(): bool {
		if ( ! is_null( $this->isToAdminPanelRequest ) ) {
			return $this->isToAdminPanelRequest;
		}

		return $this->isToAdminPanelRequest = is_admin();
	}

	public function isFrontendRequest(): bool {
		if ( ! is_null( $this->isFrontendRequest ) ) {
			return $this->isFrontendRequest;
		}

		$isToAdminPanelRequest = ( $this->isToAdminPanelRequest() || $this->isRestRequestFromAdminPanel() ) && ! $this->isAjaxRequestFromFrontend();
		return $this->isFrontendRequest = ! $isToAdminPanelRequest || $this->isRestRequestFromFrontend();
	}

	private function getAdminUrl(): string {
		$urlData = parse_url( admin_url() );
		$url     = isset( $urlData['path'] ) ? $urlData['path'] : '/wp-admin';

		return untrailingslashit( $url );
	}

	private function isRestRequestFromAdminPanel(): bool {
		if ( ! is_null( $this->isRestRequestFromAdminPanel ) ) {
			return $this->isRestRequestFromAdminPanel;
		}

		$url = $this->getRequestUrl();
		$ref = $this->getRequestRefererUrl();
		if ( $url && $ref ) {
			$isRequestRefererFromAdminPanel = strpos( $ref, $this->getAdminUrl() ) !== false;
			if ( $this->getRequestIsRest() && $isRequestRefererFromAdminPanel ) {
				return $this->isRestRequestFromAdminPanel = true;
			}
		}

		return $this->isRestRequestFromAdminPanel = false;
	}

	private function isRestRequestFromFrontend(): bool {
		if ( ! is_null( $this->isRestRequestFromFrontend ) ) {
			return $this->isRestRequestFromFrontend;
		}

		return $this->isRestRequestFromFrontend = $this->getRequestIsRest() && ! $this->isRestRequestFromAdminPanel();
	}

	private function isAjaxRequestFromAdminPanel(): bool {
		if ( ! is_null( $this->isAjaxRequestFromAdminPanel ) ) {
			return $this->isAjaxRequestFromAdminPanel;
		}

		$url = $this->getRequestUrl();
		$ref = $this->getRequestRefererUrl();
		if ( $url && $ref ) {
			$isRequestRefererFromAdminPanel = strpos( $ref, $this->getAdminUrl() ) !== false;
			if ( $this->getRequestIsAjax() && $isRequestRefererFromAdminPanel ) {
				return $this->isAjaxRequestFromAdminPanel = true;
			}
		}

		return $this->isAjaxRequestFromAdminPanel = false;
	}

	private function isAjaxRequestFromFrontend(): bool {
		if ( ! is_null( $this->isAjaxRequestFromFrontend ) ) {
			return $this->isAjaxRequestFromFrontend;
		}

		return $this->isAjaxRequestFromFrontend = $this->getRequestIsAjax() && ! $this->isAjaxRequestFromAdminPanel();
	}

	public function getRequestUrl(): string {
		return isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
	}

	public function getClientFrontendRequestUrl(): string {
		$url = $this->getRequestUrl();
		// In case of Ajax/Rest requests the real url we want to track is on which the string was rendered.
		$refUrl = $this->getRequestRefererUrl();

		if ( $this->getRequestIsAjax() ) {
			$url = strlen( $refUrl ) > 0 ? $refUrl : $url;
		} else if ( $this->getRequestIsRest() ) {
			$url = strlen( $refUrl ) > 0 ? $refUrl : $url;
		}

		return $url;
	}

	public function getRequestRefererUrl(): string {
		if ( ! is_null( $this->requestRefererUrl ) ) {
			return $this->requestRefererUrl;
		}

		$ref = wp_get_referer();
		$url = parse_url($ref);

		if ( $url === false || !isset( $url['path'] ) ) {
			return $this->requestRefererUrl = '';
		}

		return $this->requestRefererUrl = $url['path'] . (isset($url['query']) ? '?' . $url['query'] : '');
	}

	public function getRequestIsAjax(): bool {
		if ( ! is_null( $this->requestIsAjax ) ) {
			return $this->requestIsAjax;
		}

		$this->requestIsAjax = wpml_is_ajax();

		return $this->requestIsAjax;
	}

	public function getRequestIsRest(): bool {
		if ( ! is_null( $this->requestIsRest ) ) {
			return $this->requestIsRest;
		}

		$requestUrl = $this->getRequestUrl();
		if ( strlen( $requestUrl ) === 0 ) {
			return $this->requestIsRest = false;
		}

		$restApiPrefix = trailingslashit( rest_get_url_prefix() ?? '' );

		$withRestApiPrefix = strpos( $requestUrl, $restApiPrefix ) !== false;
		$withPlainPermalinks = isset( $_GET['rest_route'] ) && strpos( $_GET['rest_route'], '/', 0 ) === 0;
		$afterWpRestRequestInit = defined( 'REST_REQUEST' ) && REST_REQUEST;

		$this->requestIsRest = (
			$withRestApiPrefix || $withPlainPermalinks || $afterWpRestRequestInit
		);

		return $this->requestIsRest;
	}

	public function deleteCache() {
		$this->isToAdminPanelRequest       = null;
		$this->isFrontendRequest           = null;
		$this->isRestRequestFromAdminPanel = null;
		$this->isRestRequestFromFrontend   = null;
		$this->isAjaxRequestFromAdminPanel = null;
		$this->isAjaxRequestFromFrontend   = null;
		$this->requestIsRest               = null;
		$this->requestIsAjax               = null;
		$this->requestRefererUrl           = null;
	}
}