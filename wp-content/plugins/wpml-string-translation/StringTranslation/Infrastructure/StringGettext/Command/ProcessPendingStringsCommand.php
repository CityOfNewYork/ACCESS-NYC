<?php

namespace WPML\StringTranslation\Infrastructure\StringGettext\Command;

use WPML\StringTranslation\Application\StringCore\Domain\StringItem;
use WPML\StringTranslation\Application\StringCore\Domain\StringPosition;
use WPML\StringTranslation\Application\StringGettext\Command\ProcessPendingStringsCommandInterface;
use WPML\StringTranslation\Application\StringCore\Command\LoadExistingStringTranslationsCommandInterface;
use WPML\StringTranslation\Application\StringCore\Command\SaveStringsCommandInterface;
use WPML\StringTranslation\Application\StringCore\Command\InsertStringTranslationsCommandInterface;
use WPML\StringTranslation\Application\StringCore\Repository\TranslationsRepositoryInterface;
use WPML\StringTranslation\Application\StringCore\Domain\Factory\StringItemFactory;
use WPML\StringTranslation\Application\StringCore\Command\SaveStringPositionsCommandInterface;
use WPML\StringTranslation\Application\Setting\Repository\SettingsRepositoryInterface;
use WPML\StringTranslation\Application\StringCore\Command\UpdateStringsCommandInterface;

class ProcessPendingStringsCommand implements ProcessPendingStringsCommandInterface {

	const TIME_LIMIT = 60; // seconds

	/** @var SaveStringsCommandInterface */
	private $saveStringsCommand;

	/** @var TranslationsRepositoryInterface */
	private $translationsRepository;

	/** @var SettingsRepositoryInterface */
	private $settingsRepository;

	/** @var SaveStringPositionsCommandInterface */
	private $saveStringPositionsCommand;

	/** @var LoadExistingStringTranslationsCommandInterface */
	private $loadExistingStringTranslationsCommand;

	/** @var InsertStringTranslationsCommandInterface */
	private $insertStringTranslations;

	/** @var UpdateStringsCommandInterface */
	private $updateStringsCommand;

	/** @var StringItemFactory */
	private $stringItemFactory;

	public function __construct(
		SaveStringsCommandInterface                    $saveStringsCommand,
		TranslationsRepositoryInterface                $translationsRepository,
		SettingsRepositoryInterface                    $settingsRepository,
		SaveStringPositionsCommandInterface            $saveStringPositionsCommand,
		LoadExistingStringTranslationsCommandInterface $loadExistingStringTranslationsCommand,
		InsertStringTranslationsCommandInterface       $insertStringTranslations,
		UpdateStringsCommandInterface                  $updateStringsCommand,
		StringItemFactory                              $stringItemFactory
	) {
		$this->saveStringsCommand                    = $saveStringsCommand;
		$this->translationsRepository                = $translationsRepository;
		$this->settingsRepository                    = $settingsRepository;
		$this->saveStringPositionsCommand            = $saveStringPositionsCommand;
		$this->loadExistingStringTranslationsCommand = $loadExistingStringTranslationsCommand;
		$this->insertStringTranslations              = $insertStringTranslations;
		$this->updateStringsCommand                  = $updateStringsCommand;
		$this->stringItemFactory                     = $stringItemFactory;
	}

	public function run( array $allPendingStrings ): bool {
		$createString = function( array $stringData, string $name = null, string $domain, string $text, string $context = null ) {
			return $this->stringItemFactory->create(
				$domain,
				$context,
				$text,
				[
					'name'          => $name,
					'componentId'   => isset( $stringData['cmp'] ) ? $stringData['cmp'][0] : null,
					'componentType' => isset( $stringData['cmp'] ) ? $stringData['cmp'][1] : null,
					'stringType'    => StringItem::STRING_TYPE_AUTOREGISTER,
				]
			);
		};

		$startTime = time();

		foreach ( $allPendingStrings as $domain => $pendingStrings ) {
			$strings              = [];
			$stringsWithPositions = [];
			foreach ( $pendingStrings as $textAndContext => $stringData ) {
				list( $text, $context ) = StringItem::parseTextAndContextKey( $textAndContext );
				$allStringsForKey       = [];
				/*
				 * 'names' property does not exist when we are registering string from gettext hooks.
				 * In that case only domain, text and context properties are available.
				 * 'names' property exists when we call queueCustomStringAsPending from wpml_st_add_to_queue hook.
				 * That hook is called from '/classes/TranslateWpmlString' class and in that case also name property
				 * should be inserted into 'wp_icl_string' table. We should store names as array, because while strings
				 * are pending we can visit multiple pages and the same domain,text and context can be registered with
				 * multiple names. Example: you can create 2 pages with 2 NextGen galleries, by default they will
				 * create strings with the same domain and default text, but string name will be different, so we
				 * should register both to avoid missed untranslated strings.
				 */
				if ( isset( $stringData['names'] ) && is_array( $stringData['names'] ) && count( $stringData['names'] ) > 0 ) {
					foreach ( $stringData['names'] as $name ) {
						$allStringsForKey[] = $createString( $stringData, $name, $domain, $text, $context );
					}
				} else {
					$allStringsForKey[] = $createString( $stringData, null, $domain, $text, $context );
				}

				// Notice that string position has no id setup here yet, so we do not know yet if it already exists in db.
				foreach ( $stringData['urls'] as $url ) {
					foreach ( $allStringsForKey as $string ) {
						$position = new StringPosition(
							$url['kind'],
							$url['url'],
							$string
						);
						$string->addPosition( $position );
					}
				}

				if ( isset( $stringData['saveStringInDb'] ) && $stringData['saveStringInDb'] ) {
					foreach ( $allStringsForKey as $string ) {
						$strings[] = $string;
					}
				}
				foreach ( $allStringsForKey as $string ) {
					$stringsWithPositions[] = $string;
				}

				if ( time() - $startTime > self::TIME_LIMIT ) {
					return false;
				}
			}

			if ( count( $strings ) > 0 ) {
				$this->saveStringsCommand->run( $strings );
				$this->loadExistingStringTranslationsCommand->run( $strings );
			}

			if ( count( $stringsWithPositions ) > 0 ) {
				$this->saveStringPositionsCommand->run( $stringsWithPositions );
			}
		}

		return true;
	}
}