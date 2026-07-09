<?php

namespace WPML\StringTranslation\Application\StringCore\Domain\Factory;

use WPML\StringTranslation\Application\StringCore\Domain\StringItem;
use WPML\StringTranslation\Application\Setting\Repository\SettingsRepositoryInterface;

class StringItemFactory {

	/** @var SettingsRepositoryInterface */
	private $settingsRepository;

	public function __construct(
		SettingsRepositoryInterface $settingsRepository
	) {
		$this->settingsRepository = $settingsRepository;
	}

	public function create(
		string $domain,
		string $context = null,
		string $value,
		array $extraParams = []
	) {
		$name          = $extraParams['name'] ?? null;
		$componentId   = $extraParams['componentId'] ?? null;
		$componentType = $extraParams['componentType'] ?? StringItem::COMPONENT_TYPE_UNKNOWN;
		$stringType    = $extraParams['stringType'] ?? StringItem::STRING_TYPE_DEFAULT;

		return new StringItem(
			$this->settingsRepository->getLanguageForDomain( $domain ),
			$domain,
			$context,
			$value,
			ICL_TM_NOT_TRANSLATED,
			$name,
			$componentId,
			$componentType,
			$stringType
		);
	}
}