<?php

namespace WPML\StringTranslation\Application\StringGettext\Repository;

interface QueueStorageInterface {
	public function getPendingStringDomainNames(): array;
	public function getProcessedStringDomainNames(): array;
	public function getProcessedStringsByDomain( string $domain ): array;
	public function getPendingStringsByDomain( string $domain ): array;
}