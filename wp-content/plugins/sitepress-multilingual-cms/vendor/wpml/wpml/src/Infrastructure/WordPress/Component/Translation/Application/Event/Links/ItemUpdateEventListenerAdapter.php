<?php

namespace WPML\Infrastructure\WordPress\Component\Translation\Application\Event\Links;

use WPML\Core\Component\Translation\Application\Event\Links\ItemUpdateListener;
use WPML\Core\Component\Translation\Domain\Links\Item;
use WPML\Infrastructure\WordPress\Component\Translation\Domain\Links\Repository;
use WPML\Infrastructure\WordPress\SharedKernel\Post\Domain\PublicationStatusDefinitions;
use WPML\PHP\Exception\InvalidTypeException;
use WPML\WordPress\Term;
use WP_Post;

class ItemUpdateEventListenerAdapter {

  /** @var ItemUpdateListener */
  private $itemUpdateListener;

  /** @var PublicationStatusDefinitions */
  private $publicationStatusDefinitions;

  /** @var Repository */
  private $repository;

  /** @var array{post: ?WP_Post, statusBefore: string, nameBefore:string}[] */
  private $updatedPosts = [];

  /** @var array<int,string> */
  private $updatedTerms = [];


  public function __construct(
    ItemUpdateListener $itemUpdateListener,
    PublicationStatusDefinitions $publicationStatusDefinitions,
    Repository $repository
  ) {
    $this->itemUpdateListener = $itemUpdateListener;
    $this->publicationStatusDefinitions = $publicationStatusDefinitions;
    $this->repository = $repository;
  }


  /** @return void */
  public function onPostUpdate( WP_Post $postBeforeSave ) {
    $postId = $postBeforeSave->ID;
    if ( array_key_exists( $postId, $this->updatedPosts ) ) {
      // The post status is collected here to determine if a post was published
      // for the first time - in that case the status here is empty/"draft" and
      // "publish" on the later save hook (see onPostSave).
      // Same for the name to check if the link has changed.
      //
      // But some plugins (Elementor) trigger an additional update and in that
      // update the post status or name is already the final state. So
      // onPostSave wouldn't determine a change in status or name.
      //
      // Solution: Keep the first state and ignore further updates in the request.
      return;
    }
    $this->setDefaultsForPost( $postId );
    // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    $this->updatedPosts[ $postId ]['statusBefore'] = $postBeforeSave->post_status;
    // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    $this->updatedPosts[ $postId ]['nameBefore'] = $postBeforeSave->post_name;
  }


  /** @return void */
  public function onPostSave( int $postId, WP_Post $wpPost ) {
    $this->setDefaultsForPost( $postId );
    $this->updatedPosts[ $postId ]['post'] = $wpPost;
  }


  /** @return void */
  private function setDefaultsForPost( int $postId ) {
    if ( array_key_exists( $postId, $this->updatedPosts ) ) {
      return;
    }
    $this->updatedPosts[ $postId ] = [
      'post' => null,
      'statusBefore' => '',
      'nameBefore' => '',
    ];
  }


  /** @return void */
  public function onTermCreation( int $termId ) {
    if ( array_key_exists( $termId, $this->updatedTerms ) ) {
      return;
    }

    // No previous slug.
    $this->updatedTerms[ $termId ] = '';
  }


  /** @return void */
  public function beforeTermUpdate( int $termId ) {
    if ( array_key_exists( $termId, $this->updatedTerms ) ) {
      return;
    }

    $term = Term::get( $termId, '', 'ARRAY_A' );
    if ( is_array( $term ) ) {
      $this->updatedTerms[ $termId ] = (string) $term['slug'];
    }
  }


  /**
   * Do the actual work on shutdown to prevent multiple adjustment calls.
   * So even if multiple posts were translated in one request,
   * we only adjust the links once.
   *
   * @return void
   */
  public function onShutdown() {
    if ( ! $this->updatedPosts && ! $this->updatedTerms ) {
      return;
    }

    foreach ( $this->updatedPosts as $post ) {
      try {
        if ( $post['post'] === null ) {
          continue; // Shouldn't happen, but just in case.
        }

        $item = $this->getItemByPost(
          $post['post'],
          $post['statusBefore'],
          $post['nameBefore']
        );

        $this->itemUpdateListener->onItemSave( $item );
      } catch ( InvalidTypeException $e ) {
        continue;
      }
    }

    foreach ( $this->updatedTerms as $termId => $slugBefore ) {
      try {
        $item = $this->getItemByTerm( $termId, $slugBefore );

        if ( $item === null ) {
          continue;
        }
        $this->itemUpdateListener->onItemSave( $item );
      } catch ( InvalidTypeException $e ) {
        continue;
      }
    }
  }


  /**
  * @throws InvalidTypeException
  */
  private function getItemByPost(
    WP_Post $wpPost,
    string $statusBefore,
    string $nameBefore
  ): Item {
      $item = $this->repository->get( $wpPost->ID, Repository::TYPE_POST );
    if (
        $this->publicationStatusDefinitions->gotPublished(
          // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
          $wpPost->post_status,
          $statusBefore
        )
      ) {
      $item->markAsGotPublished();
    }

    // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    if ( $wpPost->post_name !== $nameBefore ) {
      $item->markLinkAsChanged( $nameBefore );
    }

      return $item;
  }


  /**
  * @throws InvalidTypeException
  * @return ?Item
  */
  private function getItemByTerm( int $termId, string $slugBefore ) {
    $term = Term::get( $termId, '', 'ARRAY_A' );

    if ( ! is_array( $term ) ) {
        return null;
    }

    $item = $this->repository->get( $termId, Repository::TYPE_TERM );
    $item->markAsPublished();

    if ( $term['slug'] !== $slugBefore ) {
      $item->markLinkAsChanged( $slugBefore );
    }

    return $item;
  }


}
