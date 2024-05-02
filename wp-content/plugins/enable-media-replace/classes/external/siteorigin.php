<?php
namespace EnableMediaReplace\Externals;


class SiteOrigin
{
	 protected static $instance;

	 public function __construct()
	 {
		  if (defined('SITEORIGIN_PANELS_VERSION'))
			{
				add_filter('emr/replacer/option_fields', array($this, 'addOption'));
			}
	 }

	 public static function getInstance()
	 {
		  if (is_null(self::$instance))
			{
				 self::$instance = new SiteOrigin();
			}

			return self::$instance;
	 }

	 public function addOption($options)
	 {
		  $options[] = 'widget_siteorigin-panels-builder';
			return $options;
	 }
} // class
