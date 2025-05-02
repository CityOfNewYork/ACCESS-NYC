<?php

namespace WPML\Core\Component\Translation\Domain\Sender;

use WPML\Core\Component\Translation\Domain\Translation;
use WPML\Core\Component\Translation\Domain\TranslationBatch\DuplicationBatch;

interface DuplicationSenderInterface {


  /**
   * @param DuplicationBatch $batch
   *
   * @return Translation[]
   */
  public function send( DuplicationBatch $batch ): array;


}
