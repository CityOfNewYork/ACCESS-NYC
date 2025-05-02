<?php

namespace WPML\Infrastructure\WordPress\Component\Item\Domain\WordCount\ItemContentCalculator;

use WPML\Core\Component\Post\Domain\WordCount\ItemContentCalculator\PostContentFilterInterface;

class PostContentFilter implements PostContentFilterInterface {


  public function getContent( string $content, int $postId ): string {
    try {
      /** @var string|mixed $filtered */
      $filtered = \apply_filters( 'wpml_words_count_post_content', $content, $postId );

      return is_string( $filtered ) ? $filtered : $content;
    } catch ( \Throwable $e ) {
      return $content;
    }
  }


  public function getAdditionalContent( string $initial, int $postId ): string {
    try {
      /** @var string|mixed $filtered */
      $filtered = \apply_filters( 'wpml_words_count_post_additional_content', $initial, $postId );

      return is_string( $filtered ) ? $filtered : $initial;
    } catch ( \Throwable $e ) {
      return $initial;
    }
  }


}
