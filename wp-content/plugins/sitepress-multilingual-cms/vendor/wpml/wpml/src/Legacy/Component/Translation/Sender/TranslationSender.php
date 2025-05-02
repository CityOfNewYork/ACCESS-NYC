<?php

namespace WPML\Legacy\Component\Translation\Sender;

use WPML\Core\Component\Translation\Application\Query\TranslationQueryInterface;
use WPML\Core\Component\Translation\Application\Service\Dto\SendToTranslationExtraInformationDto;
use WPML\Core\Component\Translation\Domain\Sender\SendBatchException;
use WPML\Core\Component\Translation\Domain\Sender\TranslationSenderInterface;
use WPML\Core\Component\Translation\Domain\Translation;
use WPML\Core\Component\Translation\Domain\TranslationBatch\TranslationBatch;
use WPML\Core\Component\Translation\Domain\TranslationMethod\TranslationServiceMethod;
use WPML\Legacy\Component\Translation\Sender\ErrorMapper\ErrorMapper;

/**
 * @phpstan-import-type TranslationServiceExtraFieldsArray from SendToTranslationExtraInformationDto
 *
 * @phpstan-type TpBatchInfoArray array{
 * batchName: string,
 * deadline: \DateTime|null,
 * extraFields: TranslationServiceExtraFieldsArray|null
 * }
 */
class TranslationSender implements TranslationSenderInterface {

  /**
   * It's legacy constant defined also in \WPML\TM\API\Jobs
   */
  const SEND_VIA_DASHBOARD = 6;

  /** @var  \TranslationManagement $legacyTranslationManagement */
  private $legacyTranslationManagement;

  /** @var TranslationBatchMapper $translationBatchMapper */
  private $translationBatchMapper;

  /** @var TranslationQueryInterface */
  private $translationQuery;

  /** @var ErrorMapper */
  private $errorMapper;


  public function __construct(
    TranslationBatchMapper $translationBatchMapper,
    TranslationQueryInterface $translationQuery,
    ErrorMapper $errorMapper
  ) {
    $this->legacyTranslationManagement = \wpml_load_core_tm();
    $this->translationBatchMapper      = $translationBatchMapper;
    $this->translationQuery            = $translationQuery;
    $this->errorMapper                 = $errorMapper;
  }


  /**
   * @param TranslationBatch $batch
   *
   * @return Translation[]
   * @throws SendBatchException
   */
  public function send( TranslationBatch $batch ): array {

    // Here we call the setTargetLanguagesInTranslationProxy function explicitly.,
    // because in legacy code it's only called when the WPML_Translation_Proxy_Basket_Networking::send_all_jobs().,
    // is invoked, and what we did here is that we extracted the logic to send items to translation.,
    // and rollback the failed batch from legacy code in our new WPML code, so.,
    // just calling 'wpml_tm_send_' . $type . '_jobs' isn't enough to do both things.
    $this->setTargetLanguagesInTranslationProxy( $batch );

    $translationProxyBatchInfo = null;

    $batchHasJobsForTranslationProxy = $this->getTargetLanguagesForTranslationProxy( $batch );

    if ( $batchHasJobsForTranslationProxy ) {
      /** @var TpBatchInfoArray $translationProxyBatchInfo */
      $translationProxyBatchInfo = [
        'batchName'   => $batch->getBatchName(),
        'deadline'    => $batch->getDeadline(),
        'extraFields' => $batch->getTranslationServiceExtraFields()
      ];
    }

    // We may have two batches - one for automatic and one for manual translations.
    // Such division is required because legacy API doesn't support both automatic and manual translations in one batch.
    $legacyBatches = $this->translationBatchMapper->map( $batch, $translationProxyBatchInfo );

    $jobIds = [];

    foreach ( $legacyBatches as $legacyBatch ) {
      foreach ( $this->getElementTypes() as $type ) {
        do_action(
          'wpml_tm_send_' . $type . '_jobs',
          $legacyBatch,
          $type,
          self::SEND_VIA_DASHBOARD
        );
      }

      $errors = $this->legacyTranslationManagement->messages_by_type( 'error' );
      if ( is_array( $errors ) ) {
        $errorMessage = $this->errorMapper->map( $errors );
        throw new SendBatchException( $errorMessage );
      }

      do_action( 'wpml_tm_jobs_notification' );

      $jobIdsOfLegacy = $this->legacyTranslationManagement->get_sent_job_ids();
      if ( is_array( $jobIdsOfLegacy ) ) {
        $jobIds = array_merge( $jobIds, $jobIdsOfLegacy );
      }
    }

    if ( $jobIds ) {
      return $this->translationQuery->getManyByJobIds( $jobIds );
    }

    return [];
  }


  /**
   * @param TranslationBatch $batch
   *
   * @return string[]
   */
  private function getTargetLanguagesForTranslationProxy( TranslationBatch $batch ): array {
    $targetLanguages = [];

    foreach ( $batch->getTargetLanguages() as $targetLanguage ) {
      if ( $targetLanguage->getMethod() instanceof TranslationServiceMethod ) {
        $targetLanguages[] = $targetLanguage->getLanguageCode();
      }
    }

    return array_unique( $targetLanguages );
  }


  /**
   * @param TranslationBatch $batch
   *
   * @return void
   */
  private function setTargetLanguagesInTranslationProxy( TranslationBatch $batch ) {
    $targetLanguages = $this->getTargetLanguagesForTranslationProxy( $batch );
    if ( $targetLanguages ) {
      \TranslationProxy_Basket::set_remote_target_languages( $targetLanguages );
    }

  }


  /**
   * @return string[] string|post|package
   */
  private function getElementTypes(): array {
    /**
     * @var array<string, string> $types
     */
    $types = \apply_filters(
      'wpml_tm_basket_items_types',
      [
        'st-batch' => 'core',
        'post'     => 'core',
        'package'  => 'custom',
      ]
    );

    return array_keys( $types );
  }


  public function rollback( TranslationBatch $batch ) {
    \WPML\TM\API\Batch::rollback( $batch->getBatchName() );
  }


}
