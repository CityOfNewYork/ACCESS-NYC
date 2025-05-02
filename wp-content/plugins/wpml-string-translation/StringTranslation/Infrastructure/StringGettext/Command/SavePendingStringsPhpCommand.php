<?php

namespace WPML\StringTranslation\Infrastructure\StringGettext\Command;

use WPML\StringTranslation\Application\Setting\Repository\FilesystemRepositoryInterface;
use WPML\StringTranslation\Application\StringGettext\Command\SavePendingStringsCommandInterface;

class SavePendingStringsPhpCommand implements SavePendingStringsCommandInterface {

	/** @var FilesystemRepositoryInterface */
	private $filesystemRepository;

	/** @var CreateFileCommand */
	private $createPhpFile;

	public function __construct(
		FilesystemRepositoryInterface $filesystemRepository,
		CreatePhpFileCommand          $createPhpFile
	) {
		$this->filesystemRepository = $filesystemRepository;
		$this->createPhpFile        = $createPhpFile;
	}

	public function run( string $domain, array $strings ) {
		$this->createPhpFile->run(
			$strings,
			$this->filesystemRepository->getPendingStringsFilepath( $domain, 'php' )
		);
	}
}