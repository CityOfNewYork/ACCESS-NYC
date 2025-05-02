<?php

namespace WPML\StringTranslation\Application\StringCore\Query;

use WPML\StringTranslation\Application\StringCore\Domain\StringItem;

interface FindByIdQueryInterface {

	/**
	 * @param int[] $ids
	 *
	 * @return StringItem[]
	 */
	public function execute( array $ids ): array;
}