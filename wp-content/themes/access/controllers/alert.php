<?php

/**
 * Alert
 *
 * @link /wp-json/wp/v2/alert
 * @author NYC Opportunity
 */

namespace Controller;

use Timber;
use DateTime;
use DateTimeZone;

class Alert extends Timber\Post {
  /**
   * Constructor
   *
   * @param   Object/Number  $pid  Post object or ID
   *
   * @return  Object               This
   */
  public function __construct($pid = false) {
    if ($pid) {
      parent::__construct($pid);
    } else {
      parent::__construct();
    }

    /**
     * Post Properties
     */

    $this->status = $this->getStatus();

    /**
     * Structured Data
     */

    $this->item_scope = $this->getItemScope();

    $this->itemprop_body = $this->getItempropBody();

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
      'status' => $this->getStatus(),
      'item_scope' => $this->getItemScope(),
    );
  }

  /**
   * Get the status of the alert, based on the color.
   *
   * @return  String  status
   */
  public function getStatus() {
    return array(
      'type' => $this->custom['alert_color']
    );
  }

  /**
   * Get the itemtype for structure data
   *
   * @return  String  The itemtype
   */
  public function getItemScope() {
    $item_scope = false;

    if ($this->getStatus()['type'] === self::STATUS_COVID) {
      $item_scope = self::ITEMSCOPE_COVID;
    }

    return $item_scope;
  }

  /**
   * Get the itemprop for body element
   *
   * @return  String  The itemprop
   */

  public function getItempropBody() {
    $itemprop_body = false;

    if ($this->getStatus()['type'] === self::STATUS_COVID) {
      $itemprop_body = 'text';
    }

    return $itemprop_body;
  }

  /**
   * Constants
   */

  const STATUS_COVID = 'covid-response';

  const ITEMSCOPE_COVID = 'SpecialAnnouncement';
}
