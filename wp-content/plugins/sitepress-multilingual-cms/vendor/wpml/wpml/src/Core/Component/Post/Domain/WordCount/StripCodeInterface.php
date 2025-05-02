<?php

namespace WPML\Core\Component\Post\Domain\WordCount;

interface StripCodeInterface {


  /**
   * It filters out the code part like tags or WP shortcodes from the content.
   *
   * @param string $content
   *
   * @return string
   */
  public function strip( string $content ): string;


}
