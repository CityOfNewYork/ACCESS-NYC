<?php

use WPML\StringTranslation\Infrastructure\Core\HookHandler\WPMLPopulatedItemSectionsFilter;
use WPML\StringTranslation\Infrastructure\Core\HookHandler\WPMLDashboardItemSectionsFilter;
use WPML\StringTranslation\Infrastructure\WordPress\HookHandler\GetSettingFilter;
use WPML\StringTranslation\Infrastructure\WordPress\HookHandler\HasKeyInSettingsFilter;
use WPML\StringTranslation\Infrastructure\WordPress\HookHandler\GetTextFilter;
use WPML\StringTranslation\Infrastructure\WordPress\HookHandler\GettextStrings\AddToQueueAction;
use WPML\StringTranslation\Infrastructure\WordPress\HookHandler\GettextStrings\ProcessQueueAction;
use WPML\StringTranslation\Infrastructure\WordPress\HookHandler\GettextStrings\SaveQueueAction;
use WPML\StringTranslation\Infrastructure\WordPress\HookHandler\GettextStrings\UnloadQueueAction;
use WPML\StringTranslation\Infrastructure\WordPress\HookHandler\GetTextWithContextFilter;
use WPML\StringTranslation\Infrastructure\WordPress\HookHandler\HtmlStrings\ExtractHtmlStringsFilter;
use WPML\StringTranslation\Infrastructure\WordPress\HookHandler\HtmlStrings\ProcessFrontendGettextStringsQueueAction;
use WPML\StringTranslation\Infrastructure\WordPress\HookHandler\HtmlStrings\QueueFrontendGettextStringsAction;
use WPML\StringTranslation\Infrastructure\WordPress\HookHandler\HtmlStrings\QueueJsonFrontendGettextStringsAction;
use WPML\StringTranslation\Infrastructure\WordPress\HookHandler\InitAction;
use WPML\StringTranslation\Infrastructure\WordPress\HookHandler\NGetTextFilter;
use WPML\StringTranslation\Infrastructure\WordPress\HookHandler\NGetTextWithContextFilter;
use WPML\StringTranslation\Infrastructure\WordPress\HookHandler\PluginLocaleFilter;
use WPML\StringTranslation\Infrastructure\WordPress\HookHandler\RestApiInitAction;
use WPML\StringTranslation\Infrastructure\WordPress\HookHandler\ShutdownAction;
use WPML\StringTranslation\Infrastructure\WordPress\HookHandler\ThemeLocaleFilter;
use WPML\StringTranslation\Infrastructure\WordPress\HookHandler\UpdateSettingsAction;
use WPML\StringTranslation\Infrastructure\WordPress\HookHandler\WpAction;
use WPML\StringTranslation\Infrastructure\WordPress\HookHandler\WordPress\UpgraderProcessCompleteAction;
use WPML\StringTranslation\Infrastructure\WordPress\HookHandler\Wpml\St\WpmlStBeforeRemoveStringsAction;
use WPML\StringTranslation\Infrastructure\WordPress\HookHandler\Wpml\WpmlResetPluginsBeforeAction;
use WPML\StringTranslation\Infrastructure\WordPress\HookHandler\Wpml\WpmlUpdateActiveLanguagesAction;
use WPML\StringTranslation\Infrastructure\WordPress\HookHandler\TranslateEverything\AddUntranslatedStringsStrategyFilter;

return [
	ExtractHtmlStringsFilter::class,
	QueueFrontendGettextStringsAction::class,
	QueueJsonFrontendGettextStringsAction::class,
	ProcessFrontendGettextStringsQueueAction::class,
	AddToQueueAction::class,
	ProcessQueueAction::class,
	SaveQueueAction::class,
	UnloadQueueAction::class,
	WpmlResetPluginsBeforeAction::class,
	WpmlUpdateActiveLanguagesAction::class,
	WpmlStBeforeRemoveStringsAction::class,
	GetTextFilter::class,
	GetTextWithContextFilter::class,
	NGetTextFilter::class,
	NGetTextWithContextFilter::class,
	WPMLDashboardItemSectionsFilter::class,
	WPMLPopulatedItemSectionsFilter::class,
	InitAction::class,
	RestApiInitAction::class,
	ShutdownAction::class,
	GetSettingFilter::class,
	HasKeyInSettingsFilter::class,
	ThemeLocaleFilter::class,
	UpgraderProcessCompleteAction::class,
	PluginLocaleFilter::class,
	UpdateSettingsAction::class,
	AddUntranslatedStringsStrategyFilter::class,
	WpAction::class,
];
