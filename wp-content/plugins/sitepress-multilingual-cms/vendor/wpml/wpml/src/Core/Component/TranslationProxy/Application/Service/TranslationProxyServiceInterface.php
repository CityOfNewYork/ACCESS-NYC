<?php

namespace WPML\Core\Component\TranslationProxy\Application\Service;

interface TranslationProxyServiceInterface {


  /**
   * @return int|bool
   * @throws SendTranslationProxyCommitRequestException
   */
  public function sendCommitRequest();


  public function getTPUrl(): string;


}
