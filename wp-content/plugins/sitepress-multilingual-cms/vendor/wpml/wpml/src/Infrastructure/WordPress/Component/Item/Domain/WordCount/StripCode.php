<?php

namespace WPML\Infrastructure\WordPress\Component\Item\Domain\WordCount;

use WPML\Core\Component\Post\Domain\WordCount\StripCodeInterface;

class StripCode implements StripCodeInterface {


  public function strip( string $content ): string {
    $content = htmlspecialchars_decode( $content );
    $content = $this->stripShortcodes( $content );
    $content = \wp_strip_all_tags( $content );
    $content = $this->stripEmailsAndSpaces( $content );

    return $content;
  }


  private function stripShortcodes( string $content ): string {
    // Pattern to match the opening and closing shortcode tags, preserving inner content
    $pattern = '/\[\/?([a-zA-Z0-9_-]+)[^\]]*\](?:(?!\[\/\1\]).)*?(?=\[\/\1\]|\Z)/s';

    // Use preg_replace_callback to remove the tags but keep the inner content
    $result = preg_replace_callback(
      $pattern,
      /**
       * @param string[] $matches
       */
      function ( $matches ): string {
        // Remove only the opening and closing shortcode tags, keep the content between
        return preg_replace( '/\[(\/?)([a-zA-Z0-9_-]+)[^\]]*\]/', '', $matches[0] ) ?: '';
      },
      $content
    );

    return is_string( $result ) ? $result : $content;
  }


  private function stripEmailsAndSpaces( string $content ): string {
    $result = preg_replace(
      [
        '/[^@\s]*@[^@\s]*\.[^@\s]*/', // Emails.
        '/[0-9\t\n\r\s]+/', // Spaces.
      ],
      '',
      $content
    );

    return is_string( $result ) ? $result : $content;
  }


}
