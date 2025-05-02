<?php

namespace WPML\StringTranslation\Application\Setting\Repository;

interface SettingsRepositoryInterface {
	const AUTOREGISTER_STRINGS_TYPE_ONLY_VIEWED_BY_ADMIN = 0;
	const AUTOREGISTER_STRINGS_TYPE_VIEWED_BY_ALL_USERS = 1;
	const AUTOREGISTER_STRINGS_TYPE_DISABLED = 2;

	const WAS_FRONTEND_VISITED_KEY = 'was_frontend_visited_key';

	public function canProcessQueueInCurrentRequest(): bool;
	public function isAutoregisterStringsTypeOnlyViewedByAdmin(): bool;
	public function isAutoregisterStringsTypeViewedByAllUsers(): bool;
	public function isAutoregisterStringsTypeDisabled(): bool;
	/**
	 * @param $value self::AUTOREGISTER_STRINGS_TYPE_ONLY_VIEWED_BY_ADMIN|self::AUTOREGISTER_STRINGS_TYPE_VIEWED_BY_ALL_USERS|self::AUTOREGISTER_STRINGS_TYPE_DISABLED
	 */
	public function setAutoregisterStringsTypeSetting( $value );
	public function getAutoregisterStringsTypeSetting(): int;
	public function setShouldRegisterBackendStringsSetting( bool $shouldRegisterBackendStrings );
	public function getShouldRegisterBackendStringsSetting(): bool;
	public function setNewTranslationsWereLoadedSetting();
	public function unsetNewTranslationsWereLoadedSetting();
	public function wereNewTranslationsLoaded(): bool;
	/**
	 * @param int|string $value
	 */
	public function saveKeyToSettings( string $keyName, $value = 1 );
	public function removeKeyFromSettings( string $keyName );
	public function hasKeyInSettings( string $keyName ): bool;
	public function getKeyValueFromSettings( string $keyName ): string;
	public function getIsCurrentUserAdmin(): bool;
	public function isAdminViewingFrontendPage(): bool;
	public function setIsAutoregistrationEnabled( bool $isAutoregistrationEnabled );
	public function getIsAutoregistrationEnabled(): bool;
	public function getCurrentLanguage(): string;
	public function getActiveLanguageCodes(): array;
	public function getDefaultLanguageCode(): string;
	public function getDefaultLanguageLocaleCode(): string;
	/**
	 * @return string[]
	 */
	public function getActiveSecondaryLanguageCodes(): array;
	/**
	 * Returns full locale names like 'es_ES' or 'it_IT'.
	 * @return string[]
	 */
	public function getActiveSecondaryLanguageLocales(): array;
	/**
	 * @return array {languageCode: string, languageFullName: string, languageFlagUrl: string}
	 */
	public function getLanguageDetails( string $languageCode ): array;
	public function isCronRequest(): bool;
	public function shouldNotAutoregisterStringsFromCurrentUrl(): bool;
	public function deleteCache();
	public function updateIfIsCurrentUserAdminCache();
	/**
	 * @param string[] $domainsToAllowReloadTranslations
	 */
	public function switchToLocale( string $locale, array $domainsToAllowReloadTranslations = [] );
	public function restorePreviousLocale();

	/**
	 * @param string|null $sourceLanguageCode
	 *
	 * @return string[]
	 */
	public function getAllTargetLanguagesBySource( $sourceLanguageCode ): array;
	public function getLanguageForDomain( string $domain ): string;
	public function setLanguageForDomain( string $domain, string $language );
	public function setMaxQueuedFrontendStringsCount( int $maxQueuedFrontendStringsCount );
	public function getMaxQueuedFrontendStringsCount(): int;
	public function isStringTrackingEnabled(): bool;
	public function enableStringTracking();
	public function disableStringTracking();
}