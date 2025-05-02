<?php

namespace WPML\UserInterface\Web\Core\Component\Dashboard\Application\Endpoint\GetPosts;

use WPML\Core\Component\Post\Application\WordCount\ItemWordCountService;
use WPML\PHP\Exception\InvalidItemIdException;
use function WPML\PHP\Logger\notice;

/**
 * @phpstan-type PostTranslationStatus array{
 *     id: int,
 *     language: string,
 *     status: string,
 *     createdAt: string,
 *     updatedAt: string
 * }
 *
 * @phpstan-type PostData array{
 *     id: int,
 *     title: string,
 *     status: string,
 *     createdAt: string,
 *     translations: PostTranslationStatus[],
 *     wordCount: int,
 *     translatorNote: ?string,
 *     viewLink: string
 * }
 */
class WordCountDecoratorController implements GetPostControllerInterface {

  /** @var GetPostControllerInterface */
  private $innerController;

  /** @var ItemWordCountService */
  private $itemWordCountService;


  public function __construct(
    GetPostControllerInterface $innerController,
    ItemWordCountService $itemWordCountService
  ) {
    $this->innerController      = $innerController;
    $this->itemWordCountService = $itemWordCountService;
  }


  public function handle( $requestData = null ): array {
    /** @var PostData[] $posts */
    $posts = $this->innerController->handle( $requestData );

    return array_map(
      function ( array $post ) {
        return $this->maybeCalculateWords( $post );
      },
      $posts
    );
  }


  /**
   * @param PostData $post
   *
   * @return PostData
   */
  private function maybeCalculateWords( array $post ): array {
    if ( ! $post['wordCount'] ) {
      try {
        $wordCount         = $this->itemWordCountService->calculatePost( $post['id'], true );
        $post['wordCount'] = $wordCount;
      } catch ( InvalidItemIdException $e ) {
        notice( 'Failed to calculate word count for post ' . $post['id'] );
      }
    }

    return $post;
  }


}
