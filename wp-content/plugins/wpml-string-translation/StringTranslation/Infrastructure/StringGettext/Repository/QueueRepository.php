<?php

namespace WPML\StringTranslation\Infrastructure\StringGettext\Repository;

use WPML\StringTranslation\Application\StringGettext\Repository\QueueRepositoryInterface;
use WPML\StringTranslation\Application\StringGettext\Repository\QueueStorageInterface;
use WPML\StringTranslation\Application\Setting\Repository\SettingsRepositoryInterface;
use WPML\StringTranslation\Application\Setting\Repository\UrlRepositoryInterface;
use WPML\StringTranslation\Application\StringCore\Domain\StringItem;
use WPML\StringTranslation\Application\StringCore\Domain\StringPosition;
use WPML\StringTranslation\Application\StringCore\Repository\ComponentRepositoryInterface;
use WPML\StringTranslation\Application\StringGettext\Command\DeletePendingStringsCommandInterface;
use WPML\StringTranslation\Application\StringGettext\Command\InitStorageCommandInterface;
use WPML\StringTranslation\Application\StringGettext\Command\SavePendingStringsCommandInterface;
use WPML\StringTranslation\Application\StringGettext\Command\SaveProcessedStringsCommandInterface;
use WPML\StringTranslation\Infrastructure\Factory;
use WPML\StringTranslation\Application\StringCore\Domain\Factory\StringItemFactory;

class QueueRepository implements QueueRepositoryInterface {

	const MAX_PENDING_STRINGS_COUNT_FOR_DOMAIN = 30000;

	/**
	 * @var array<string, array{string, string, string|null}>
	 */
	private $currentUrlStrings = [];

	/** @var array {
	 *     [domain]: array {
	 *         [text\4context]: array {
	 *             'names': array {
	 *                 'name1',
	 *                 'name2',
	 *                 ...
	 *             }, // optional
	 *             'urls': array {
	 *                 'url1',
	 *                 'url2',
	 *                 ...
	 *              },
	 *         },
	 *     },
	 * }
	 */
	private $processedStrings = [];

	/** @var array {
	 *     [domain]: array {
	 *         [text\4context]: array {
	 *             'names': array {
	 *                  'name1',
	 *                  'name2',
	 *                  ...
	 *              }, // optional
	 *             'cmp': array {
	 *                 0: string, // componentId
	 *                 1: int,    // componentType
	 *             },
	 *             'urls': array {
	 *                 array {
	 *                     'kind': int,
	 *                     'url': string,
	 *                 },
	 *                 array { ... },
	 *             },
	 *         },
	 *     },
	 * }
	 */
	private $pendingStrings = [];

	/** @var bool */
	private $hasNewPendingStrings = false;

	/** @var \wpdb */
	private $wpdb;

	/** @var Factory */
	private $factory;

	/** @var SettingsRepositoryInterface */
	private $settingsRepository;

	/** @var ComponentRepositoryInterface */
	private $componentRepository;

	/** @var UrlRepositoryInterface */
	private $urlRepository;

	/** @var DeletePendingStringsCommandInterface */
	private $deletePendingStrings;

	/** @var InitStorageCommandInterface */
	private $initStorage;

	/** @var SavePendingStringsCommandInterface */
	private $savePendingStrings;

	/** @var SaveProcessedStringsCommandInterface */
	private $saveProcessedStrings;

	/** @var StringItemFactory */
	private $stringItemFactory;

	/**
	 * @param \wpdb $wpdb
	 */
	public function __construct(
		$wpdb,
		Factory                                $factory,
		SettingsRepositoryInterface            $settingsRepository,
		ComponentRepositoryInterface           $componentRepository,
		UrlRepositoryInterface                 $urlRepository,
		DeletePendingStringsCommandInterface   $deletePendingStrings,
		InitStorageCommandInterface            $initStorage,
		SavePendingStringsCommandInterface     $savePendingStrings,
		SaveProcessedStringsCommandInterface   $saveProcessedStrings,
		StringItemFactory                      $stringItemFactory
	) {
		$this->wpdb                         = $wpdb;
		$this->factory                      = $factory;
		$this->settingsRepository           = $settingsRepository;
		$this->componentRepository          = $componentRepository;
		$this->urlRepository                = $urlRepository;
		$this->deletePendingStrings         = $deletePendingStrings;
		$this->initStorage                  = $initStorage;
		$this->savePendingStrings           = $savePendingStrings;
		$this->saveProcessedStrings         = $saveProcessedStrings;
		$this->stringItemFactory            = $stringItemFactory;
	}

