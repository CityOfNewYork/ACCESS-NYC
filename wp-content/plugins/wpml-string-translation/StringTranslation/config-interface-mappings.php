<?php

use WPML\StringTranslation\Application\StringCore\Command\InsertStringsCommandInterface;
use WPML\StringTranslation\Application\StringCore\Command\InsertStringPositionsCommandInterface;
use WPML\StringTranslation\Application\StringCore\Command\InsertStringTranslationsCommandInterface;
use WPML\StringTranslation\Application\StringCore\Command\LoadExistingStringTranslationsCommandInterface;
use WPML\StringTranslation\Application\StringCore\Command\LoadExistingStringTranslationsForAllStringsCommandInterface;
use WPML\StringTranslation\Application\StringCore\Command\UpdateStringsCommandInterface;
use WPML\StringTranslation\Application\StringCore\Command\SaveStringsCommandInterface;
use WPML\StringTranslation\Application\StringCore\Command\SaveStringPositionsCommandInterface;
use WPML\StringTranslation\Application\StringCore\Query\FetchFiltersQueryInterface;
use WPML\StringTranslation\Application\StringCore\Query\FindCountBySearchCriteriaQueryInterface;
use WPML\StringTranslation\Application\StringCore\Query\FindAllStringsCountQueryInterface;
use WPML\StringTranslation\Application\StringCore\Query\FindAllStringsQueryInterface;
use WPML\StringTranslation\Application\StringCore\Query\FindBySearchCriteriaQueryInterface;
use WPML\StringTranslation\Application\StringCore\Query\FindByDomainValueAndContextQueryInterface;
use WPML\StringTranslation\Application\StringCore\Query\FindByIdQueryInterface;
use WPML\StringTranslation\Application\StringCore\Repository\ComponentRepositoryInterface;
use WPML\StringTranslation\Application\StringCore\Repository\TranslationsRepositoryInterface;
use WPML\StringTranslation\Application\StringGettext\Command\ClearAllStoragesCommandInterface;
use WPML\StringTranslation\Application\StringGettext\Command\ProcessPendingStringsCommandInterface;
use WPML\StringTranslation\Application\StringGettext\Command\DeletePendingStringsCommandInterface;
use WPML\StringTranslation\Application\StringGettext\Command\InitStorageCommandInterface;
use WPML\StringTranslation\Application\StringGettext\Command\SavePendingStringsCommandInterface;
use WPML\StringTranslation\Application\StringGettext\Command\SaveProcessedStringsCommandInterface;
use WPML\StringTranslation\Application\StringGettext\Repository\FrontendQueueRepositoryInterface;
use WPML\StringTranslation\Application\StringGettext\Repository\QueueRepositoryInterface;
use WPML\StringTranslation\Application\StringGettext\Repository\LoadedTextdomainRepositoryInterface;
use WPML\StringTranslation\Application\StringGettext\Validator\IsExcludedDomainStringValidatorInterface;
use WPML\StringTranslation\Application\StringHtml\Command\QueueGettextStringsToBeSetAsFrontendCommandInterface;
use WPML\StringTranslation\Application\StringHtml\Repository\GettextStringsRepositoryInterface;
use WPML\StringTranslation\Application\StringHtml\Repository\HtmlStringsFromScriptTagRepositoryInterface;
use WPML\StringTranslation\Application\StringHtml\Repository\HtmlStringsRepositoryInterface;
use WPML\StringTranslation\Application\StringHtml\Repository\JsonStringsRepositoryInterface;
use WPML\StringTranslation\Application\StringHtml\Validator\IsExcludedHtmlStringValidatorInterface;
use WPML\StringTranslation\Application\Setting\Repository\FilesystemRepositoryInterface;
use WPML\StringTranslation\Application\Setting\Repository\PluginRepositoryInterface;
use WPML\StringTranslation\Application\Setting\Repository\SettingsRepositoryInterface;
use WPML\StringTranslation\Application\Setting\Repository\UrlRepositoryInterface;
use WPML\StringTranslation\Application\Translation\Query\FindTranslationDetailsQueryInterface;


