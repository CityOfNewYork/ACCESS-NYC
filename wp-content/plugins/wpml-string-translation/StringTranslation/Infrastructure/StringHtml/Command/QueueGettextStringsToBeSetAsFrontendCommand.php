<?php

namespace WPML\StringTranslation\Infrastructure\StringHtml\Command;

use WPML\StringTranslation\Application\StringHtml\Command\QueueGettextStringsToBeSetAsFrontendCommandInterface;
use WPML\StringTranslation\Application\StringGettext\Repository\FrontendQueueRepositoryInterface;
use WPML\StringTranslation\Application\Setting\Repository\UrlRepositoryInterface;
use WPML\StringTranslation\Infrastructure\StringGettext\Repository\Dto\GettextStringsByUrl;

class QueueGettextStringsToBeSetAsFrontendCommand implements QueueGettextStringsToBeSetAsFrontendCommandInterface {

	/** @var FrontendQueueRepositoryInterface */
	private $frontendQueueRepository;

	/** @var UrlRepositoryInterface */
	private $urlRepository;

	public function __construct(
		FrontendQueueRepositoryInterface $frontendQueueRepository,
		UrlRepositoryInterface           $urlRepository
	) {
		$this->frontendQueueRepository = $frontendQueueRepository;
		$this->urlRepository           = $urlRepository;
	}

	/**
	 * @param array<array{string, string, string|null}> $gettextStrings
	 */
	public function run( array $gettextStrings ) {
		if ( count( $gettextStrings ) === 0 ) {
			return;
		}

		$strings = [];
		foreach ( $gettextStrings as $gettextString ) {
			$strings[] = [
				'value'   => $gettextString[0],
				'domain'  => $gettextString[1],
				'context' => $gettextString[2],
			];
		}

		$data[] = [
			'requestUrl'     => $this->urlRepository->getClientFrontendRequestUrl(),
			'gettextStrings' => $strings,
		];

		$this->frontendQueueRepository->save( $data );
	}
}