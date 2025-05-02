<?php

namespace WPML\StringTranslation\Infrastructure\StringGettext\Command;

use WPML\StringTranslation\Application\Setting\Repository\FilesystemRepositoryInterface;
use WPML\StringTranslation\Application\StringGettext\Command\DeletePendingStringsCommandInterface;

class DeletePendingStringsPhpCommand implements DeletePendingStringsCommandInterface {

	/** @var FilesystemRepositoryInterface */
	private $filesystemRepository;

	public function __construct(
		FilesystemRepositoryInterface $filesystemRepository
	) {
		$this->filesystemRepository  = $filesystemRepository;
	}

	public function run( string $domain ) {
		unlink( $this->filesystemRepository->getPendingStringsFilepath( $domain, 'php' ) );
	}
}