<?php

namespace WPML\Infrastructure\WordPress\Component\Item\Application\Query;

use WPML\Core\Port\Persistence\Exception\DatabaseErrorException;
use WPML\Core\Port\Persistence\QueryHandlerInterface;
use WPML\Core\Port\Persistence\QueryPrepareInterface;
use WPML\Core\SharedKernel\Component\Item\Application\Query\Dto\UntranslatedTypeCountDto;
use WPML\Core\SharedKernel\Component\Item\Application\Query\UntranslatedTypesCountQueryInterface;
use WPML\Core\SharedKernel\Component\Language\Application\Query\LanguagesQueryInterface;
use WPML\Core\SharedKernel\Component\Post\Application\Query\Dto\PostTypeDto;
use WPML\Core\SharedKernel\Component\Post\Application\Query\TranslatableTypesQueryInterface;
use WPML\Core\SharedKernel\Component\Translation\Domain\TranslationStatus;

/**
 * @phpstan-type UntranslatedCountRow array{
 *   post_type: string,
 *   count: int,
 * }
 */
class UntranslatedTypesCountQuery implements UntranslatedTypesCountQueryInterface {

  const POST_META_KEY_USE_NATIVE_EDITOR = '_wpml_post_translation_editor_native';

  /** @phpstan-var  QueryHandlerInterface<int, UntranslatedCountRow> $queryHandler */
  private $queryHandler;

  /** @var QueryPrepareInterface */
  private $queryPrepare;

  /** @var TranslatableTypesQueryInterface */
  private $translatableTypesQuery;

  /** @var LanguagesQueryInterface */
  private $languagesQuery;


  /**
   * @phpstan-param QueryHandlerInterface<int, UntranslatedCountRow> $queryHandler
   *
   * @param QueryPrepareInterface $queryPrepare
   * @param TranslatableTypesQueryInterface $translatableTypesQuery
   * @param LanguagesQueryInterface $languagesQuery
   */
  public function __construct(
    QueryHandlerInterface $queryHandler,
    QueryPrepareInterface $queryPrepare,
    TranslatableTypesQueryInterface $translatableTypesQuery,
    LanguagesQueryInterface $languagesQuery
  ) {
    $this->queryHandler           = $queryHandler;
    $this->queryPrepare           = $queryPrepare;
    $this->translatableTypesQuery = $translatableTypesQuery;
    $this->languagesQuery         = $languagesQuery;
  }


  /**
   * @return UntranslatedTypeCountDto[]
   */
  public function get(): array {
    $translatableTypes = $this->translatableTypesQuery->getTranslatable();

    $typesWithoutAttachmentType = array_filter(
      $translatableTypes,
      function ( $postTypeDto ) {
        return $postTypeDto->getId() !== 'attachment';
      }
    );

    $postTypes = array_map(
      function ( $postTypesDto ) {
        return "'post_" . $postTypesDto->getId() . "'";
      },
      $typesWithoutAttachmentType
    );

    $typesIn = implode( ',', $postTypes );

    $statusesIn = implode(
      ',',
      [
        TranslationStatus::NOT_TRANSLATED,
        TranslationStatus::ATE_CANCELED
      ]
    );

    $defaultLanguageCode = $this->languagesQuery->getDefaultCode();

    $secondaryLanguages = $this->languagesQuery->getSecondary();

    $sql = "
    SELECT translations.post_type, COUNT(translations.ID) count
			FROM (
	            SELECT RIGHT(element_type, LENGTH(element_type) - 5) as post_type, posts.ID
	            FROM {$this->queryPrepare->prefix()}icl_translations
	            INNER JOIN {$this->queryPrepare->prefix()}posts posts ON element_id = ID
                
                LEFT JOIN {$this->queryPrepare->prefix()}postmeta postmeta 
                  ON postmeta.post_id = posts.ID 
                  AND postmeta.meta_key = %s
	                                        
	            WHERE element_type IN ($typesIn)
		           AND post_status = 'publish'
		           AND source_language_code IS NULL
		           AND language_code = %s
		           AND (
	                   SELECT COUNT(trid)
	                   FROM {$this->queryPrepare->prefix()}icl_translations icl_translations_inner
	                   INNER JOIN {$this->queryPrepare->prefix()}icl_translation_status icl_translations_status
	                   ON icl_translations_inner.translation_id = icl_translations_status.translation_id
	                   WHERE icl_translations_inner.trid = {$this->queryPrepare->prefix()}icl_translations.trid
	                     AND icl_translations_status.status NOT IN ({$statusesIn})
	                     AND icl_translations_status.needs_update != 1
	               ) < %d
	               AND ( postmeta.meta_value IS NULL OR postmeta.meta_value = 'no' )
	         ) as translations
			GROUP BY translations.post_type;
    ";

    $preparedSql = $this->queryPrepare->prepare(
      $sql,
      self::POST_META_KEY_USE_NATIVE_EDITOR,
      $defaultLanguageCode,
      count( $secondaryLanguages )
    );

    try {
      /** @var array<array{post_type: string, count: int}> $untranslatedTypesCount */
      $untranslatedTypesCount = $this->queryHandler->query( $preparedSql )->getResults();
    } catch ( DatabaseErrorException $e ) {
      $untranslatedTypesCount = [];
    }

    return $this->mapTypesToTypeWithCountDto( $untranslatedTypesCount, $translatableTypes );
  }


  /**
   * @param array<array{post_type: string, count: int}> $untranslatedTypesCount
   * @param PostTypeDto[] $translatablePostTypesDto
   *
   * @return UntranslatedTypeCountDto[]
   */
  private function mapTypesToTypeWithCountDto( array $untranslatedTypesCount, array $translatablePostTypesDto ): array {
    return array_map(
      function ( $typeWithCount ) use ( $translatablePostTypesDto ) {

        /** @var PostTypeDto|false $postTypeDto */
        $postTypeDto = current(
          array_filter(
            $translatablePostTypesDto,
            function ( $dto ) use ( $typeWithCount ) {
              return $dto->getId() === $typeWithCount['post_type'];
            }
          )
        );

        return new UntranslatedTypeCountDto(
          $postTypeDto ? $postTypeDto->getPlural() : $typeWithCount['post_type'],
          $postTypeDto ? $postTypeDto->getSingular() : $typeWithCount['post_type'],
          $typeWithCount['count']
        );
      },
      $untranslatedTypesCount
    );
  }


}
