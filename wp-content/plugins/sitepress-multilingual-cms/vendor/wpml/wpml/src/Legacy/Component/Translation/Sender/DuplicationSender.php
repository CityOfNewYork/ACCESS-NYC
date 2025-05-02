<?php

namespace WPML\Legacy\Component\Translation\Sender;

use WPML\Core\Component\Translation\Application\Query\TranslationQueryInterface;
use WPML\Core\Component\Translation\Domain\Sender\DuplicationSenderInterface;
use WPML\Core\Component\Translation\Domain\Translation;
use WPML\Core\Component\Translation\Domain\TranslationBatch\DuplicationBatch;

class DuplicationSender implements DuplicationSenderInterface {

  /**
   * It's legacy constant defined also in \WPML\TM\API\Jobs
   */
  const SEND_VIA_DASHBOARD = 6;

  /** @var  \TranslationManagement $legacyTranslationManagement */
  private $legacyTranslationManagement;

  /** @var DuplicationBatchMapper $duplicationBatchMapper */
  private $duplicationBatchMapper;

  /** @var TranslationQueryInterface */
  private $translationQuery;


  public function __construct(
    DuplicationBatchMapper $duplicationBatchMapper,
    TranslationQueryInterface $translationQuery
  ) {
    $this->legacyTranslationManagement = \wpml_load_core_tm();
    $this->duplicationBatchMapper      = $duplicationBatchMapper;
    $this->translationQuery            = $translationQuery;
  }


  /**
   * @param DuplicationBatch $batch
   *
   * @return Translation[]
   */
  public function send( DuplicationBatch $batch ): array {
    $legacyBatch = $this->duplicationBatchMapper->map( $batch );

    // Only posts can be duplicated. Therefore, other types are not supported.
    do_action(
      'wpml_tm_send_post_jobs',
      $legacyBatch,
      'post',
      self::SEND_VIA_DASHBOARD
    );

    // Even though, the method is called `get_sent_job_ids`, but it returns translated post ids in this case.
    $translatedPostIds = $this->legacyTranslationManagement->get_sent_job_ids();
    if ( ! is_array( $translatedPostIds ) ) {
      return [];
    }

    if ( $translatedPostIds ) {
      return $this->translationQuery->getManyByTranslatedElementIds( $translatedPostIds );
    }

    return [];
  }


}
