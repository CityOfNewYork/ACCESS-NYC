<?php

// Mapping of interfaces to implementations.

use WPML\UserInterface\Web\Core\Component\Dashboard\Application\Endpoint\GetPopulatedItemSections\PopulatedItemSectionsFilterInterface;
use WPML\UserInterface\Web\Core\Component\Notices\PromoteUsingDashboard\Application\Repository\DashboardTranslationsRepositoryInterface;
use WPML\UserInterface\Web\Core\Component\Notices\PromoteUsingDashboard\Application\Repository\ManualTranslationsCountRepositoryInterface;
use WPML\UserInterface\Web\Infrastructure\WordPress\Component\NoticeStartUsingDashboard\Application\Repository\DashboardTranslationsRepository;
use WPML\UserInterface\Web\Infrastructure\WordPress\Component\NoticeStartUsingDashboard\Application\Repository\ManualTranslationsCountRepository;

return [

  /** CORE **/
  \WPML\Core\SharedKernel\Component\User\Application\Query\UserQueryInterface::class =>
    \WPML\Infrastructure\WordPress\Component\User\Application\Query\UserQuery::class,

  \WPML\Core\Component\Post\Application\Query\SearchQueryInterface::class =>
    \WPML\Infrastructure\WordPress\Component\Item\Application\Query\SearchQuery::class,

  \WPML\Core\SharedKernel\Component\Post\Domain\Repository\RepositoryInterface::class =>
    \WPML\Infrastructure\WordPress\SharedKernel\Post\Domain\Repository\Repository::class,

  \WPML\Core\Component\Post\Application\Query\HierarchicalPostQueryInterface::class =>
    \WPML\Legacy\Component\Post\Application\Query\HierarchicalPostQuery::class,

  \WPML\Core\Component\Post\Application\Query\PermalinkQueryInterface::class =>
    \WPML\Infrastructure\WordPress\Component\Item\Application\Query\PermalinkQuery::class,

  \WPML\Core\Component\Post\Application\Query\PublicationStatusQueryInterface::class =>
    \WPML\Infrastructure\WordPress\Component\Item\Application\Query\PublicationStatusQuery::class,

  \WPML\Core\Component\Post\Application\Query\TaxonomyQueryInterface::class =>
    \WPML\Legacy\Component\Post\Application\Query\TaxonomyQuery::class,

  \WPML\Core\Component\Translation\Application\Repository\TranslatorNoteRepositoryInterface::class =>
    \WPML\Infrastructure\WordPress\Component\Translation\Application\Repository\PostTranslatorNoteRepository::class,

  \WPML\Core\Component\Translation\Application\Repository\TranslationRepositoryInterface::class =>
    \WPML\Legacy\Component\Translation\Application\Repository\TranslationRepository::class,

  \WPML\Core\SharedKernel\Component\Post\Application\Query\TranslatableTypesQueryInterface::class =>
    \WPML\Legacy\Component\Post\Application\Query\TranslatableTypesQuery::class,

  \WPML\Core\SharedKernel\Component\String\Application\Query\StringLanguageQueryInterface::class =>
    \WPML\Infrastructure\WordPress\Component\String\Application\Query\StringLanguageQuery::class,

  \WPML\Core\Component\Translation\Application\String\Query\StringsFromBatchQueryInterface::class =>
    \WPML\Infrastructure\WordPress\Component\Translation\Application\String\Query\StringsFromBatchQuery::class,

  \WPML\Core\Component\Translation\Application\Query\TranslationBatchesQueryInterface::class =>
    \WPML\Infrastructure\WordPress\Component\Translation\Application\Query\TranslationBatchesQuery::class,

  \WPML\Core\Component\Translation\Application\Query\NeedsUpdateCreatedInCteQueryInterface::class =>
    \WPML\Infrastructure\WordPress\Component\Translation\Application\Query\NeedsUpdateCreatedInCteQuery::class,

  \WPML\Core\Component\Translation\Application\Query\TranslationStatusQueryInterface::class =>
    \WPML\Infrastructure\WordPress\Component\Translation\Application\Query\TranslationStatusQuery::class,

  \WPML\UserInterface\Web\Core\Component\Notices\WarningTranslationEdit\Application\TranslationEditorInterface::class =>
    \WPML\UserInterface\Web\Legacy\Component\Translation\TranslationEditor::class,

  \WPML\UserInterface\Web\Core\Port\Asset\AssetInterface::class =>
    \WPML\UserInterface\Web\Infrastructure\WordPress\Port\Asset\Asset::class,

  \WPML\Core\Component\Translation\Application\Query\ItemLanguageQueryInterface::class =>
    \WPML\Infrastructure\WordPress\Component\Translation\Application\Query\RegularItemsAndStringsLanguageQuery::class,

  \WPML\Core\Component\Translation\Application\Query\TranslationQueryInterface::class =>
    \WPML\Infrastructure\WordPress\Component\Translation\Application\Query\RegularItemsAndStringsTranslationQuery::class, // phpcs:ignore

  \WPML\Core\Component\Translation\Application\Query\JobQueryInterface::class =>
    \WPML\Infrastructure\WordPress\Component\Translation\Application\Query\JobQuery::class,

  \WPML\Core\Component\ATE\Application\Query\AccountInterface::class =>
    \WPML\Legacy\Component\ATE\Application\Query\Account::class,

  \WPML\Core\Component\ATE\Application\Query\GlossaryInterface::class =>
    \WPML\Legacy\Component\ATE\Application\Query\Glossary::class,

  \WPML\Core\Port\Persistence\OptionsInterface::class =>
    \WPML\Infrastructure\WordPress\Port\Persistence\Options::class,

  \WPML\Core\Port\Persistence\QueryHandlerInterface::class =>
    \WPML\Infrastructure\WordPress\Port\Persistence\QueryHandler::class,

  \WPML\Core\Port\Persistence\QueryPrepareInterface::class =>
    \WPML\Infrastructure\WordPress\Port\Persistence\QueryPrepare::class,

  \WPML\Core\Port\Persistence\DatabaseAlterInterface::class =>
    \WPML\Infrastructure\WordPress\Port\Persistence\DatabaseAlter::class,

  \WPML\Core\Port\Persistence\DatabaseWriteInterface::class =>
    \WPML\Infrastructure\WordPress\Port\Persistence\DatabaseWrite::class,

  \WPML\Core\Component\Translation\Domain\Sender\TranslationSenderInterface::class =>
    \WPML\Legacy\Component\Translation\Sender\TranslationSender::class,

  \WPML\Core\Component\Translation\Domain\Sender\DuplicationSenderInterface::class =>
    \WPML\Legacy\Component\Translation\Sender\DuplicationSender::class,

  \WPML\Core\Component\Translation\Application\Query\PostTranslationQueryInterface::class =>
    \WPML\Infrastructure\WordPress\Component\Translation\Application\Query\PostTranslationQuery::class,

  \WPML\Core\SharedKernel\Component\Language\Application\Query\LanguagesQueryInterface::class =>
    \WPML\Legacy\Component\Language\Application\Query\LanguagesQuery::class,

  \WPML\Core\Component\Translation\Application\Service\TranslationService\BatchBuilder\BatchBuilderInterface::class =>
    \WPML\Core\Component\Translation\Application\Service\TranslationService\BatchBuilder\BatchBuilder::class,

  \WPML\Core\Component\Translation\Application\String\Repository\StringBatchRepositoryInterface::class =>
    \WPML\Legacy\Component\Translation\Application\String\Repository\StringBatchRepository::class,

  \WPML\Core\SharedKernel\Component\Translator\Domain\Query\TranslatorsQueryInterface::class =>
    \WPML\Legacy\Component\Translator\Domain\Query\TranslatorsQuery::class,

  \WPML\Core\SharedKernel\Component\Translator\Domain\Query\TranslatorLanguagePairsQueryInterface::class =>
    \WPML\Infrastructure\WordPress\Component\Translator\Domain\Query\TranslatorLanguagePairsQuery::class,

  \WPML\Core\SharedKernel\Component\TranslationProxy\Domain\Query\RemoteTranslationServiceQueryInterface::class =>
  \WPML\Legacy\Component\TranslationProxy\Domain\Query\RemoteTranslationServiceQuery::class,

  \WPML\Core\Component\TranslationProxy\Application\Service\TranslationProxyServiceInterface::class =>
  \WPML\Legacy\Component\TranslationProxy\Application\Service\TranslationProxyService::class,

  \WPML\Core\Port\Event\DispatcherInterface::class =>
    \WPML\Infrastructure\WordPress\Port\Event\Dispatcher::class,

  \WPML\Core\Component\Translation\Domain\Links\CollectorInterface::class =>
  \WPML\Legacy\Component\Translation\Domain\Links\Collector::class,

  \WPML\Core\Component\Translation\Domain\Links\AdjustLinksInterface::class =>
  \WPML\Legacy\Component\Translation\Domain\Links\AdjustLinks::class,

  \WPML\Core\Component\Translation\Domain\Links\RepositoryInterface::class =>
  \WPML\Infrastructure\WordPress\Component\Translation\Domain\Links\Repository::class,

  \WPML\Core\SharedKernel\Component\Post\Domain\PublicationStatusDefinitionsInterface::class =>
  \WPML\Infrastructure\WordPress\SharedKernel\Post\Domain\PublicationStatusDefinitions::class,
  \WPML\Core\Component\TranslationProxy\Application\Service\LastPickedUpDateServiceInterface::class =>
  \WPML\Legacy\Component\TranslationProxy\Application\Service\LastPickedUpDateService::class,

  \WPML\Core\Component\TranslationProxy\Application\Query\RemoteJobsQueryInterface::class =>
  \WPML\Infrastructure\WordPress\Component\TranslationProxy\Application\Query\RemoteJobsQuery::class,

  \WPML\Core\Component\Post\Domain\WordCount\StripCodeInterface::class =>
    \WPML\Infrastructure\WordPress\Component\Item\Domain\WordCount\StripCode::class,

  \WPML\Core\SharedKernel\Component\String\Domain\Repository\RepositoryInterface::class =>
    \WPML\Infrastructure\WordPress\Component\String\Domain\Repository\Repository::class,

  \WPML\Core\SharedKernel\Component\StringPackage\Domain\Repository\RepositoryInterface::class =>
    \WPML\Infrastructure\WordPress\Component\StringPackage\Domain\Repository\Repository::class,

  \WPML\Core\Component\StringPackage\Application\Query\PackageDefinitionQueryInterface::class =>
    \WPML\Infrastructure\WordPress\Component\StringPackage\Application\Query\PackageDefinitionQuery::class,

  \WPML\Core\SharedKernel\Component\Post\Domain\Repository\MetadataRepositoryInterface::class =>
    \WPML\Infrastructure\WordPress\SharedKernel\Post\Domain\Repository\MetadataRepository::class,

  \WPML\Core\Component\Communication\Domain\DismissedNoticesStorageInterface::class =>
    \WPML\Infrastructure\WordPress\Component\Communication\Domain\DismissedNoticesStorage::class,

  WPML\Core\Component\Post\Domain\WordCount\ItemContentCalculator\PostContentFilterInterface::class =>
    WPML\Infrastructure\WordPress\Component\Item\Domain\WordCount\ItemContentCalculator\PostContentFilter::class,

  \WPML\Core\Component\ATE\Application\Service\EnginesServiceInterface::class =>
    \WPML\Legacy\Component\ATE\Application\Service\EnginesService::class,

  /** USER INTERFACE **/
  \WPML\UserInterface\Web\Core\Component\Dashboard\Application\Hook\DashboardPublicationStatusFilterInterface::class =>
    \WPML\UserInterface\Web\Infrastructure\WordPress\Port\Hook\DashboardPublicationStatusFilter::class,

  \WPML\UserInterface\Web\Core\Component\Dashboard\Application\Hook\DashboardItemSectionsFilterInterface::class =>
  \WPML\UserInterface\Web\Infrastructure\WordPress\Port\Hook\DashboardItemSectionsFilter::class,

  WPML\UserInterface\Web\Core\Component\Dashboard\Application\Query\DashboardTranslatableTypesQueryInterface::class =>
    WPML\UserInterface\Web\Infrastructure\WordPress\Component\Dashboard\Query\DashboardTranslatableTypesQuery::class,

  WPML\UserInterface\Web\Core\Component\Dashboard\Application\Hook\DashboardTranslatablePostTypesFilterInterface::class
    => \WPML\UserInterface\Web\Infrastructure\WordPress\Port\Hook\DashboardTranslatablePostTypesFilter::class,

  WPML\UserInterface\Web\Core\Component\Dashboard\Application\DashboardTabsInterface::class =>
    WPML\UserInterface\Web\Legacy\Component\Dashboard\DashboardTabs::class,

  \WPML\UserInterface\Web\Core\Component\Dashboard\Application\Endpoint\GetPosts\GetPostControllerInterface::class =>
    \WPML\UserInterface\Web\Core\Component\Dashboard\Application\Endpoint\GetPosts\WordCountDecoratorController::class,

  WPML\UserInterface\Web\Core\Component\Dashboard\Application\Endpoint\GetPosts\PostsFilterInterface::class =>
    WPML\UserInterface\Web\Infrastructure\WordPress\Port\Hook\PostsFilter::class,

  WPML\Core\Component\Post\Application\Query\SearchPopulatedTypesQueryInterface::class =>
    \WPML\Infrastructure\WordPress\Component\Item\Application\Query\SearchPopulatedTypesQuery::class,

  PopulatedItemSectionsFilterInterface::class =>
      \WPML\UserInterface\Web\Infrastructure\WordPress\Port\Hook\PopulatedItemSectionsFilter::class,

  DashboardTranslationsRepositoryInterface::class => DashboardTranslationsRepository::class,

  ManualTranslationsCountRepositoryInterface::class => ManualTranslationsCountRepository::class,

];