	public function unloadStrings() {
		$this->pendingStrings   = [];
		$this->processedStrings = [];
	}

	private function getStorage() {
		return $this->factory->getGettextStringsQueueStorage();
	}

	public function addCurrentUrlString( string $text, string $domain, string $context = null ) {
		$key = $text . $domain . $context;
		$this->currentUrlStrings[ $key ] = [ $text, $domain, $context ];
	}

	/**
	 * @return array<int, array{string, string, string|null}>
	 */
	public function getCurrentUrlStrings(): array {
		return array_values( $this->currentUrlStrings );
	}

	private function loadDomainProcessedStrings( string $domain ) {
		if ( isset( $this->processedStrings[ $domain ] ) ) {
			return;
		}

		$this->processedStrings[ $domain ] = $this->getStorage()->getProcessedStringsByDomain( $domain );
	}

	private function loadDomainPendingStrings( string $domain ) {
		if ( isset( $this->pendingStrings[ $domain ] ) ) {
			return;
		}

		$this->pendingStrings[ $domain ] = $this->getStorage()->getPendingStringsByDomain( $domain );
	}

	public function getPendingStringsByDomain( string $domain ): array {
		return $this->getStorage()->getPendingStringsByDomain( $domain );
	}

	private function isProcessedString( string $domain, string $key ): bool {
		return isset( $this->processedStrings[ $domain ][ $key ] );
	}

	private function isPendingString( string $domain, string $key ): bool {
		return isset( $this->pendingStrings[ $domain ][ $key ] );
	}

	private function getProcessedString( string $domain, string $key ): array {
		return $this->isProcessedString( $domain, $key ) ? $this->processedStrings[ $domain ][ $key ] : [];
	}

	private function getPendingString( string $domain, string $key ): array {
		return $this->isPendingString( $domain, $key ) ? $this->pendingStrings[ $domain ][ $key ] : [];
	}

	private function getProcessedStringEntry( string $domain, string $key, string $entryKey ): array {
		$processedString = $this->getProcessedString( $domain, $key );
		$hasEntry        = (
			isset( $processedString[ $entryKey ] ) &&
			is_array( $processedString[ $entryKey ] )
		);

		return $hasEntry ? $processedString[ $entryKey ]: [];
	}

	private function getPendingStringEntry( string $domain, string $key, string $entryKey ): array {
		$pendingString = $this->getPendingString( $domain, $key );
		$hasEntry      = (
			isset( $pendingString[ $entryKey ] ) &&
			is_array( $pendingString[ $entryKey ] )
		);

		return $hasEntry ? $pendingString[ $entryKey ]: [];
	}

	public function isStringAlreadyRegistered( string $text, string $domain, string $context = null, string $name = null ): bool {
		$key = StringItem::createTextAndContextKey( $text, $context );
		$this->loadDomainProcessedStrings( $domain );
		$this->loadDomainPendingStrings( $domain );

		$isProcessed = $this->isProcessedString( $domain, $key );
		$isPending   = $this->isPendingString( $domain, $key );

		if ( is_string( $name ) ) {
			$isProcessed = in_array( $name, $this->getProcessedStringEntry( $domain, $key, 'names' ) );
			$isPending   = in_array( $name, $this->getPendingStringEntry( $domain, $key, 'names' ) );
		}

		return $isProcessed || $isPending;
	}

