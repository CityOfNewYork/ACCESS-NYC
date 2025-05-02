<?php

namespace WPML\Core\Component\Post\Application\Query\Criteria;

use WPML\Core\SharedKernel\Component\Language\Application\Query\LanguagesQueryInterface;
use WPML\PHP\Exception\Exception;
use WPML\PHP\Exception\InvalidArgumentException;
use WPML\PHP\Value\Validate;

final class SearchCriteriaBuilder {

  /** @var SourceAndTargetLanguagesBuilder */
  private $languagesBuilder;


  public function __construct( LanguagesQueryInterface $languagesQuery ) {
    $this->languagesBuilder = new SourceAndTargetLanguagesBuilder( $languagesQuery );
  }


  /**
   * @param array<string,mixed> $array
   *
   * @return SearchCriteria
   * @throws InvalidArgumentException If a required argument is missing.
   *
   * @throws Exception If the constructor is not accessible.
   */
  public function build( array $array ): SearchCriteria {
    return new SearchCriteria(
      Validate::nonEmptyString( [ $array, 'type' ] ),
      Validate::nonEmptyString( [ $array, 'title' ], null ),
      Validate::nonEmptyString( [ $array, 'publicationStatus' ], null ),
      $this->buildLanguages( $array ),
      Validate::arrayOfSameType(
        [ $array, 'translationStatuses' ],
        [ Validate::class, 'int' ],
        []
      ),
      Validate::int( [ $array, 'parentId' ], null ),
      Validate::nonEmptyString( [ $array, 'taxonomyId' ], null ),
      Validate::int( [ $array, 'termId' ], null ),
      Validate::int( [ $array, 'limit' ], 10 ),
      Validate::int( [ $array, 'offset' ], 0 ),
      Validate::array(
        [ $array, 'sorting' ],
        [
          'by' => [ Validate::class, 'nonEmptyString' ],
          'order' => [ Validate::class, 'nonEmptyString' ]
        ],
        null
      )
    );
  }


  /**
   * @param array<string, mixed> $array
   *
   * @throws InvalidArgumentException
   *
   * @return SourceAndTargetLanguages
   */
  private function buildLanguages( array $array ): SourceAndTargetLanguages {
    $targetLang = Validate::nonEmptyString( [ $array, 'targetLanguageCode' ], null );
    $languages = $this->languagesBuilder->build(
      Validate::nonEmptyString( [ $array, 'sourceLanguageCode' ], null ),
      $targetLang ? [ $targetLang ] : []
    );

    return $languages;
  }


}
