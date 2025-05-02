<?php

namespace WPML\Legacy\Component\Translation\Sender;

use WPML\Core\Component\Translation\Domain\TranslationBatch\DuplicationBatch;

class DuplicationBatchMapper {


  public function map( DuplicationBatch $batch ): \WPML_TM_Translation_Batch {
    $elements    = $this->buildElements( $batch );
    $translators = $this->buildTranslators( $batch );

    return new \WPML_TM_Translation_Batch(
      $elements,
      $batch->getBatchName(),
      $translators
    );
  }


  /**
   * @param DuplicationBatch $batch
   *
   * @return \WPML_TM_Translation_Batch_Element[]
   */
  private function buildElements( DuplicationBatch $batch ): array {
    $targetLanguagesCodes = $batch->getTargetLanguages();

    /** @var array<string, int> $targetLanguages */
    $targetLanguages = array_combine(
      $targetLanguagesCodes,
      array_fill( 0, count( $targetLanguagesCodes ), 2 ) // 2 represents "duplicate" action
    );

    $elements = [];
    foreach ( $batch->getPostIds() as $postId ) {
      $mediaTranslations = [];

      $elements[] = new \WPML_TM_Translation_Batch_Element(
        $postId,
        'post',
        $batch->getSourceLanguageCode(),
        $targetLanguages,
        $mediaTranslations
      );
    }

    return $elements;
  }


  /**
   * @param DuplicationBatch $batch
   *
   * @return array<string, int>
   */
  private function buildTranslators( DuplicationBatch $batch ): array {
    $targetLanguagesCodes = $batch->getTargetLanguages();

    /** @var array<string, int> $result */
    $result = array_combine(
      $targetLanguagesCodes,
      array_fill( 0, count( $targetLanguagesCodes ), 0 ) // 0 -> no translator
    );

    return $result;
  }


}
