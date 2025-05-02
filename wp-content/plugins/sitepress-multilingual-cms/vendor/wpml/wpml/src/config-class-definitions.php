<?php

// Mapping of class definitions.
// Use this if a class needs a specific implementation other than the default
// interface mapping (of config-interface-mappings.php).

// FORMAT: className => [ 'argument1Name' => className1, 'argument2Name' => className2, ... ]
// You only need to specify the arguments that need a specific implementation.

// Example:
// MyClass::__construct(Interface1 $arg1, Interface2 $arg2)
// I only want to specify the implementation of $arg2 so the mapping would be:
// MyClass::class => ['arg2' => SpecificImplementation::class]

use WPML\Core\Component\Translation\Domain\TranslationBatch\Validator\CompletedTranslationValidator;
use WPML\Core\Component\Translation\Domain\TranslationBatch\Validator\ElementTargetLanguageValidator;
use WPML\Core\SharedKernel\Component\Language\Application\Query\LanguagesQueryInterface;
use WPML\Core\SharedKernel\Component\User\Application\Query\UserQueryInterface;
use WPML\DicInterface;
use WPML\Infrastructure\WordPress\Component\Item\Application\Query\SearchQuery\QueryBuilder\ManyLanguagesStrategy\QueryBuilderFactory as ManyTargetLanguagesFactory;
use WPML\Infrastructure\WordPress\Component\Item\Application\Query\SearchQuery\QueryBuilder\ManyLanguagesStrategy\SearchPopulatedTypesQueryBuilder as ManyLanguagesStrategySearchPopulatedTypesQueryBuilder;
use WPML\Infrastructure\WordPress\Component\Item\Application\Query\SearchQuery\QueryBuilder\ManyLanguagesStrategy\SearchQueryBuilder as ManyLanguagesStrategySearchQueryBuilder;
use WPML\Infrastructure\WordPress\Component\Item\Application\Query\SearchQuery\QueryBuilder\MultiJoinStrategy\QueryBuilderFactory as MultiJoinFactory;
use WPML\Infrastructure\WordPress\Component\Item\Application\Query\SearchQuery\QueryBuilder\MultiJoinStrategy\SearchPopulatedTypesQueryBuilder as MultiJoinStrategySearchPopulatedTypesQueryBuilder;
use WPML\Infrastructure\WordPress\Component\Item\Application\Query\SearchQuery\QueryBuilder\MultiJoinStrategy\SearchQueryBuilder as MultiJoinStrategySearchQueryBuilder;
use WPML\Infrastructure\WordPress\Component\Item\Application\Query\SearchQuery\QueryBuilder\QueryBuilderResolver;
use WPML\Infrastructure\WordPress\Component\Item\Application\Query\UntranslatedTypesCountQuery as PostUntranslatedTypesCountQuery;
use WPML\Infrastructure\WordPress\Component\String\Application\Query\UntranslatedTypesCountQuery as StringUntranslatedTypesCountQuery;
use WPML\Infrastructure\WordPress\Component\StringPackage\Application\Query\UntranslatedTypesCountQuery as PackageUntranslatedTypesCountQuery;
use WPML\Legacy\Component\Language\Application\Query\AutomaticTranslationsSupportInfoDecoratorForLanguagesQuery;
use WPML\Legacy\Component\Translation\Domain\TranslationBatch\Validator\Base64Validator;
use WPML\Legacy\Component\Translation\Sender\ErrorMapper\LegacyAteJobCreationError;
use WPML\Legacy\Component\Translation\Sender\ErrorMapper\UnsupportedLanguagesInTranslationService;
use WPML\UserInterface\Web\Core\Component\Dashboard\Application\Endpoint\GetUntranslatedTypesCount\GetUntranslatedTypesCountController;
use WPML\UserInterface\Web\Core\Component\Notices\PromoteUsingDashboard\Application\Repository\ManualTranslationsCountRepositoryInterface;
use WPML\UserInterface\Web\Core\Component\Notices\PromoteUsingDashboard\Application\Service\ManualTranslationsCountService;
use WPML\UserInterface\Web\Infrastructure\WordPress\CompositionRoot\Config\ExistingPage\PostEditPage;
use WPML\UserInterface\Web\Infrastructure\WordPress\CompositionRoot\Config\ExistingPage\PostListingPage;

