<?php

namespace WPML\StringTranslation\Application\StringHtml\Repository;

interface JsonStringsRepositoryInterface {

	/**
	 * @return string[]
	 */
	public function getAllStringsFromOutput( string $output ): array;
}