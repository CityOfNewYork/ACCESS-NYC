<?php

namespace WPML\Legacy\Component\Post\Application\Query;

use WPML\Core\SharedKernel\Component\Post\Application\Hook\PostTypeFilterInterface;
use WPML\Core\SharedKernel\Component\Post\Application\Query\Dto\PostTypeDto;
use WPML\Core\SharedKernel\Component\Post\Application\Query\TranslatableTypesQueryInterface;

class TranslatableTypesQuery implements TranslatableTypesQueryInterface {

  /** @var \SitePress */
  private $sitepress;

  /** @var PostTypeFilterInterface|null */
  private $postTypeFilter;


  public function __construct(
    \SitePress $sitepress,
    PostTypeFilterInterface $postTypeFilter = null
  ) {
    $this->sitepress = $sitepress;
    $this->postTypeFilter = $postTypeFilter;
  }


  /**
   * @return array<PostTypeDto>
   */
  public function getTranslatable(): array {
    /** @var string[] $postTypes e.g ['page', 'post', 'movie' ] */
    $postTypes = array_keys( $this->getFilteredTranslatablePostTypes() );

    return $this->buildCollection( $postTypes );
  }


  /**
   * @return array<PostTypeDto>
   */
  public function getDisplayAsTranslated(): array {
    /** @var string[] $postTypes */
    $postTypes = array_keys( $this->getFilteredDisplayAsTranslatedPostTypes() );

    return $this->buildCollection( $postTypes );
  }


  /**
   * @return array<PostTypeDto>
   */
  public function getTranslatableWithoutDisplayAsTranslated(): array {
    /** @var string[] $allTranslatable */
    $allTranslatable = array_keys(
      $this->getFilteredTranslatablePostTypes()
    );
    /** @var string[] $displayAsTranslated */
    $displayAsTranslated = array_keys(
      $this->sitepress->get_display_as_translated_documents()
    );

    $postTypes = array_diff( $allTranslatable, $displayAsTranslated );

    return $this->buildCollection( $postTypes );
  }


  /**
   * @param string[] $postTypes
   *
   * @return array<PostTypeDto>
   */
  private function buildCollection( array $postTypes ): array {
    /** @var string[] $postTypes */
    $displayAsTranslated = array_keys( $this->sitepress->get_display_as_translated_documents() );
    $collection = [];

    foreach ( $postTypes as $postType ) {
      // phpcs:disable Generic.CodeAnalysis.EmptyStatement.DetectedCatch
      try {
        $isDisplayAsTranslated = in_array( $postType, $displayAsTranslated, true );
        $collection[] = $this->buildItemTypeDetails( $postType, $isDisplayAsTranslated );
      } catch ( \InvalidArgumentException $e ) {
        // Do nothing. We need it just to filter out non-existing post types.
      }
      // phpcs:enable Generic.CodeAnalysis.EmptyStatement.DetectedCatch
    }

    return $collection;
  }


  /**
   * @param string $postType
   * @return PostTypeDto
   * @throws \InvalidArgumentException
   */
  private function buildItemTypeDetails( string $postType, bool $isDisplayAsTranslated = false ): PostTypeDto {
    $postTypeObject = get_post_type_object( $postType );

    if ( ! $postTypeObject ) {
      throw new \InvalidArgumentException(
        "Post type $postType does not exist"
      );
    }

    $postTypeObject = apply_filters( 'wpml_post_type_dto_filter', $postTypeObject );

    return new PostTypeDto(
      $postTypeObject->name,
      $postTypeObject->labels->name,
      $postTypeObject->labels->singular_name,
      $postTypeObject->labels->name,
      $postTypeObject->hierarchical,
      $isDisplayAsTranslated
    );
  }


  /**
   * @return array<string, mixed>
   */
  private function getFilteredDisplayAsTranslatedPostTypes(): array {
    /** @var array<string, mixed> $postTypes */
    $postTypes = $this->sitepress->get_display_as_translated_documents();
    if ( ! $this->postTypeFilter ) {
      return $postTypes;
    }
    return $this->postTypeFilter->filter( $postTypes );
  }


  /**
   * @return array<string, mixed>
   */
  private function getFilteredTranslatablePostTypes(): array {
    /** @var array<string, mixed> $postTypes */
    $postTypes = $this->sitepress->get_translatable_documents();
    if ( ! $this->postTypeFilter ) {
      return $postTypes;
    }
    return $this->postTypeFilter->filter( $postTypes );
  }


}