return [
  \WPML\Core\Component\Translation\Application\Service\TranslationService::class                                     =>
    [ 'batchBuilder' => \WPML\Core\Component\Translation\Application\String\StringBatchBuilder::class ],
  \WPML\Core\Component\Translation\Application\Service\TranslatorNoteService::class                                  =>
    [
      'stringPackageTranslatorNoteRepo' =>
      // phpcs:ignore Glingener.File.LineLength.LineTooLong
        \WPML\Infrastructure\WordPress\Component\Translation\Application\Repository\StringPackageTranslatorNoteRepository::class
    ],
  \WPML\UserInterface\Web\Core\Component\Dashboard\Application\Endpoint\GetPosts\WordCountDecoratorController::class =>
    [
      'innerController' =>
        \WPML\UserInterface\Web\Core\Component\Dashboard\Application\Endpoint\GetPosts\GetPostsController::class
    ],

  GetUntranslatedTypesCountController::class =>
    function ( DicInterface $dic ) {
      $queries = [ $dic->make( PostUntranslatedTypesCountQuery::class ) ];

      if ( defined( 'WPML_ST_VERSION' ) ) {
        $queries[] = $dic->make( PackageUntranslatedTypesCountQuery::class );
        $queries[] = $dic->make( StringUntranslatedTypesCountQuery::class );
      }

      return new GetUntranslatedTypesCountController( $queries );
    },

  AutomaticTranslationsSupportInfoDecoratorForLanguagesQuery::class =>
    [ 'languagesQuery' => \WPML\Legacy\Component\Language\Application\Query\LanguagesQuery::class ],

  \WPML\UserInterface\Web\Core\Component\Dashboard\Application\Endpoint\TranslateEverything\EnableController::class =>
    [
      'languagesQuery' => AutomaticTranslationsSupportInfoDecoratorForLanguagesQuery::class
    ],
  \WPML\UserInterface\Web\Core\Component\Preferences\Application\LanguagePreferencesLoader::class =>
    [
      'languagesQuery' => AutomaticTranslationsSupportInfoDecoratorForLanguagesQuery::class
    ],
  \WPML\Core\Component\Translation\Domain\TranslationBatch\Validator\ValidatorInterface::class                      =>
    function ( DicInterface $dic ) {
      return new \WPML\Core\Component\Translation\Domain\TranslationBatch\Validator\CompositeValidator(
        [
          new ElementTargetLanguageValidator(),
          new CompletedTranslationValidator(),
          new Base64Validator()
        ],
        new \WPML\Core\Component\Translation\Domain\TranslationBatch\Validator\EmptyMethodsValidator()
      );
    },
  \WPML\Legacy\Component\Translation\Sender\ErrorMapper\ErrorMapper::class =>
    function ( DicInterface $dic ) {
      return new \WPML\Legacy\Component\Translation\Sender\ErrorMapper\ErrorMapper(
        [
          $dic->make( UnsupportedLanguagesInTranslationService::class ),
          $dic->make( LegacyAteJobCreationError::class )
        ]
      );
    },
  QueryBuilderResolver::class =>
    function ( DicInterface $dic ) {
      return new QueryBuilderResolver(
        $dic->make( LanguagesQueryInterface::class ),
        new ManyTargetLanguagesFactory(
          $dic->make( ManyLanguagesStrategySearchQueryBuilder::class ),
          $dic->make( ManyLanguagesStrategySearchPopulatedTypesQueryBuilder::class )
        ),
        new MultiJoinFactory(
          $dic->make( MultiJoinStrategySearchQueryBuilder::class ),
          $dic->make( MultiJoinStrategySearchPopulatedTypesQueryBuilder::class )
        )
      );
    },
  ManualTranslationsCountService::class =>
    function ( DicInterface $dic ) {
      return new ManualTranslationsCountService(
        $dic->make( ManualTranslationsCountRepositoryInterface::class ),
        $dic->make( UserQueryInterface::class ),
        [
          $dic->make( PostListingPage::class ),
          $dic->make( PostEditPage::class ),
        ]
      );
    }
];
