<?php

function wpml_cms_nav_db_setup(){
    global $wpdb;
    
    if ( method_exists($wpdb, 'has_cap') && $wpdb->has_cap( 'collation' ) ) {
            if ( ! empty($wpdb->charset) )
                    $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
            if ( ! empty($wpdb->collate) )
                    $charset_collate .= " COLLATE $wpdb->collate";
    }else{
        $charset_collate = '';
    }    
    
    // cms navigation caching
    $table_name = $wpdb->prefix.'icl_cms_nav_cache';
    if($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name){
        $sql = "
            CREATE TABLE `{$table_name}` (
            `id` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
            `cache_key` VARCHAR( 128 ) NOT NULL ,
            `type` VARCHAR( 128 ) NOT NULL ,
            `data` TEXT NOT NULL ,
            `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) {$charset_collate}"; 
       $wpdb->query($sql);
    }    
}

function wpml_cms_nav_default_settings(){
    global $sitepress_settings, $sitepress;
    $current_settings = get_option('wpml_cms_nav_settings');
    if(empty($current_settings)){
        
        // legacy
        $iclsettings['modules'] = isset($sitepress_settings['modules']) ? $sitepress_settings['modules'] : array();
        if(!empty($iclsettings['modules']['cms-navigation'])){
            $old_settings = $iclsettings['modules']['cms-navigation'];
            unset($iclsettings['modules']['cms-navigation']);
            $sitepress->save_settings($iclsettings);                
        }

        $default_settings = array(
            'page_order'            => 'menu_order',
            'show_cat_menu'         => 0,
            'cat_menu_page_order'   => '0',
            'cat_menu_contents'     => 'posts',
            'heading_start'         => '',
            'heading_end'           => '',
            'cache'                 => 0,
            'breadcrumbs_separator' => ' &raquo; ',
            'cat_menu_title'        => __('News','wpml-cms-nav')
        );
        
        foreach($default_settings as $k=>$v){
            if(isset($old_settings[$k])){
                $default_settings[$k] = $old_settings[$k];
            }
        }
        
        update_option('wpml_cms_nav_settings', $default_settings);            
    }
    
}