use WPML\StringTranslation\Application\StringPackage\Query\FindStringPackagesQueryInterface;
use WPML\StringTranslation\Application\StringPackage\Query\SearchPopulatedKindsQueryInterface;
use WPML\StringTranslation\Infrastructure\StringPackage\Query\FindStringPackagesQuery;
use WPML\StringTranslation\Infrastructure\StringCore\Command\InsertStringsCommand;
use WPML\StringTranslation\Infrastructure\StringCore\Command\InsertStringPositionsCommand;
use WPML\StringTranslation\Infrastructure\StringCore\Command\InsertStringTranslationsCommand;
use WPML\StringTranslation\Infrastructure\StringCore\Command\LoadExistingStringTranslationsCommand;
use WPML\StringTranslation\Infrastructure\StringCore\Command\LoadExistingStringTranslationsForAllStringsCommand;
use WPML\StringTranslation\Infrastructure\StringCore\Command\UpdateStringsCommand;
use WPML\StringTranslation\Infrastructure\StringCore\Command\SaveStringsCommand;
use WPML\StringTranslation\Infrastructure\StringCore\Command\SaveStringPositionsCommand;
use WPML\StringTranslation\Infrastructure\StringCore\Query\FetchFiltersQuery;
use WPML\StringTranslation\Infrastructure\StringCore\Query\FindCountBySearchCriteriaQuery;
use WPML\StringTranslation\Infrastructure\StringCore\Query\FindAllStringsCountQuery;
use WPML\StringTranslation\Infrastructure\StringCore\Query\FindAllStringsQuery;
use WPML\StringTranslation\Infrastructure\StringCore\Query\FindBySearchCriteriaQuery;
use WPML\StringTranslation\Infrastructure\StringCore\Query\FindByDomainValueAndContextQuery;
use WPML\StringTranslation\Infrastructure\StringCore\Query\FindByIdQuery;
use WPML\StringTranslation\Infrastructure\StringCore\Repository\ComponentRepository;
use WPML\StringTranslation\Infrastructure\StringCore\Repository\TranslationsRepository;
use WPML\StringTranslation\Infrastructure\StringGettext\Command\ClearAllStoragesCommand;
use WPML\StringTranslation\Infrastructure\StringGettext\Command\ProcessPendingStringsCommand;
use WPML\StringTranslation\Infrastructure\StringGettext\Command\DeletePendingStringsCommand;
use WPML\StringTranslation\Infrastructure\StringGettext\Command\InitStorageCommand;
use WPML\StringTranslation\Infrastructure\StringGettext\Command\SavePendingStringsCommand;
use WPML\StringTranslation\Infrastructure\StringGettext\Command\SaveProcessedStringsCommand;
use WPML\StringTranslation\Infrastructure\StringGettext\Repository\FrontendQueueRepository;
use WPML\StringTranslation\Infrastructure\StringGettext\Repository\QueueRepository;
use WPML\StringTranslation\Infrastructure\StringGettext\Repository\LoadedTextdomainRepository;
use WPML\StringTranslation\Infrastructure\StringGettext\Validator\IsExcludedDomainStringValidator;
use WPML\StringTranslation\Infrastructure\StringHtml\Command\QueueGettextStringsToBeSetAsFrontendCommand;
use WPML\StringTranslation\Infrastructure\StringHtml\Repository\GettextStringsRepository;
use WPML\StringTranslation\Infrastructure\StringHtml\Repository\HtmlStringsFromScriptTagRepository;
use WPML\StringTranslation\Infrastructure\StringHtml\Repository\HtmlStringsRepository;
use WPML\StringTranslation\Infrastructure\StringHtml\Repository\JsonStringsRepository;
use WPML\StringTranslation\Infrastructure\StringHtml\Validator\IsExcludedHtmlStringValidator;
use WPML\StringTranslation\Infrastructure\Setting\Repository\FilesystemRepository;
use WPML\StringTranslation\Infrastructure\Setting\Repository\PluginRepository;
use WPML\StringTranslation\Infrastructure\Setting\Repository\SettingsRepository;
use WPML\StringTranslation\Infrastructure\Setting\Repository\UrlRepository;
use WPML\StringTranslation\Infrastructure\StringPackage\Query\SearchPopulatedKindsQuery;
use WPML\StringTranslation\Infrastructure\Translation\Query\FindTranslationDetailsQuery;
use WPML\StringTranslation\Application\StringPackage\Repository\WidgetPackageRepositoryInterface;
use WPML\StringTranslation\Infrastructure\StringPackage\Repository\WidgetPackageRepository;

