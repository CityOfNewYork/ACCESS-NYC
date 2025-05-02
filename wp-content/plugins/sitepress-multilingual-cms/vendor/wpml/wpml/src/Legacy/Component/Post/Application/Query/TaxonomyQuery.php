<?php

namespace WPML\Legacy\Component\Post\Application\Query;

use WPML\Core\Component\Post\Application\Query\Criteria\TaxonomyCriteria;
use WPML\Core\Component\Post\Application\Query\Criteria\TaxonomyTermCriteria;
use WPML\Core\Component\Post\Application\Query\Dto\PostTaxonomyDto;
use WPML\Core\Component\Post\Application\Query\Dto\PostTermDto;
use WPML\Core\Component\Post\Application\Query\TaxonomyQueryInterface;

class TaxonomyQuery implements TaxonomyQueryInterface
{

  /** @var \SitePress */
    private $sitepress;


  public function __construct( \SitePress $sitepress ) {
      $this->sitepress = $sitepress;
  }


  public function getTaxonomies( TaxonomyCriteria $criteria ): array {
    //Set  the SourceLanguageCode  filter by switching the language
      $original_language
      = $this->applySourceLanguageCodeCriteria( $criteria->getSourceLanguageCode() );

      $wpTaxonomies = get_taxonomies( [], 'objects' );

    //Restore the original language
    if ( $original_language ) {
        $this->sitepress->switch_lang( $original_language );
    }

      return array_map(
        function ( $wpTaxonomy ) {
          // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
            return new PostTaxonomyDto(
              $wpTaxonomy->name,
              $wpTaxonomy->label,
              array_values( $wpTaxonomy->object_type )
            );
        },
        $wpTaxonomies
      );
  }


  /**
   * @param TaxonomyTermCriteria $criteria
   *
   * @return PostTermDto[]
   * @throws \Exception
   */
  public function getTerms( TaxonomyTermCriteria $criteria ): array {
    //Set  the SourceLanguageCode  filter by switching the language
      $original_language
      = $this->applySourceLanguageCodeCriteria( $criteria->getSourceLanguageCode() );

    /** @var array<\WP_Term>|\WP_Error $wpTerms */
      $wpTerms = get_terms(
        array(
          'orderby'    => 'name',
          'taxonomy'   => $criteria->getTaxonomyId(),
          'hide_empty' => true,
          'search'     => $criteria->getSearch() ?? '',
          'number'     => $criteria->getLimit() ?? '',
          'offset'     => (int) $criteria->getOffset(),
          )
      );

    //Restore the original language
    if ( $original_language ) {
        $this->sitepress->switch_lang( $original_language );
    }

    if ( is_wp_error( $wpTerms ) ) {
        throw new \Exception( 'Could not retrieve terms.' );
    }

      return array_values(
        array_map(
          function ( $wpTerm ) {
            // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
              return new PostTermDto( $wpTerm->term_id, $wpTerm->name );
          },
          $wpTerms
        )
      );
  }


  /**
   * Apply the SourceLanguageCode criteria to the query.
   *
   *
   * @param string $sourceLanguageCode The source language code.
   *
   * @return string|null The original language code or null if the source language code is the same as the user's language.
   */
  private function applySourceLanguageCodeCriteria( string $sourceLanguageCode ) {
      $original_language = $this->sitepress->get_current_language();
    if ( $original_language === $sourceLanguageCode ) {
        return null;
    }
      $this->sitepress->switch_lang( $sourceLanguageCode );

      return $original_language;
  }


}
