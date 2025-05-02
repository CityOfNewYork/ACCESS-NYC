<?php

namespace WPML\Core\Component\Post\Domain\WordCount\ItemContentCalculator;

interface PostContentFilterInterface {


  public function getContent( string $content, int $postId ): string;


  public function getAdditionalContent( string $initial, int $postId ): string;


}
