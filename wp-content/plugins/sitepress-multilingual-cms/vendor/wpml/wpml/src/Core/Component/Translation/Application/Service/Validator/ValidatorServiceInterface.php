<?php

namespace WPML\Core\Component\Translation\Application\Service\Validator;

use WPML\Core\Component\Translation\Application\Service\Dto\SendToTranslationDto;
use WPML\Core\Component\Translation\Application\Service\Validator\Dto\ValidationResultDto;

interface ValidatorServiceInterface {


  public function validate( SendToTranslationDto $sendToTranslationDto ): ValidationResultDto;


  public function getType(): string;


}
