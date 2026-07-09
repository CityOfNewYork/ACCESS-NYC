<?php

namespace WPML\StringTranslation\Application\StringGettext\Repository;

use WPML\StringTranslation\Application\StringCore\Domain\StringItem;

interface QueueRepositoryInterface {
	/**
	 * Add a string from the current URL to the queue
	 *
	 * @param string $text The text to add.
	 * @param string $domain The domain of the text.
	 * @param string|null $context Optional context for the text.
	 *
	 * @return void
	 */
	public function addCurrentUrlString( string $text, string $domain, ?string $context = null );
	/**
	 * @return array<array{string, string, string|null}>
	 */
	public function getCurrentUrlStrings(): array;
	public function unloadStrings();
	/**
	 * Check if a string is already registered
	 *
	 * @param string $text The text to check.
	 * @param string $domain The domain of the text.
	 * @param string|null $context Optional context for the text.
	 * @param string|null $name Optional name of the string.
	 *
	 * @return bool True if the string is already registered, false otherwise
	 */
	public function isStringAlreadyRegistered( string $text, string $domain, ?string $context = null, ?string $name = null ): bool;
	/**
	 * Check if a string is already tracked for a specific URL
	 *
	 * @param string $text The text to check.
	 * @param string $domain The domain of the text.
	 * @param string|null $context Optional context for the text.
	 * @param string $requestUrl The URL to check against.
	 *
	 * @return bool True if the string is already tracked for the URL, false otherwise
	 */
	public function isStringAlreadyTrackedOnUrl( string $text, string $domain, string $requestUrl, ?string $context = null ): bool;
	/**
	 * Add a string to the pending queue
	 *
	 * @param string $text The text to queue.
	 * @param string $domain The domain of the text.
	 * @param string|null $context Optional context for the text.
	 * @param string|null $name Optional name of the string.
	 *
	 * @return bool True if the string was queued successfully, false otherwise
	 */
	public function queueStringAsPending( string $text, string $domain, ?string $context = null, ?string $name = null ): bool;
	/**
	 * Check if a string can be tracked
	 *
	 * @param string $text The text to check.
	 * @param string $domain The domain of the text.
	 * @param string|null $context Optional context for the text.
	 *
	 * @return bool True if the string can be tracked, false otherwise
	 */
	public function canTrackString( string $text, string $domain, ?string $context = null ): bool;
	/**
	 * Track a string for a specific URL
	 *
	 * @param string $text The text to track.
	 * @param string $domain The domain of the text.
	 * @param string|null $context Optional context for the text.
	 * @param string $requestUrl The URL to track the string for.
	 *
	 * @return void
	 */
	public function trackString( string $text, string $domain, string $requestUrl, ?string $context = null );
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
