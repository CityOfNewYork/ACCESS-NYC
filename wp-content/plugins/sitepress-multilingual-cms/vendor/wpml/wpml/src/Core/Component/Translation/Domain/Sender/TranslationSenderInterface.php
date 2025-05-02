<?php

namespace WPML\Core\Component\Translation\Domain\Sender;

use WPML\Core\Component\Translation\Domain\Translation;
use WPML\Core\Component\Translation\Domain\TranslationBatch\TranslationBatch;

interface TranslationSenderInterface {


  /**
   * @param TranslationBatch $batch
   *
   * @return Translation[]
   * @throws SendBatchException
   */
  public function send( TranslationBatch $batch ): array;


  /**
   * @param TranslationBatch $batch
   *
   * @return void
   */
  public function rollback( TranslationBatch $batch );


}
