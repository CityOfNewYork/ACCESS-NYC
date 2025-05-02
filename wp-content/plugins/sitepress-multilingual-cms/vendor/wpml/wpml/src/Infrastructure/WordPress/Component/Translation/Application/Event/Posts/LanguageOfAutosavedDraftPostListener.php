<?php

namespace WPML\Infrastructure\WordPress\Component\Translation\Application\Event\Posts;

use WPML\Core\Component\Translation\Application\Service\LanguageService;
use WPML\Core\Port\Event\EventListenerInterface;
use WPML\Core\SharedKernel\Component\Language\Application\Query\LanguagesQueryInterface;
use WPML\PHP\Exception\InvalidArgumentException;
use WP_Post;

class LanguageOfAutosavedDraftPostListener implements EventListenerInterface {

  /** @var LanguageService */
  private $languageService;

  /** @var LanguagesQueryInterface */
  private $languageQuery;


  public function __construct( LanguageService $languageInfoService, LanguagesQueryInterface $languageQuery ) {
    $this->languageService = $languageInfoService;
    $this->languageQuery   = $languageQuery;
  }


  /**
   * @param int          $postId
   * @param WP_Post      $post
   * @param bool         $update
   * @param WP_Post|null $postBefore
   *
   * @return void
   */
  public function setLanguage( $postId, $post, $update, $postBefore ) {
    if ( ! $this->isDoingAutosave() ) {
      return;
    }

    // phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    if ( ! $update || $post->post_status !== 'draft' || ! $postBefore || $postBefore->post_status !== 'auto-draft' ) {
      return;
    }

    $currentLanguage = $this->languageQuery->getCurrentLanguageCode();

    $langData = $this->maybeGetSourceLanguageCodeAndTridOfOriginalPostIfCurrentPostIsATranslation();

    try {
      $this->languageService->setLanguageOfElement(
        $postId,
        'post',
        $post->post_type,
        $currentLanguage,
        $langData['sourceLang'],
        $langData['trid']
      );
      // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch -- Intentionally ignoring invalid data
    } catch ( InvalidArgumentException $e ) {
      // Do nothing
    }
    // phpcs:enable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

  }


  /**
   * @return array{sourceLang:string|null, trid:int|null}
   */
  private function maybeGetSourceLanguageCodeAndTridOfOriginalPostIfCurrentPostIsATranslation() {
    $sourceLang = null;
    $trid       = null;

    if ( isset( $_SERVER['HTTP_REFERER'] ) ) {
      $referer       = $_SERVER['HTTP_REFERER'];
      $urlComponents = wp_parse_url( $referer );

      if ( isset( $urlComponents['query'] ) ) {
        parse_str( $urlComponents['query'], $queryParams );

        if (
          isset( $queryParams['source_lang'] ) &&
          is_string( $queryParams['source_lang'] ) &&
          isset( $queryParams['trid'] ) &&
          is_numeric( $queryParams['trid'] )
        ) {
          $sourceLang = sanitize_text_field( $queryParams['source_lang'] );
          $trid       = (int) sanitize_text_field( (string) $queryParams['trid'] );
        }
      }
    }

    return [
      'sourceLang' => $sourceLang,
      'trid'       => $trid
    ];
  }


  protected function isDoingAutosave(): bool {
    /** @psalm-suppress RedundantCondition */
    return defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE;
  }


}
