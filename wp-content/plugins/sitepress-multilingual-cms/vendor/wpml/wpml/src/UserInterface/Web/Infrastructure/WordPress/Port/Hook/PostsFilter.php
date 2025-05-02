<?php

namespace WPML\UserInterface\Web\Infrastructure\WordPress\Port\Hook;

use WPML\Legacy\Component\Post\Application\TranslationEditorMode;
use WPML\UserInterface\Web\Core\Component\Dashboard\Application\Endpoint\GetPosts\PostsFilterInterface;

/**
 * @phpstan-import-type Post from \WPML\UserInterface\Web\Core\Component\Dashboard\Application\Endpoint\GetPosts\GetPostsController
 * @phpstan-import-type SearchCriteriaRaw from \WPML\UserInterface\Web\Core\Component\Dashboard\Application\Endpoint\GetPosts\GetPostsController
 */
class PostsFilter implements PostsFilterInterface {

  /**
   * @var TranslationEditorMode
   */
  private $translationEditorMode;


  public function __construct( TranslationEditorMode $translationEditorMode ) {
    $this->translationEditorMode = $translationEditorMode;
  }


  /**
   * @param Post[]            $posts
   * @param SearchCriteriaRaw $searchCriteria
   *
   * @return Post[]
   */
  public function filter( array $posts, array $searchCriteria ): array {
    return apply_filters( 'wpml_tm_dashboard_posts', $this->addIsBlockedProp( $posts ), $searchCriteria );
  }


  /**
   * @param Post[] $posts
   *
   * @return Post[]
   */
  private function addIsBlockedProp( array $posts ): array {
    $postIds = array_map(
      function( $post ) {
        return $post['id'];
      },
      $posts
    );

    $blockedPosts = $this->translationEditorMode->getBlockedPosts( $postIds );

    foreach ( $posts as &$post ) {
      $post['isBlocked'] = isset( $blockedPosts[ $post['id'] ] );
    }

    return $posts;
  }


  public function filterViewLink( string $viewLink, int $postId, string $postType, string $languageCode ): string {
    return self::filterLink( 'wpml_document_view_item_link', 'View', $viewLink, $postId, $postType, $languageCode );
  }


  public function filterEditLink( string $editLink, int $postId, string $postType, string $languageCode ): string {
    return self::filterLink( 'wpml_document_edit_item_link', 'Edit', $editLink, $postId, $postType, $languageCode );
  }


  private static function filterLink(
    string $hook,
    string $oldLabel,
    string $link,
    int $postId,
    string $postType,
    string $languageCode
  ): string {
    $maybeExtractUrlFromLinkTag = function( string $linkOrUrl ): string {
      $htmlLinkPattern = '/<a\s+(?:[^>]*?\s+)?href="([^"]*)"/i';
      preg_match( $htmlLinkPattern, $linkOrUrl, $matches );

      return $matches[1] ?? $linkOrUrl;
    };

    $legacyObject = (object) [
      'ID'              => $postId,
      'original_doc_id' => $postId,
      'language_code'   => $languageCode,
    ];

    // The filter 'wpml_document_edit/view_item_link' passes the link with an anchor tag.
    // We need to make sure the linkOrUrl really has the anchor tag for backward compatibility.
    // Another importance: use double quotes for the href attribute.
    if ( strpos( $link, '<a' ) === false ) {
      $link = '<a href="' . $link . '">' . $oldLabel . '</a>';
    }

    return $maybeExtractUrlFromLinkTag(
      (string) apply_filters( $hook, $link, $oldLabel, $legacyObject, 'post', $postType )
    );
  }


}
