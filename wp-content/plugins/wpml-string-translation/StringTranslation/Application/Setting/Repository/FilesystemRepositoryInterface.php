<?php

namespace WPML\StringTranslation\Application\Setting\Repository;

interface FilesystemRepositoryInterface {
	public function createQueueDir();
	public function getQueueDir(): string;
	public function getFilepath( string $filename ): string;
	public function getProcessedStringsFilepath( string $domain, string $ext = 'php' ): string;
	public function getPendingStringsFilepath( string $domain, string $ext = 'php' ): string;
	public function getDomainFromFilepath( string $filepath ): string;
	public function getPendingStringDomainNames( string $ext = 'php' ): array;
	public function getProcessedStringDomainNames( string $ext = 'php' ): array;
	public function getProcessedStringFilenames( string $ext = 'php' ): array;
	public function getProcessedStringFilePaths( string $ext = 'php' ): array;
	public function getPendingStringFilenames( string $ext = 'php' ): array;
	public function getPendingStringFilePaths( string $ext = 'php' ): array;
}