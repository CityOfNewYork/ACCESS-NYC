<?php

define('WPML_CMS_NAV_PLUGIN_FOLDER', basename(WPML_CMS_NAV_PLUGIN_PATH));

define('WPML_CMS_NAV_PLUGIN_URL', plugins_url('', dirname(__FILE__)));

define('WPML_CMS_NAV_CACHE_EXPIRE', '1 HOUR');

if(!defined('PHP_EOL')){ define ('PHP_EOL',"\r\n"); }
