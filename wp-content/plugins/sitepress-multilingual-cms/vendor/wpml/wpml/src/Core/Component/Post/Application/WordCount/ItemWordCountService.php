<?php

namespace WPML\Core\Component\Post\Application\WordCount;

use WPML\Core\Component\Post\Domain\WordCount\ItemContentCalculator\PackageCalculator;
use WPML\Core\Component\Post\Domain\WordCount\ItemContentCalculator\PostCalculator;
use WPML\Core\Component\Post\Domain\WordCount\ItemContentCalculator\StringCalculator;
use WPML\PHP\Exception\InvalidItemIdException;

class ItemWordCountService {

  /** @var PostCalculator */
  private $postCalculator;

  /** @var PackageCalculator */
  private $packageCalculator;

  /** @var StringCalculator */
  private $stringCalculator;


  public function __construct(
    PostCalculator $postCalculator,
    PackageCalculator $packageCalculator,
    StringCalculator $stringCalculator
  ) {
    $this->postCalculator    = $postCalculator;
    $this->packageCalculator = $packageCalculator;
    $this->stringCalculator  = $stringCalculator;
  }


  /**
   * @param int  $postId
   * @param bool $forceRecalculate
   *
   * @return int
   * @throws InvalidItemIdException
   *
   */
  public function calculatePost( int $postId, bool $forceRecalculate = false ): int {
    return $forceRecalculate ?
      $this->postCalculator->calculate( $postId ) :
      $this->postCalculator->getWordCount( $postId );
  }


  /**
   * @param int $packageId
   *
   * @return int
   * @throws InvalidItemIdException
   *
   */
  public function calculatePackage( int $packageId ): int {
    return $this->packageCalculator->calculate( $packageId );
  }


  /**
   * @param int $stringId
   *
   * @return int
   * @throws InvalidItemIdException
   *
   */
  public function calculateString( int $stringId ): int {
    return $this->stringCalculator->calculate( $stringId );
  }


}
