<?php

namespace Shortcode;

use Timber;

class Accordion {
  public function __construct() {
    add_shortcode($this->shortCode, [$this, 'shortCode']);
  }

  public function shortCode() {

  }
}