<?php

namespace WPML\StringTranslation\Infrastructure\StringGettext\Command;

use WPML\StringTranslation\Application\Setting\Repository\FilesystemRepositoryInterface;
use WPML\StringTranslation\Application\StringGettext\Command\SaveProcessedStringsCommandInterface;

class SaveProcessedStringsPhpCommand implements SaveProcessedStringsCommandInterface {

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
			$this->filesystemRepository->getProcessedStringsFilepath( $domain, 'php' )
		);
	}
}