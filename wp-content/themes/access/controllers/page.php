<?php
/**
 * General Pages
 *
 * @link /wp-json/wp/v2/pages
 * @author NYC Opportunity
 */

namespace Controller;

use Timber;

class Page extends Timber\Post {
  /**
   * Constructor
   *
   * @return  Object  This
   */
  public function __construct($pid = false) {
    if ($pid) {
      parent::__construct($pid);
    } else {
      parent::__construct();
    }

    return $this;
  }

  /**
   * Get Default Schema for pages
   *
   * @return  Array  The default schema for pages
   */
  public function getSchema() {
    return array(
      '@context' => 'http://schema.org',
      '@type' => 'WebPage',
      'mainEntityOfPage' => array(
        '@type' => 'WebPage',
        'name' => $this->title,
        'dateModified' => $this->post_modified
      ),
      'spatialCoverage' => array(
        'type' => 'City',
        'name' => 'New York'
      )
    );
  }
}
