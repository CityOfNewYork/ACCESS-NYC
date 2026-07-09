<?php

namespace WPML\StringTranslation\Application\StringCore\Repository;

use WPML\StringTranslation\Application\StringCore\Domain\StringItem;
use WPML\StringTranslation\Application\StringCore\Domain\StringTranslation;

interface TranslationsRepositoryInterface {
	/**
	 * Check if a translation is available for the given text in the specified domain and context.
	 *
	 * @param string $text The text to check for translation.
	 * @param string $domain The domain of the text.
	 * @param string|null $context Optional context for the text.
	 *
	 * @return bool True if a translation is available, false otherwise
	 */
	public function isTranslationAvailable( string $text, string $domain, ?string $context = null ): bool;
	/**
	 * @param StringItem[] $strings
	 *
	 * @return StringTranslation[]
	 */
	public function createEntitiesForExistingTranslations( array $strings );
}
