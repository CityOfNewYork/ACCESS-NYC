<?php

namespace WPML\Infrastructure\WordPress\Component\Translation\Application\Query;

use WPML\Core\Component\Translation\Domain\Job;
use WPML\Core\Component\Translation\Domain\Translation;
use WPML\Core\Component\Translation\Domain\TranslationEditor\AteEditor;
use WPML\Core\Component\Translation\Domain\TranslationEditor\ClassicEditor;
use WPML\Core\Component\Translation\Domain\TranslationEditor\EditorInterface;
use WPML\Core\Component\Translation\Domain\TranslationEditor\NoneEditor;
use WPML\Core\Component\Translation\Domain\TranslationEditor\WordpressEditor;
use WPML\Core\Component\Translation\Domain\TranslationMethod\AutomaticMethod;
use WPML\Core\Component\Translation\Domain\TranslationMethod\LocalTranslatorMethod;
use WPML\Core\Component\Translation\Domain\TranslationMethod\TranslationMethodInterface;
use WPML\Core\Component\Translation\Domain\TranslationMethod\TranslationServiceMethod;
use WPML\Core\Component\Translation\Domain\TranslationType;
use WPML\Core\SharedKernel\Component\Translation\Domain\ReviewStatus;
use WPML\Core\SharedKernel\Component\Translation\Domain\TranslationEditorType;
use WPML\Core\SharedKernel\Component\Translation\Domain\TranslationStatus;
use WPML\PHP\Exception\InvalidArgumentException;

/**
 * @phpstan-import-type TranslationRow from TranslationQuery
 *
 */
class TranslationResultMapper {


  /**
   * @phpstan-param  TranslationRow $row
   *
   * @return Translation
   * @throws InvalidArgumentException
   */
  public function mapRow( array $row ): Translation {
    $job = null;
    if ( $row['job_id'] && $row['batch_id'] && $row['editor'] ) {
      $job = new Job(
        $row['job_id'],
        $row['batch_id'],
        $this->mapMethod( $row ),
        $row['translation_service'] !== 'local' ?
          new NoneEditor() :
          $this->mapEditor( $row['editor'] ),
        (bool) $row['job_completed'],
        $row['translation_service'] !== 'local' ?
          (int) $row['translation_service'] :
          $row['translator_id']
      );
    }

    return new Translation(
      $row['translation_id'],
      new TranslationStatus( $row['status'] ),
      $this->mapType( $row ),
      $row['original_element_id'],
      $row['source_language_code'],
      $row['language_code'],
      $job,
      $row['translated_element_id'],
      isset( $row['review_status'] ) ? new ReviewStatus( $row['review_status'] ) : null,
      (bool) $row['needs_update']
    );
  }


  private function mapEditor( string $editor ): EditorInterface {
    switch ( $editor ) {
      case TranslationEditorType::ATE:
        return new AteEditor();
      case TranslationEditorType::WORDPRESS:
        return new WordpressEditor();
      case TranslationEditorType::CLASSIC:
        return new ClassicEditor();
      default:
        return new NoneEditor();
    }
  }


  /**
   * @phpstan-param  TranslationRow $row
   *
   * @return TranslationMethodInterface
   */
  private function mapMethod( array $row ): TranslationMethodInterface {
    if ( $row['automatic'] ) {
      $method = new AutomaticMethod();
    } else if ( $row['translation_service'] !== 'local' ) {
      $translationServiceId = (int) $row['translation_service'];
      $method               = new TranslationServiceMethod( $translationServiceId );
    } else {
      $method = new LocalTranslatorMethod( (int) $row['translator_id'], $row['language_code'] );
    }

    return $method;
  }


  /**
   * @phpstan-param  TranslationRow $row
   *
   * @return TranslationType
   * @throws InvalidArgumentException
   */
  private function mapType( array $row ): TranslationType {
    $type = substr(
      $row['element_type'],
      0,
      (int) strpos( $row['element_type'], '_' )
    );

    if ( $type === 'package' ) {
      $type = TranslationType::PACKAGE;
    } else if ( $type === 'st-batch' ) {
      $type = TranslationType::STRING_BATCH;
    } else {
      $type = TranslationType::POST;
    }

    return new TranslationType( $type );
  }


}
