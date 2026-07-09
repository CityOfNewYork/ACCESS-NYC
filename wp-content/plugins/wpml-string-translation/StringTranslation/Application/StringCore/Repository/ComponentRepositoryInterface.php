<?php

namespace WPML\StringTranslation\Application\StringCore\Repository;

interface ComponentRepositoryInterface {

	/**
	 * Get the component ID and type for a given string
	 *
	 * @param string $text The text to get component for.
	 * @param string $domain The domain of the text.
	 * @param string|null $context Optional context for the text.
	 *
	 * @return array{id: string, type: string} Array containing 'id' and 'type' keys
	 */
	public function getComponentIdAndType( string $text, string $domain, ?string $context = null ): array;
}
