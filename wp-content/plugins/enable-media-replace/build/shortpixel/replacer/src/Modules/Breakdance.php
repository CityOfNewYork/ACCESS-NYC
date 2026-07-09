<?php
namespace EnableMediaReplace\Replacer\Modules;

use EnableMediaReplace\ShortPixelLogger\ShortPixelLogger as Log;


class Breakdance
{
    private static $instance;
    protected $queryKey = 'breakdance';

    public static function getInstance()
    {
        if (is_null(self::$instance))
          self::$instance = new static();

        return self::$instance;
    }

    public function __construct()
    {
      if (\has_action('breakdance_loaded'))   // elementor is active
      {
         if ($this->checkRequiredFunctions())
         {
               add_filter('emr/replacer/custom_replace_query', array($this, 'addBreakdance'), 10, 4);
				       add_filter('emr/replacer/load_meta_value', array($this, 'loadContent'),10,3);
               add_filter('emr/replacer/save_meta_value', array($this, 'saveContent'), 10,3);
          }
          else {
              add_filter('emr/replacer/load_meta_value', array($this, 'abortOnContent'),10,3);
          }
     }
    }

    // This integration uses several Breakdance functions.  Don't something if this dance breaks somehow
    public function checkRequiredFunctions()
    {
        $functions = [
          '\Breakdance\Data\get_tree',
          '\Breakdance\Data\encode_before_writing_to_wp',
          '\Breakdance\Data\get_global_option',
          '\Breakdance\Data\save_document'
        ];

        foreach($functions as $function)
        {
           if (false === function_exists($function))
           {
              Log::addWarn('Replacer breakdance module cannot find ' . $function);
              return false;
           }

        }

        return true;
    }

		public function addBreakdance($items, $base_url, $search_urls, $replace_urls)
		{

			$base_url = $this->addSlash($base_url);
			$el_search_urls = $search_urls; //array_map(array($this, 'addslash'), $search_urls);
			$el_replace_urls = $replace_urls; //array_map(array($this, 'addslash'), $replace_urls);
			//$args = [('json_flags' => 0, 'component' => $this->queryKey];
      $args = ['component' => $this->queryKey, 'replacer_do_save' => false, 'replace_no_serialize' => true];
			$items[$this->queryKey] = array('base_url' => $base_url, 'search_urls' => $el_search_urls, 'replace_urls' => $el_replace_urls, 'args' => $args);
			return $items;

		}

		// @todo This function is duplicated w/ elementor, so possibly at some point needs a Module main class for utils.
		public function addSlash($value)
    {
        global $wpdb;
        $value= ltrim($value, '/'); // for some reason the left / isn't picked up by Mysql.
        $value= str_replace('/', '\/', $value);
        $value =  $wpdb->esc_like(($value)); //(wp_slash) / str_replace('/', '\/', $value);

        return $value;
    }

		public function loadContent($content, $meta_row, $component)
		{
        if ($component !== $this->queryKey)
        {
           return $content;
        }

        $meta_id = $meta_row['meta_id'];
        $post_id = $meta_row['post_id'];

        $result = \Breakdance\Data\get_tree($post_id);
        if (false === $result)
        {
           Log::addWarn("Breakdance integration: Tree returns as false");
           return null;
        }

        return $result;
		}

    public function saveContent($content, $meta_row, $component)
    {
      if ($component !== $this->queryKey)
      {
         return $content;
      }

      $global = \Breakdance\Data\get_global_option('global_settings_json_string');

      $tree = json_encode($content, JSON_UNESCAPED_SLASHES);

     $paramCount = 4; // Default. 
     try 
     {
       $method = new \ReflectionFunction('Breakdance\Data\save_document');
       $paramCount = $method->getNumberOfParameters();

     }
     catch (\Exception $e)
     {
        Log::addError('Reflection check in breakdance failing', $e);
     }

     // Old vs New.  Tried via Ajax Method which seems more dynamic, put this path ends the PHP script via send_json. 
     if (4 === $paramCount)
     {
          \Breakdance\Data\save_document($tree, $global, null, $meta_row['post_id']);
     }
     elseif (10 === $paramCount)
     {
          \Breakdance\Data\save_document($tree, false, false, false, false, false, false, false, false, $meta_row['post_id']);
     }
     
     // \Breakdance\Data\save_document($content, $global, null, $meta_row['post_id']);

      /*  return \Breakdance\Data\encode_before_writing_to_wp([
          'tree_json_string' => $content,
        ], true); */

       return $content;
    }

    // If something is wrong with breakdance, don't replace content for it since it breaks the whole page content.
    public function abortOnContent($content, $meta_row, $component)
    {
      if ($component !== $this->queryKey)
      {
         return $content;
      }

      return null;
    }



} //  class
