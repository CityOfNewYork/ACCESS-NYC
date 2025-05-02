<?php

namespace WPML\WordPress;

use WPML\PHP\Boolean;

class Term {


  /**
   * This method is a wrapper for the WordPress `get_term` function. It just
   * disables WPML's term adjustment feature, which automatically adjusts the
   * requested term to the current language term (if available).
   *
   * @psalm-suppress HookNotFound Legacy hook to disable term adjustment.
   * @return mixed
   */
  public static function get(
    int $id,
    string $taxonomy = '',
    string $output = 'OBJECT'
  ) {
    // Disable legacy filtering of the term.
    add_filter( 'wpml_disable_term_adjust_id', [ Boolean::class, 'true' ] );
    $item = get_term( $id, $taxonomy, $output );
    // Re-enable the term adjustment.
    remove_filter( 'wpml_disable_term_adjust_id', [ Boolean::class, 'true' ] );

    return $item;
  }


}