	public function canTrackString( string $text, string $domain, string $context = null ): bool {
		$key = StringItem::createTextAndContextKey( $text, $context );
		$this->loadDomainProcessedStrings( $domain );
		$this->loadDomainPendingStrings( $domain );

		$isProcessed = $this->isProcessedString( $domain, $key );
		$isPending   = $this->isPendingString( $domain, $key );

		if ( $isProcessed === false && $isPending === false ) {
			return true;
		}

		$maxCount = 5;
		$totalCount = 0;
		if ( $isProcessed ) {
			$totalCount += count( $this->getProcessedStringEntry( $domain, $key, 'urls' ) );
		}
		if ( $isPending ) {
			$totalCount += count( $this->getPendingStringEntry( $domain, $key, 'urls' ) );
		}

		return $totalCount <= $maxCount;
	}

	public function isStringAlreadyTrackedOnUrl( string $text, string $domain, string $context = null, string $requestUrl ): bool {
		$key = StringItem::createTextAndContextKey( $text, $context );
		$this->loadDomainProcessedStrings( $domain );
		$this->loadDomainPendingStrings( $domain );

		$isProcessed = $this->isProcessedString( $domain, $key );
		$isPending   = $this->isPendingString( $domain, $key );

		if ( $isProcessed === false && $isPending === false ) {
			return false;
		}

		// String was already registered(processed) and tracked on the current request url.
		$isTrackedOnCurrentUrl = in_array( $requestUrl, $this->getProcessedStringEntry( $domain, $key, 'urls' ) );
		// String was already registered and is scheduled to be tracked on the current request url.
		$willBeTrackedOnCurrentUrl = in_array(
			$requestUrl,
			array_map(
				function( $item ) {
					return $item['url'];
				},
				$this->getPendingStringEntry( $domain, $key, 'urls' )
			)
		);

		return (
			( $isProcessed && $isTrackedOnCurrentUrl ) ||
			( $isPending && $willBeTrackedOnCurrentUrl )
		);
	}

	public function queueStringAsPending( string $text, string $domain, string $context = null, string $name = null ): bool {
		$key = StringItem::createTextAndContextKey( $text, $context );
		$this->loadDomainProcessedStrings( $domain );
		$this->loadDomainPendingStrings( $domain );

		if ( count ( $this->pendingStrings[ $domain ] ) >= self::MAX_PENDING_STRINGS_COUNT_FOR_DOMAIN ) {
			return false;
		}

		list( $componentId, $componentType ) = $this->componentRepository->getComponentIdAndType( $text, $domain, $context );

		$this->pendingStrings[ $domain ][ $key ] = [
			'saveStringInDb' => true,
			'cmp' => [
				$componentId,
				$componentType,
			],
			'names' => $this->getPendingStringEntry( $domain, $key, 'names' ),
			'urls'  => $this->getPendingStringEntry( $domain, $key, 'urls' ),
		];
		if ( is_string( $name ) && strlen( $name ) > 0 ) {
			$this->pendingStrings[ $domain ][ $key ]['names'][] = $name;
		}

		return $this->hasNewPendingStrings = true;
	}

	public function trackString( string $text, string $domain, string $context = null, string $requestUrl ) {
		if ( ! $this->settingsRepository->isStringTrackingEnabled() ) {
			return;
		}

		$key = StringItem::createTextAndContextKey( $text, $context );
		$this->loadDomainProcessedStrings( $domain );
		$this->loadDomainPendingStrings( $domain );

		$url = [
			'kind' => ICL_STRING_TRANSLATION_STRING_TRACKING_TYPE_BACKEND,
			'url'  => $requestUrl,
		];
		if ( $this->urlRepository->getRequestIsAjax() ) {
			$url['kind'] = ICL_STRING_TRANSLATION_STRING_TRACKING_TYPE_AJAX;
		} else if ( $this->urlRepository->getRequestIsRest() ) {
			$url['kind'] = ICL_STRING_TRANSLATION_STRING_TRACKING_TYPE_REST;
		}

		if ( ! isset( $this->pendingStrings[ $domain ][ $key ] ) ) {
			$this->pendingStrings[ $domain ][ $key ] = [];
		}

		$willBeTrackedOnAnotherUrl = (
			count( $this->getPendingStringEntry( $domain, $key, 'urls' ) ) > 0
		);
		$urls = [ $url ];
		if ( $willBeTrackedOnAnotherUrl ) {
			$pendingUrls   = $this->getPendingStringEntry( $domain, $key, 'urls' );
			$pendingUrls[] = $url;
			$urls          = $pendingUrls;
		}

		$this->pendingStrings[ $domain ][ $key ] = array_merge(
			$this->pendingStrings[ $domain ][ $key ],
			[
				'urls' => $urls,
			]
		);

		$this->hasNewPendingStrings = true;
	}

