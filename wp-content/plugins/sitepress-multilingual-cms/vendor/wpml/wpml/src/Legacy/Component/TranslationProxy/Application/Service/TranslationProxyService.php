<?php

namespace WPML\Legacy\Component\TranslationProxy\Application\Service;

use WPML\Core\Component\TranslationProxy\Application\Service\SendTranslationProxyCommitRequestException;
use WPML\Core\Component\TranslationProxy\Application\Service\TranslationProxyServiceInterface;

class TranslationProxyService implements TranslationProxyServiceInterface {

  /** @var \TranslationProxy_Project|false */
  private $legacyTranslationProxyProject;


  public function __construct() {

    $currentService = \TranslationProxy::get_current_service();

    if ( is_wp_error( $currentService ) || $currentService === false ) {
      $this->legacyTranslationProxyProject = false;
    } else {
      $this->legacyTranslationProxyProject = new \TranslationProxy_Project(
        $currentService,
        'xmlrpc',
        \TranslationProxy::get_tp_client()
      );
    }
  }


  /**
   * @return int|bool
   * @throws SendTranslationProxyCommitRequestException
   */
  public function sendCommitRequest() {

    if ( ! $this->legacyTranslationProxyProject ) {
      return false;
    }

    try {
      $result = $this->legacyTranslationProxyProject->commit_batch_job();
      if ( ! $result ) {
        return false;
      }

      $batchJobId = $this->legacyTranslationProxyProject->get_batch_job_id();

      if ( ! is_numeric( $batchJobId ) ) {
        return false;
      }

      $batchJobId = (int) $batchJobId;

      if ( ! $batchJobId ) {
        return false;
      }

      // As done in WPML legacy code, doing the wpml_tm_jobs_notification action.,
      // should process emails and maybe do some other related stuff
      do_action( 'wpml_tm_jobs_notification' );

      // Clean legacy TP basket name and batch after each success batch commit
      \TranslationProxy_Basket::cleanBasket();

      return $batchJobId;
    } catch ( \Throwable $e ) { // generally catch any exception happens on legacy side
      throw new SendTranslationProxyCommitRequestException( $e->getMessage() );
    }
  }


  /**
   * Returns constant defined in sitepress-multilingual-cms/inc/constants.php
   *
   * @return string
   */
  public function getTPUrl(): string {
    /** @phpstan-ignore-next-line  */
    return OTG_TRANSLATION_PROXY_URL;
  }


}
