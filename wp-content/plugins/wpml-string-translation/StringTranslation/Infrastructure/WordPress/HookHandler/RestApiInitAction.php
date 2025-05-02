<?php
namespace WPML\StringTranslation\Infrastructure\WordPress\HookHandler;

use WPML\StringTranslation\UserInterface\RestApi\StringPackageListApiController;
use WPML\StringTranslation\UserInterface\RestApi\StringSettingsApiController;
use WPML\StringTranslation\UserInterface\RestApi\StringItemsCountApiController;
use WPML\StringTranslation\UserInterface\RestApi\StringListApiController;
use WPML\StringTranslation\UserInterface\RestApi\StringFiltersApiController;
use WPML\StringTranslation\UserInterface\RestApi\ProcessStringsQueueApiController;

class RestApiInitAction extends AbstractActionHookHandler {
	const ACTION_NAME = 'rest_api_init';
	const ACTION_ARGS = 0;

	/** @var StringSettingsApiController */
	private $stringSettingsApiController;

	/** @var StringItemsCountApiController */
	private $stringsItemsCountApiController;

	/** @var StringListApiController */
	private $stringsListApiController;

	/** @var StringPackageListApiController */
	private $stringPackageListApiController;

	/** @var StringFiltersApiController */
	private $stringFiltersApiController;

	/** @var ProcessStringsQueueApiController */
	private $processStringsQueueApiController;

	/**
	 * @param StringSettingsApiController $stringSettingsApiController
	 * @param StringItemsCountApiController $stringItemsCountApiController
	 * @param StringListApiController     $stringsListApiController
	 * @param StringPackageListApiController $stringPackageListApiController
	 * @param StringFiltersApiController  $stringFiltersApiController
	 * @param ProcessStringsQueueApiController $processStringsQueueApiController
	 */
	public function __construct(
		StringSettingsApiController $stringSettingsApiController,
		StringItemsCountApiController $stringItemsCountApiController,
		StringListApiController     $stringsListApiController,
		StringPackageListApiController $stringPackageListApiController,
		StringFiltersApiController  $stringFiltersApiController,
		ProcessStringsQueueApiController $processStringsQueueApiController
	) {
		$this->stringSettingsApiController = $stringSettingsApiController;
		$this->stringsItemsCountApiController = $stringItemsCountApiController;
		$this->stringsListApiController    = $stringsListApiController;
		$this->stringFiltersApiController  = $stringFiltersApiController;
		$this->stringPackageListApiController = $stringPackageListApiController;
		$this->processStringsQueueApiController = $processStringsQueueApiController;
	}

	protected function onAction( ...$args ) {
		$this->stringSettingsApiController->add_hooks();
		$this->stringsItemsCountApiController->add_hooks();
		$this->stringsListApiController->add_hooks();
		$this->stringFiltersApiController->add_hooks();
		$this->stringPackageListApiController->add_hooks();
		$this->processStringsQueueApiController->add_hooks();
	}
}
