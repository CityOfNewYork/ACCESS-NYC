<?php

/**
 * Locations
 *
 * @link /wp-json/wp/v2/homepage_tout
 * @author NYC Opportunity
 */

namespace Controller;

use Timber;
use DateTime;
use DateTimeZone;

class HomepageTout extends Timber\Post {
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

    $this->status = $this->getStatus();

    /**
     * Structured Data
     */

    $this->item_scope = $this->getItemScope();

    // Use the post permalink for the url if post relationship is present
    $this->link_url = ($this->link_to_content) ? get_permalink($this->link_to_content[0]) : $this->link_url;

    return $this;
  }

  /**
   * Items returned in this object determine what is shown in the WP REST API.
   * Called by 'rest-prepare-posts.php' must use plugin.
   *
   * @return  Array  Items to show in the WP REST API
   */
  public function showInRest() {
    return array(
      'status' => $this->getStatus()
    );
  }

  /**
   * Calculate and get the status of the Tout
   *
   * @return  Object/Null  Status. If cleared return null.
   */
  public function getStatus() {
    $status = array(
      'type' => $this->custom['tout_status_type'],
      'text' => $this->custom['tout_status']
    );

    $clear = strtotime($this->custom['tout_status_clear_date']);

    $date = new DateTime('now', new DateTimeZone('America/New_York'));
    $now = $date->getTimestamp() + $date->getOffset();

    if ($this->custom['tout_status'] && !$clear) {
      return $status;
    }

    if ($clear < $now) {
      return false;
    } else {
      return $status;
    }
  }

  /**
   * Get the itemtype for structure data
   *
   * @return  String  The itemtype
   */

  public function getItemScope() {
    $item_scope = false;

    if ($this->custom['tout_status_type'] == 'covid-response') {
      $item_scope = 'SpecialAnnouncement';
    }

    return $item_scope;
  }
}
