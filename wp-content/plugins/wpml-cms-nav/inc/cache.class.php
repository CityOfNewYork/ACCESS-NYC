<?php
function wpml_cms_nav_cache_get($key){
    $icl_cache = get_option('_wpml_cms_nav_cache');
    if(isset($icl_cache[$key])){
        return $icl_cache[$key];
    }else{
        return false;
    }
}  

function wpml_cms_nav_cache_set($key, $value=null){
    $icl_cache = get_option('_wpml_cms_nav_cache');
    if(false === $icl_cache){
        delete_option('_icl_cache');
    }
    if(!is_null($value)){
        $icl_cache[$key] = $value;    
    }else{
        if(isset($icl_cache[$key])){
            unset($icl_cache[$key]);
        }        
    }
    update_option('_wpml_cms_nav_cache', $icl_cache);
}

function wpml_cms_nav_cache_clear($key){
    delete_option('_wpml_cms_nav_cache');
}

class wpml_cms_nav_cache{
   
    private $data;
    
    function __construct($name = "", $cache_to_option = false){
        $this->data = array();
        $this->name = $name;
        $this->cache_to_option = $cache_to_option;
        
        if ($cache_to_option) {
            $this->data = wpml_cms_nav_cache_get($name.'_cache_class');
            if ($this->data == false){
                $this->data = array();
            }
        }
    }
    
    function cache_disabled(){
        return defined('WPML_CMS_NAV_DISABLE_CACHE') && WPML_CMS_NAV_DISABLE_CACHE;
    }
    
    function get($key) {
        if($this->cache_disabled()){
            return null;
        }
        return $this->data[$key];
    }
    
    function has_key($key){
        if($this->cache_disabled()){
            return false;
        }
        return array_key_exists($key, (array)$this->data);
    }
    
    function set($key, $value) {
        if($this->cache_disabled()){
            return;
        }
        $this->data[$key] = $value;
        if ($this->cache_to_option) {
            wpml_cms_nav_cache_set($this->name.'_cache_class', $this->data);
        }
    }
    
    function clear() {
        $this->data = array();
        if ($this->cache_to_option) {
            wpml_cms_nav_cache_clear($this->name.'_cache_class');
        }
    }
}
