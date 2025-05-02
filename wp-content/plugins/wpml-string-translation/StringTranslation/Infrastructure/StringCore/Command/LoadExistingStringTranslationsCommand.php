<?php

namespace WPML\StringTranslation\Infrastructure\StringCore\Command;

use WPML\StringTranslation\Application\StringCore\Domain\StringItem;
use WPML\StringTranslation\Application\StringCore\Command\LoadExistingStringTranslationsCommandInterface;
use WPML\StringTranslation\Application\StringCore\Command\InsertStringTranslationsCommandInterface;
use WPML\StringTranslation\Application\StringCore\Repository\TranslationsRepositoryInterface;
use WPML\StringTranslation\Application\Setting\Repository\SettingsRepositoryInterface;
use WPML\StringTranslation\Application\StringCore\Command\UpdateStringsCommandInterface;

class LoadExistingStringTranslationsCommand implements LoadExistingStringTranslationsCommandInterface
{
	/** @var TranslationsRepositoryInterface */
	private $translationsRepository;

	/** @var SettingsRepositoryInterface */
	private $settingsRepository;

	/** @var InsertStringTranslationsCommandInterface */
	private $insertStringTranslations;

	/** @var UpdateStringsCommandInterface */
	private $updateStringsCommand;

	public function __construct(
		TranslationsRepositoryInterface          $translationsRepository,
		SettingsRepositoryInterface              $settingsRepository,
		InsertStringTranslationsCommandInterface $insertStringTranslations,
		UpdateStringsCommandInterface            $updateStringsCommand
	) {
		$this->translationsRepository = $translationsRepository;
		$this->settingsRepository = $settingsRepository;
		$this->insertStringTranslations = $insertStringTranslations;
		$this->updateStringsCommand = $updateStringsCommand;
	}

	/**
	 * @param StringItem[] $strings
	 */
	public function run( array $strings ) {
		$translations = $this->translationsRepository->createEntitiesForExistingTranslations( $strings );
		$this->insertStringTranslations->run( $translations );
		$this->updateStringTranslationStatuses( $strings );
	}

	/**
	 * @param StringItem[] $strings
	 */
	private function updateStringTranslationStatuses( array $strings ) {
		$allLanguageCodes = $this->settingsRepository->getActiveSecondaryLanguageCodes();
		foreach ( $strings as $string ) {
			$string->refreshStatus( $this->settingsRepository->getDefaultLanguageCode(), $allLanguageCodes );
		}

		$stringsByStatus = [];
		foreach ( $strings as $string ) {
			if ( ! array_key_exists( $string->getStatus(), $stringsByStatus ) ) {
				$stringsByStatus[ $string->getStatus() ] = [];
			}

			$stringsByStatus[ $string->getStatus() ][] = $string;
		}

		foreach ( $stringsByStatus as $status => $strings ) {
			$this->updateStringsCommand->run( $strings, ['status'], [ $status ] );
		}
	}
}