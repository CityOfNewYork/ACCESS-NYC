<?php

namespace CovidJobs;

/**
 * Usage
 *
 * In any WYSIWYG editor;
 *
 * [covid_jobs]
 *
 * Programatically;
 *
 * do_shortcode('[covid_jobs]');
 */

/**
 * This is my API Key. Low priority. Replace this with an ACCESS Airtable User SYSTEM KEY - Devon
 */
define('AIRTABLE_API_KEY', 'key61LgclzJJW4nNx');

/**
 * This plugin pulls data from an Airtable Database. It will use the WP Transient Cache to
 * Cache the request so if the database updates then the object cache will need to be flushed.
 * It should be possible to put expiry on the cache as well.
 *
 * Airtable DB (source); https://airtable.com/tblTGmhD3E5f1l162/viwlVZxg7AwokDih2?blocks=hide
 * Airtable API Docs; https://airtable.com/appJVoFY0tsKUtobw/api/docs#curl/authentication
 * WP Shortcodes Guide; https://pagely.com/blog/creating-custom-shortcodes/
 */

class CovidJobs {
  public $transient = 'covid_jobs';

  public $transient_exp = DAY_IN_SECONDS;

  public $shortCode = 'covid_jobs';

  public $request = 'https://api.airtable.com/v0/appJVoFY0tsKUtobw/Imported%20table?api_key=';

  /**
   * The Class Constructor
   */
  public function __construct() {
    add_shortcode($this->shortCode, [$this, 'shortCode']);
  }

  /**
   * ShortCode Function
   */
  public function shortCode() {
    $records = $this->getRecords();

    $structured = $this->clean($records);

    $compiled = '';

    foreach ($structured as $category => $data) {
      $rows = implode('', array_map(function($fields) {
        $org = $fields['org'];
        $link = $fields['link'];

        return "<tr><td align='left'><a href='$link' target='_blank'>$org</a></td></tr>";
      }, $data));

      debug($rows);

      $compiled = "$compiled<h2>$category</h2><table><tbody>$rows</tbody></table>";
    }

    return $compiled;
  }

  /**
   * Clean/Structure Data
   */
  public function clean($records) {
    $structured = array();

    foreach ($records as $data) {
      $fields = (array) $data->fields;

      $cat = $fields['Category'];
      $org = $fields['Hiring Organization'];
      $link = $fields['Link To Positions'];

      if (null === $structured[$cat]) {
        $structured[$cat] = array();
      }

      $structured[$cat][] = array(
        'org' => $org,
        'link' => $link
      );
    }

    return $structured;
  }

  /**
   * Get airtable records
   *
   * @return  Array  Airtable Records
   */
  public function getRecords() {
    $transient = $transients[$this->transient];
    $data = get_transient($transient); // CACHE

    if (false === $data) {
      $api_url = $this->request . AIRTABLE_API_KEY;

      // Create a stream
      $opts = array(
        'https' => array(
          'method' => "GET",
        )
      );

      $context = stream_context_create($opts);

      // Open the file using the HTTP headers set above
      $file = file_get_contents($api_url, false, $context);

      $jsonObj = json_encode($file);

      $records_data_parse = (array) json_decode($file);

      $data = $records_data_parse['records'];

      set_transient($transient, $data, $this->transient_exp);
    }

    return $data;
  }
}

new CovidJobs();
