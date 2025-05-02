<?php

namespace WPML\StringTranslation\Application\StringGettext\Repository;

use WPML\StringTranslation\Application\StringCore\Domain\StringItem;

interface QueueRepositoryInterface {
	public function addCurrentUrlString( string $text, string $domain, string $context = null );
	/**
	 * @return array<array{string, string, string|null}>
	 */
	public function getCurrentUrlStrings(): array;
	public function unloadStrings();
	public function isStringAlreadyRegistered( string $text, string $domain, string $context = null, string $name = null ): bool;
	public function isStringAlreadyTrackedOnUrl( string $text, string $domain, string $context = null, string $requestUrl ): bool;
	public function queueStringAsPending( string $text, string $domain, string $context = null, string $name = null ): bool;
	public function canTrackString( string $text, string $domain, string $context ): bool;
	public function trackString( string $text, string $domain, string $context = null, string $requestUrl );
	public function savePendingStringsQueue();
	public function loadPendingStrings(): array;
	public function markPendingStringsAsProcessed();
	public function getPendingStringsByDomain( string $domain ): array;
	/**
	 * @param StringItem[] $strings
	 *
	 *  When string is removed, this function should be called to remove the string also from autoregister queue.
	 *  We should do it because otherwise string will never be autoregistered again.
	 *  (It will be blocked by condition which checks if string already exists in processed strings)
	 */
	public function removeProcessedStrings( array $strings );
}