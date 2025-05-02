<?php

namespace WPML\UserInterface\Web\Core\Component\Dashboard\Application\ViewModel;

use WPML\Core\SharedKernel\Component\Post\Application\Query\Dto\PostTypeDto;
use WPML\PHP\ConstructableFromArrayTrait;

/**
 * @phpstan-type ItemSectionData array{
 *   id: string,
 *   title: string,
 *   singular: string,
 *   plural: string,
 *   kind: array{
 *    id: string,
 *    hierarchical?: bool,
 *    type?: string
 *   }
 * }
 */
final class ItemSection {
  /** @use ConstructableFromArrayTrait<ItemSection> */
  use ConstructableFromArrayTrait;

  /** @var string */
  private $id;

  /** @var string */
  private $title;

  /** @var string */
  private $singular;

  /** @var string */
  private $plural;

  /** @var bool */
  private $isDisplayAsTranslated = false;

  /** @var string */
  private $kindId;

  /** @var bool|null */
  private $kindHierarchical;

  /** @var string|null */
  private $kindType;

  /** @var array<string, string> */
  private $defaultFilters;


  /**
   * @param string                $id
   * @param string                $title
   * @param string                $singular
   * @param string                $plural
   * @param string                $kindId
   * @param bool                  $kindHierarchical
   * @param string                $kindType
   * @param array<string, string> $defaultFilters
   */
  public function __construct(
    string $id,
    string $title,
    string $singular,
    string $plural,
    string $kindId,
    bool $kindHierarchical,
    string $kindType,
    array $defaultFilters = []
  ) {
    $this->id               = $id;
    $this->title            = $title;
    $this->singular         = $singular;
    $this->plural           = $plural;
    $this->kindId           = $kindId;
    $this->kindHierarchical = $kindHierarchical;
    $this->kindType         = $kindType;
    $this->defaultFilters   = $defaultFilters;
  }


  /**
   * @return string
   */
  public function getId() {
    return $this->id;
  }


  public function isDisplayAsTranslated(): bool {
    return $this->isDisplayAsTranslated;
  }


  /**
   * @param bool $isDisplayAsTranslated
   *
   * @return void
   */
  public function setIsDisplayAsTranslated( bool $isDisplayAsTranslated ) {
    $this->isDisplayAsTranslated = $isDisplayAsTranslated;
  }


  /**
   * @return ItemSectionData
   */
  public function toArray() {
    $kind = [ 'id' => $this->kindId ];

    if ( $this->kindHierarchical !== null ) {
      $kind['hierarchical'] = $this->kindHierarchical;
    }

    if ( $this->kindType !== null ) {
      $kind['type'] = $this->kindType;
    }

    $kind['defaultFilters'] = $this->defaultFilters;

    return [
      'id'                    => $this->id,
      'title'                 => $this->title,
      'singular'              => $this->singular,
      'plural'                => $this->plural,
      'kind'                  => $kind,
      'isDisplayAsTranslated' => $this->isDisplayAsTranslated,
    ];
  }


  /**
   * @param PostTypeDto $postTypeDto
   *
   * @return ItemSection
   */
  public static function createFromPostType( PostTypeDto $postTypeDto ) {
    $kindId = 'post';

    $result = new self(
      sprintf( '%s/%s', $kindId, $postTypeDto->getId() ),
      $postTypeDto->getTitle(),
      $postTypeDto->getSingular(),
      $postTypeDto->getPlural(),
      $kindId,
      $postTypeDto->isHierarchical(),
      $postTypeDto->getId()
    );

    $result->setIsDisplayAsTranslated( $postTypeDto->isDisplayAsTranslated() );

    return $result;
  }


}
