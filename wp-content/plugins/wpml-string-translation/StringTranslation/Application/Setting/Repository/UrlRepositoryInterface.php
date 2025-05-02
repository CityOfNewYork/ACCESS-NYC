<?php

namespace WPML\StringTranslation\Application\Setting\Repository;

interface UrlRepositoryInterface {
	public function isCurrentPageWpmlDashboard(): bool;
	public function isCurrentPageStDashboard(): bool;
	public function isCurrentPageThemeAndPluginsLocalization(): bool;
	public function isCurrentPageTroubleshooting(): bool;
	public function makeUrlHash( string $requestUrl, string $prefix = '' ): string;
	public function isToAdminPanelRequest(): bool;
	public function isFrontendRequest(): bool;
	public function getRequestUrl(): string;
	public function getClientFrontendRequestUrl(): string;
	public function getRequestRefererUrl(): string;
	public function getRequestIsAjax(): bool;
	public function getRequestIsRest(): bool;
	public function deleteCache();
}