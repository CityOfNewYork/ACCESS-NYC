<?php

namespace WPML\StringTranslation\Infrastructure\StringGettext\Repository;

use WPML\StringTranslation\Application\Setting\Repository\FilesystemRepositoryInterface;
use WPML\StringTranslation\Application\StringGettext\Repository\FrontendQueueRepositoryInterface;
use WPML\StringTranslation\Infrastructure\Core\Command\SaveFileCommand;

class FrontendQueueJsonRepository implements FrontendQueueRepositoryInterface {

	/** @var FilesystemRepositoryInterface */
	private $filesystemRepository;

	/**
	 * @var SaveFileCommand
	 */
	protected $saveFileCommand;

	public function __construct(
		FilesystemRepositoryInterface $filesystemRepository,
		SaveFileCommand $saveFileCommand
	) {
		$this->filesystemRepository = $filesystemRepository;
		$this->saveFileCommand = $saveFileCommand;
	}

	private function getQueueFilepath(): string {
		$this->filesystemRepository->createQueueDir();
		return $this->filesystemRepository->getQueueDir() . 'gettextfrontend.json';
	}

	public function save( array $data ) {
		$filepath = $this->getQueueFilepath();
		$this->saveFileCommand->run( $filepath, json_encode( $data ) );
	}

	public function get(): array {
		$filepath = $this->getQueueFilepath();

		$data = [];
		if ( file_exists( $filepath ) && is_readable( $filepath ) ) {
			$data = json_decode( file_get_contents( $filepath ), true );
			$hasErrors = json_last_error() !== JSON_ERROR_NONE || ! is_array( $data );
			if ( $hasErrors ) {
				$data = [];
			}
		}

		return $data;
	}

	public function remove() {
		$queueFilepath = $this->getQueueFilepath();
		if ( file_exists( $queueFilepath ) ) {
			unlink( $queueFilepath );
		}
	}
}