<?php
namespace EnableMediaReplace;
use EnableMediaReplace\ShortPixelLogger\ShortPixelLogger as Log;
use EnableMediaReplace\Notices\NoticeController as Notices;

use EnableMediaReplace\Externals\Elementor as Elementor;
use EnableMediaReplace\Externals\WpBakery as WpBakery;
use EnableMediaReplace\Externals\SiteOrigin as SiteOrigin;
use EnableMediaReplace\Externals\WPOffload as WPOffload;


class Externals
{
  protected $replaceType = null;
  protected $replaceSearchType = null;

  protected $messages = array();

  public function __construct()
  {
    // These hooks prevent loading of options when plugin conflicts arise.
      add_filter('emr_display_replace_type_options', array($this, 'get_replace_type'));
      add_filter('emr_enable_replace_and_search', array($this, 'get_replacesearch_type'));
      add_action('emr_after_replace_type_options', array($this, 'get_messages'));

      $this->check();

      // integrations
      $this->loadElementor();
      $this->loadBakery(); // in case of urlencoded issues, this class should be used probably.
			$this->loadSiteOrigins();
	   $this->loadWpOffload();
  }

  protected function check() //  check if any of the options should be disabled due to conflicts
{
    /*if (class_exists('FLBuilder'))
    {
      $this->replaceSearchType = false;
      $this->messages[]				return true;
 = __('Replace and Search feature is not compatible with Beaver Builder.', 'enable-media-replace');
    } */
}

  public function get_replace_type($bool)
  {
    if ($this->replaceType === null)
      return $bool;

    return $this->replaceType;
  }

  public function get_replacesearch_type($bool)
  {
    if ($this->replaceSearchType === null)
      return $bool;

    return $this->replaceSearchType;
  }

  public function get_messages()
  {
      foreach($this->messages as $message)
      {
        echo '<span class="nofeature-notice"><p>'. $message . '</p></span>';
      }
  }

  public function loadElementor()
  {
         Elementor::getInstance();
  }

  public function loadBakery()
  {
      WpBakery::getInstance();
  }

	public function loadSiteOrigins()
	{
		 SiteOrigin::getInstance();
	}

	public function loadWPOffload()
	{
		 WPOffload::getInstance();
	}

} // class
