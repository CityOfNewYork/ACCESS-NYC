<?php

// phpcs:ignore PHPCompatibility.Keywords.ForbiddenNamesAsDeclared.stringFound
namespace WPML\Infrastructure\WordPress\Component\String\Application\Query;

use WPML\Core\Port\Persistence\Exception\DatabaseErrorException;
use WPML\Core\Port\Persistence\QueryHandlerInterface;
use WPML\Core\Port\Persistence\QueryPrepareInterface;
use WPML\Core\SharedKernel\Component\Item\Application\Query\Dto\UntranslatedTypeCountDto;
use WPML\Core\SharedKernel\Component\Item\Application\Query\UntranslatedTypesCountQueryInterface;
use WPML\Core\SharedKernel\Component\Language\Application\Query\Dto\LanguageDto;
use WPML\Core\SharedKernel\Component\Language\Application\Query\LanguagesQueryInterface;

class UntranslatedTypesCountQuery implements UntranslatedTypesCountQueryInterface {

  /** @phpstan-var  QueryHandlerInterface<int, int> $queryHandler */
  private $queryHandler;

  /** @var QueryPrepareInterface */
  private $queryPrepare;

  /** @var LanguagesQueryInterface */
  private $languagesQuery;


  /**
   * @param QueryHandlerInterface<int, int> $queryHandler
   * @param QueryPrepareInterface $queryPrepare
   * @param LanguagesQueryInterface $languagesQuery
   */
  public function __construct(
    QueryHandlerInterface $queryHandler,
    QueryPrepareInterface $queryPrepare,
    LanguagesQueryInterface $languagesQuery
  ) {
    $this->queryHandler   = $queryHandler;
    $this->queryPrepare   = $queryPrepare;
    $this->languagesQuery = $languagesQuery;
  }


  /** @return UntranslatedTypeCountDto[] */
  public function get(): array {
    $languageCrossJoin = $this->buildLanguageCrossJoin();

    $sql = "
			SELECT COUNT( DISTINCT strings.id ) as count
			FROM {$this->queryPrepare->prefix()}icl_strings strings
			{$languageCrossJoin}
			LEFT JOIN {$this->queryPrepare->prefix()}icl_string_translations translations
				ON strings.id = translations.string_id AND translations.language = langs.code
			WHERE strings.string_type = 1
				AND ( translations.status IS NULL OR translations.status = 0 )
				AND EXISTS (
	        SELECT 1
	        FROM {$this->queryPrepare->prefix()}icl_string_positions positions
	        WHERE positions.string_id = strings.id
	          AND positions.kind = %d
	    	) AND strings.language = 'en'
			ORDER BY langs.code, strings.id ASC
		";

    $sql = $this->queryPrepare->prepare( $sql, 6 ); // only frontend strings see: ICL_STRING_TRANSLATION_STRING_TRACKING_TYPE_FRONTEND

    try {
      $count = (int) $this->queryHandler->querySingle( $sql );
    } catch ( DatabaseErrorException $e ) {
      $count = 0;
    }

    return [
      new UntranslatedTypeCountDto( 'Strings', 'String', $count )
    ];
  }


  private function buildLanguageCrossJoin(): string {
    $secondary = array_map(
      function ( LanguageDto $language ) {
        return $language->getCode();
      },
      $this->languagesQuery->getSecondary()
    );

    // We include only English strings in TEA process so it does not make sense to support EN as secondary language
    $secondaryWithoutEnglish = array_filter(
      $secondary,
      function ( string $language ) {
        return $language !== 'en';
      }
    );

    $languageSelect    = array_map(
      function ( $languageCode ) {
        return "SELECT '{$languageCode}' AS code";
      },
      $secondaryWithoutEnglish
    );
    $languageSelect    = implode( ' UNION ALL ', $languageSelect );
    $languageCrossJoin = "
			CROSS JOIN (
				$languageSelect
			) as langs
		";

    return $languageCrossJoin;
  }


}
