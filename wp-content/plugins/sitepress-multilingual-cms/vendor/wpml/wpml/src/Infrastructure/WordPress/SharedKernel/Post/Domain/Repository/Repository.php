<?php
// phpcs:ignoreFile Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
namespace WPML\Infrastructure\WordPress\SharedKernel\Post\Domain\Repository;


use WPML\Core\SharedKernel\Component\Post\Domain\Repository\RepositoryInterface;
use WPML\Core\SharedKernel\Component\Post\Domain\Post;
use WPML\PHP\Exception\InvalidItemIdException;

class Repository implements RepositoryInterface {


  public function getById( int $postId ): Post {
    /** @var \WP_Post|null $post */
    $post = \get_post( $postId );
    if ( ! $post ) {
      throw new InvalidItemIdException( sprintf( 'Post with ID %d not found', $postId ) );
    }

    return new Post(
      $post->ID,
      $post->post_status,
      $post->post_type,
      $post->post_title,
      $post->post_content,
      $post->post_excerpt
    );

  }


}
