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

	/**
	 * Create a new StringItem instance.
	 *
	 * @param string      $domain      The domain of the string.
	 * @param string      $value       The string value.
	 * @param string|null $context     The context of the string.
	 * @param array       $extraParams Additional parameters for the string.
	 *
	 * @return StringItem
	 */
	public function create(
		string $domain,
		string $value,
		?string $context = null,
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

