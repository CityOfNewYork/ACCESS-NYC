<?php

namespace WPML\Core\Component\Post\Application\Query\Dto;

class PostTaxonomyDto {

  /** @var string */
  private $id;

  /** @var string */
  private $name;

  /**
   *
   * List of ItemSectionId IDs that are associated with this taxonomy.
   * For example, a Taxonomy with itemSections [ 'post', 'page' ] is associated with posts and pages.
   *
   * @var array<string>
   */
  private $postTypeIds;


  /**
   * @param string $id
   * @param string $name
   * @param array<string> $postTypeIds
   */
  public function __construct(
    string $id,
    string $name,
    array $postTypeIds = []
  ) {
    $this->id       = $id;
    $this->name     = $name;
    $this->postTypeIds    = $postTypeIds;
  }


  public function getId(): string {
    return $this->id;
  }


  public function getName(): string {
    return $this->name;
  }


  /**
   * @return string[]
   */
  public function getPostTypeIds(): array {
    return $this->postTypeIds;
  }


}
