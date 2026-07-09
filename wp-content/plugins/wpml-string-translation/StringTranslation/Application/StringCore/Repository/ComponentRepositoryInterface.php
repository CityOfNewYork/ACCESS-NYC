<?php

namespace WPML\StringTranslation\Application\StringCore\Repository;

interface ComponentRepositoryInterface {

	/**
	 * @return array {id: string, type: string}
	 */
	public function getComponentIdAndType( string $text, string $domain, string $context = null ): array;
}