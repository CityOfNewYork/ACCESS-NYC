<?php

namespace WPML\StringTranslation\Application\StringGettext\Repository;

use WPML\StringTranslation\Infrastructure\StringGettext\Repository\Dto\GettextStringsByUrl;

interface FrontendQueueRepositoryInterface {
	public function save( array $data );
	/**
	 * @return GettextStringsByUrl[]
	 */
	public function get(): array;
	public function remove();
}