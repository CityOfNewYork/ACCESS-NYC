<?php

namespace WPML\Core\Component\Post\Domain\WordCount\ItemContentCalculator;

use WPML\Core\SharedKernel\Component\String\Domain\Repository\RepositoryInterface as StringRepositoryInterface;
use WPML\Core\SharedKernel\Component\StringPackage\Domain\Repository\RepositoryInterface as PackageRepositoryInterface;
use WPML\PHP\Exception\InvalidArgumentException;
use WPML\PHP\Exception\InvalidItemIdException;
use function WPML\PHP\Logger\error;

class PackageCalculator {

  /** @var StringRepositoryInterface */
  private $stringRepository;

  /** @var PackageRepositoryInterface */
  private $packageRepository;

  /** @var StringCalculator */
  private $stringCalculator;


  public function __construct(
    StringRepositoryInterface $stringRepository,
    PackageRepositoryInterface $packageRepository,
    StringCalculator $stringCalculator
  ) {
    $this->stringRepository  = $stringRepository;
    $this->packageRepository = $packageRepository;
    $this->stringCalculator  = $stringCalculator;
  }


  /**
   * @param int $itemId
   *
   * @return int
   * @throws InvalidItemIdException
   */
  public function calculate( int $itemId ): int {
    $strings = $this->stringRepository->getBelongingToPackage( $itemId );

    $wordCount = 0;
    foreach ( $strings as $string ) {
      $wordCount += $string->getWordCount() ?: $this->stringCalculator->calculate( $string->getId() );
    }

    try {
      $this->packageRepository->updateField( $itemId, 'word_count', $wordCount );
    } catch ( InvalidArgumentException $e ) {
      error( sprintf( 'Failed to update word count for package %d', $itemId ) );
    }

    return $wordCount;
  }


}