	public function savePendingStringsQueue() {
		if ( ! $this->hasNewPendingStrings ) {
			return;
		}

		foreach ( $this->pendingStrings as $domain => $pendingStrings ) {
			$this->initStorage->run( $domain );
			$this->savePendingStrings->run( $domain, $pendingStrings );
		}

		$this->hasNewPendingStrings = false;
	}

	public function loadPendingStrings(): array {
		$pendingStringDomains = $this->getStorage()->getPendingStringDomainNames();

		foreach ( $pendingStringDomains as $domain ) {
			$this->loadDomainPendingStrings( $domain );
		}

		return $this->pendingStrings;
	}

	public function markPendingStringsAsProcessed() {
		$pendingStringDomains = $this->getStorage()->getPendingStringDomainNames();

		foreach ( $pendingStringDomains as $domain ) {
			$this->loadDomainProcessedStrings( $domain );

			foreach ( $this->pendingStrings[ $domain ] as $textAndContext => $string ) {
				$key = $textAndContext;
				foreach ( $string as $prop => $value ) {
					if ( ! isset( $this->processedStrings[ $domain ][ $key ] ) ) {
						$this->processedStrings[ $domain ][ $key ] = [
							'urls'  => [],
							'names' => [],
						];
					}
					if ( ! array_key_exists( 'urls', $this->processedStrings ) ) {
						$this->processedStrings['urls'] = [];
					}
					if ( ! array_key_exists( 'names', $this->processedStrings ) ) {
						$this->processedStrings['names'] = [];
					}

					if ( $prop === 'saveStringInDb' ) {
						continue;
					}

					if ( $prop === 'urls' ) {
						foreach ( $value as $url ) {
							if ( ! in_array( $url['url'], $this->getProcessedStringEntry( $domain, $key, 'urls' ) ) ) {
								$this->processedStrings[ $domain ][ $key ]['urls'][] = $url['url'];
							}
						}
					} else if ( $prop === 'names' ) {
						foreach ( $value as $name ) {
							if ( ! in_array( $name, $this->getProcessedStringEntry( $domain, $key, 'names' ) ) ) {
								$this->processedStrings[ $domain ][ $key ]['names'][] = $name;
							}
						}
					} else {
						$this->processedStrings[ $domain ][ $key ][ $prop ] = $value;
					}
				}
			}

			$this->saveProcessedStrings->run(
				$domain,
				$this->processedStrings[ $domain ]
			);
			unset( $this->processedStrings[ $domain ] );

			unset( $this->pendingStrings[ $domain ] );
			$this->deletePendingStrings->run( $domain );
		}
	}

	/**
	 * @param StringItem[] $strings
	 */
	public function removeProcessedStrings( array $strings ) {
		$domainsToSave = [];
		foreach ( $strings as $string ) {
			$this->loadDomainProcessedStrings( $string->getDomain() );
			$key = StringItem::createTextAndContextKey( $string->getValue(), $string->getContext() );
			if ( ! array_key_exists( $key, $this->processedStrings[ $string->getDomain() ] ) ) {
				continue;
			}

			unset( $this->processedStrings[ $string->getDomain() ][ $key ] );
			$domainsToSave[] = $string->getDomain();
		}

		$domainsToSave = array_unique( $domainsToSave );

		foreach ( $domainsToSave as $domain ) {
			$this->saveProcessedStrings->run(
				$domain,
				$this->processedStrings[ $domain ]
			);
			unset( $this->processedStrings[ $domain ] );
		}
	}
}