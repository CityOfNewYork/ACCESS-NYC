<?php

namespace WPML\Infrastructure\WordPress\Component\Translation\Application\Query;

use WPML\Core\Component\Translation\Application\Query\TranslationQueryInterface;
use WPML\Core\Component\Translation\Domain\TranslationType;

class RegularItemsAndStringsTranslationQuery implements TranslationQueryInterface {

  /** @var TranslationQuery */
  private $regularTranslationQuery;

  /** @var StringTranslationQuery */
  private $stringTranslationQuery;


  public function __construct(
    TranslationQuery $regularTranslationQuery,
    StringTranslationQuery $stringTranslationQuery
  ) {
    $this->regularTranslationQuery = $regularTranslationQuery;
    $this->stringTranslationQuery  = $stringTranslationQuery;
  }


  public function getManyByJobIds( array $jobIds ): array {
    return $this->regularTranslationQuery->getManyByJobIds( $jobIds );
  }


  public function getOneByJobId( int $jobId ) {
    return $this->regularTranslationQuery->getOneByJobId( $jobId );
  }


  public function getManyByTranslatedElementIds( array $translatedElementIds ): array {
    return $this->regularTranslationQuery->getManyByTranslatedElementIds( $translatedElementIds );
  }


  public function getManyByElementIds( TranslationType $translationType, array $elementIds ): array {
    if (
        $translationType->get() === TranslationType::STRING ||
        $translationType->get() === TranslationType::STRING_BATCH
    ) {
      return $this->stringTranslationQuery->getStringTranslations( $elementIds );
    }

    return $this->regularTranslationQuery->getManyByElementIds( $translationType, $elementIds );
  }


}