return [
	InsertStringsCommandInterface::class => InsertStringsCommand::class,
	InsertStringPositionsCommandInterface::class => InsertStringPositionsCommand::class,
	InsertStringTranslationsCommandInterface::class => InsertStringTranslationsCommand::class,
	LoadExistingStringTranslationsCommandInterface::class => LoadExistingStringTranslationsCommand::class,
	LoadExistingStringTranslationsForAllStringsCommandInterface::class => LoadExistingStringTranslationsForAllStringsCommand::class,
	UpdateStringsCommandInterface::class => UpdateStringsCommand::class,
	SaveStringsCommandInterface::class => SaveStringsCommand::class,
	SaveStringPositionsCommandInterface::class => SaveStringPositionsCommand::class,
	FetchFiltersQueryInterface::class => FetchFiltersQuery::class,
	FindCountBySearchCriteriaQueryInterface::class => FindCountBySearchCriteriaQuery::class,
	FindAllStringsCountQueryInterface::class => FindAllStringsCountQuery::class,
	FindAllStringsQueryInterface::class => FindAllStringsQuery::class,
	FindBySearchCriteriaQueryInterface::class => FindBySearchCriteriaQuery::class,
	FindByDomainValueAndContextQueryInterface::class => FindByDomainValueAndContextQuery::class,
	FindByIdQueryInterface::class => FindByIdQuery::class,
	ComponentRepositoryInterface::class => ComponentRepository::class,
	TranslationsRepositoryInterface::class => TranslationsRepository::class,
	ClearAllStoragesCommandInterface::class => ClearAllStoragesCommand::class,
	ProcessPendingStringsCommandInterface::class => ProcessPendingStringsCommand::class,
	DeletePendingStringsCommandInterface::class => DeletePendingStringsCommand::class,
	InitStorageCommandInterface::class => InitStorageCommand::class,
	SavePendingStringsCommandInterface::class => SavePendingStringsCommand::class,
	SaveProcessedStringsCommandInterface::class => SaveProcessedStringsCommand::class,
	FrontendQueueRepositoryInterface::class => FrontendQueueRepository::class,
	QueueRepositoryInterface::class => QueueRepository::class,
	LoadedTextdomainRepositoryInterface::class => LoadedTextdomainRepository::class,
	IsExcludedDomainStringValidatorInterface::class => IsExcludedDomainStringValidator::class,
	QueueGettextStringsToBeSetAsFrontendCommandInterface::class => QueueGettextStringsToBeSetAsFrontendCommand::class,
	GettextStringsRepositoryInterface::class => GettextStringsRepository::class,
	HtmlStringsFromScriptTagRepositoryInterface::class => HtmlStringsFromScriptTagRepository::class,
	HtmlStringsRepositoryInterface::class => HtmlStringsRepository::class,
	JsonStringsRepositoryInterface::class => JsonStringsRepository::class,
	IsExcludedHtmlStringValidatorInterface::class => IsExcludedHtmlStringValidator::class,
	FilesystemRepositoryInterface::class => FilesystemRepository::class,
	PluginRepositoryInterface::class => PluginRepository::class,
	SettingsRepositoryInterface::class => SettingsRepository::class,
	UrlRepositoryInterface::class => UrlRepository::class,
	FindTranslationDetailsQueryInterface::class => FindTranslationDetailsQuery::class,

	FindStringPackagesQueryInterface::class => FindStringPackagesQuery::class,
	SearchPopulatedKindsQueryInterface::class => SearchPopulatedKindsQuery::class,
	WidgetPackageRepositoryInterface::class => WidgetPackageRepository::class,
];
