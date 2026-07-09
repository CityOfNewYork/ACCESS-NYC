<?php

namespace WPML\StringTranslation\Infrastructure\StringCore\Repository;

use WPML\StringTranslation\Application\StringCore\Repository\TranslationsRepositoryInterface;
use WPML\StringTranslation\Application\Setting\Repository\SettingsRepositoryInterface;
use WPML\StringTranslation\Application\StringCore\Domain\StringItem;
use WPML\StringTranslation\Application\StringCore\Domain\StringTranslation;

class TranslationsRepository implements TranslationsRepositoryInterface {

	/* @var SettingsRepositoryInterface */
	private $settingsRepository;

	public function __construct( SettingsRepositoryInterface $settingsRepository ) {
		$this->settingsRepository = $settingsRepository;
	}

	public function isTranslationAvailable( string $text, string $domain, string $context = null ): bool {
		// Use WP i18n global to determine if the string is translated
		global $l10n;
		$translations = get_translations_for_domain( $domain );

		// WP_Translation_Controller is for WP 6.5.
		if ( class_exists('\WP_Translation_Controller') || method_exists( $translations, 'translate' ) ) {
			$translation = $translations->translate( $text, $context );
			return $translation !== $text;
		} else {
			$entry = new \Translation_Entry(
				array(
					'singular' => $text,
					'context' => $context,
				)
			);

			$translated = $translations->translate_entry($entry);
			return $translated && !empty($translated->translations);
		}
	}

	/*
	 * @param Translations|NOOP_Translations $translations
	 *
	 * @return string|null
	 */
	private function getTranslatedStringText( $translations, string $text, string $context = null ) {
		// WP_Translation_Controller is for WP 6.5.
		if ( class_exists('\WP_Translation_Controller') || method_exists( $translations, 'translate' ) ) {
			$translation = $translations->translate( $text, $context );
			return ( $translation === $text ) ? null : $translation;
		} else {
			$entry = new \Translation_Entry(
				array(
					'singular' => $text,
					'context' => $context,
				)
			);

			$translated = $translations->translate_entry($entry);
			if (!$translated || empty($translated->translations)) {
				return null;
			}

			return $translated->translations[0];
		}
	}

	/**
	 * @param StringItem[] $strings
	 *
	 * @return StringTranslation[]
	 */
	public function createEntitiesForExistingTranslations( array $strings ) {
		if ( count( $strings ) === 0 ) {
			return [];
		}

		$stringTranslations = [];
		$activeLocales      = $this->settingsRepository->getActiveSecondaryLanguageLocales();
		$defaultLanguage    = $this->settingsRepository->getDefaultLanguageCode();
		if ( $defaultLanguage !== 'en' ) {
			array_unshift( $activeLocales, $this->settingsRepository->getDefaultLanguageLocaleCode() );
		}

		$domains = array_values(
			array_unique(
				array_map(
					function( $string ) {
						return $string->getDomain();
					},
					$strings
				)
			)
		);

		foreach ( $activeLocales as $activeLocale ) {
			$this->settingsRepository->switchToLocale(
				$activeLocale,
				$domains
			);
			/**
			 * This is required because before WP 6.2 sitepress functions called in switchToLocale did not set up
			 * proper locale and we can get incorrect translations loaded, like for Japanese $activeLocale we could
			 * load English translations.
			 */
			if ( \WPML\LIB\WP\WordPress::versionCompare( '<', '6.2.000') ) {
				$currentLocale = determine_locale();
				if ( $currentLocale !== $activeLocale ) {
					$this->settingsRepository->restorePreviousLocale();
					continue;
				}
			}
			load_default_textdomain( $activeLocale );
			array_map(
				function( $domain ) use ( $activeLocale ) {
					$filepathes = apply_filters( 'wpml_st_get_filepathes_for_translation_files', $domain, $activeLocale );
					if ( is_iterable( $filepathes ) ) {
						foreach ( $filepathes as $filepath ) {
							load_textdomain( $domain, $filepath, $activeLocale );
						}
					}
				},
				$domains
			);
			$stringTranslations = array_merge(
				$stringTranslations,
				$this->getTranslations( $strings, explode( '_', $activeLocale )[0] )
			);
			$this->settingsRepository->restorePreviousLocale();
		}

		return $stringTranslations;
	}

	private function getTranslations( array $strings, string $language ): array {
		$stringTranslations   = [];
		$translationsByDomain = [];

		foreach ( $strings as $string ) {
			if ( ! in_array( $string->getDomain(), array_keys( $translationsByDomain ) ) ) {
				$translations = get_translations_for_domain( $string->getDomain() );
				$translationsByDomain[ $string->getDomain() ] = $translations;
			}

			$translation = $this->getTranslatedStringText(
				$translationsByDomain[ $string->getDomain() ],
				$string->getValue(),
				$string->getContext()
			);
			if ( $translation ) {
				$stringTranslation = new StringTranslation(
					$string,
					$language,
					$translation
				);

				$stringTranslations[] = $stringTranslation;
				$string->addTranslation( $stringTranslation );
			}
		}

		return $stringTranslations;
	}
}