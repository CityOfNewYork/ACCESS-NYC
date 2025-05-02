<?php

namespace WPML\Core\Component\Translation\Application\Service\TranslationService\BatchBuilder;

use WPML\Core\Component\Translation\Application\Service\Dto\SendToTranslationDto;
use WPML\Core\Component\Translation\Domain\TranslationBatch\DuplicationBatch;
use WPML\Core\Component\Translation\Domain\TranslationBatch\TranslationBatch;
use WPML\Core\Component\Translation\Domain\TranslationBatch\Validator\IgnoredElement;
use WPML\PHP\Exception\InvalidArgumentException;

interface BatchBuilderInterface {


  /**
   * @param SendToTranslationDto $sendToTranslationDto
   *
   * @return array{0: TranslationBatch|null, 1: DuplicationBatch|null, 2: IgnoredElement[]}
   * @throws InvalidArgumentException
   */
  public function build( SendToTranslationDto $sendToTranslationDto ): array;


}
