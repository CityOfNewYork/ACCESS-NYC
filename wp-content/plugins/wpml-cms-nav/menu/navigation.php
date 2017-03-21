<?php     
    $cms_navigation_settings = $iclCMSNavigation->get_settings();
?>
<script type="text/javascript">        
var wpml_cms_nav_ajx_cache_cleared = '<?php echo wpml_cms_nav_js_escape(__('The cache has been cleared.','wpml-cms-nav')); ?>';
</script>        
<div class="wrap wpml_cms_navigation">
    <h2><?php echo __('Setup WPML CMS Navigation', 'wpml-cms-nav') ?></h2>    
    
    <h3><?php echo __('Navigation', 'wpml-cms-nav') ?></h3>    
    
    <p><?php echo __('Out-of-the-box support for full CMS navigation in your WordPress site including drop down menus, breadcrumbs trail and sidebar navigation.', 'wpml-cms-nav')?></p>

    
    <h4><?php echo __('Settings', 'wpml-cms-nav')?></h4>
    <form name="icl_navigation_form"  id="icl_navigation_form" action="">
    <input type="hidden" name="icl_cms_nav_nonce" id="icl_cms_nav_nonce" value="<?php echo wp_create_nonce('icl_cms_nav_nonce'); ?>" />
    <p class="icl_form_errors" style="display:none"></p>
    <table class="widefat wpml_table" cellspacing="0">
        <tr valign="top">
            <th scope="row"><label for="icl_navigation_page_order"><?php echo __('Page order', 'wpml-cms-nav')?></label></th>
            <td>
                <select name="icl_navigation_page_order" id="icl_navigation_page_order">
                <option value="menu_order" <?php if($cms_navigation_settings['page_order']=='menu_order'): ?>selected="selected"<?php endif;?>><?php echo __('Menu order', 'wpml-cms-nav')?></option>
                <option value="post_name" <?php if($cms_navigation_settings['page_order']=='post_name'): ?>selected="selected"<?php endif;?>><?php echo __('Alphabetically', 'wpml-cms-nav')?></option>
                <option value="post_date" <?php if($cms_navigation_settings['page_order']=='post_date'): ?>selected="selected"<?php endif;?>><?php echo __('Creation time', 'wpml-cms-nav')?></option>
                </select>
            </td>
        </tr>        
        <tr valign="top">
            <th scope="row"><?php echo __('Blog posts menu', 'wpml-cms-nav')?></th>
            <td>
                <p><label for="icl_navigation_show_cat_menu"><input type="checkbox" id="icl_navigation_show_cat_menu" name="icl_navigation_show_cat_menu" value="1" <?php if($cms_navigation_settings['show_cat_menu']): ?>checked="checked"<?php endif ?> /> <?php _e('Show blog posts menu', 'wpml-cms-nav')?></label></p>
                
                <div id="icl_cat_menu_contents" <?php if(!$cms_navigation_settings['show_cat_menu']): ?>style="display:none"<?php endif ?>>
                    <?php if('page' != get_option('show_on_front') || !get_option('page_for_posts')): ?>
                    <p>
                    <label for="icl_navigation_cat_menu_title" <?php if(!$cms_navigation_settings['show_cat_menu']): ?>style="display:none"<?php endif;?>>
                    <?php echo __('Categories menu title', 'wpml-cms-nav')?>                
                    <input type="text" id="icl_navigation_cat_menu_title" name="icl_navigation_cat_menu_title" value="<?php echo $cms_navigation_settings['cat_menu_title']?esc_attr($cms_navigation_settings['cat_menu_title']):__('News','wpml-cms-nav'); ?>" /></label>&nbsp;
                    <label><?php _e('Page order', 'wpml-cms-nav') ?><input type="text" name="icl_navigation_cat_menu_page_order" value="<?php echo intval($cms_navigation_settings['cat_menu_page_order']); ?>" size="3" /></label></p>                
                    <?php endif; ?>
                    <p>
                    <?php _e('Select what items to display for the blog menu:', 'wpml-cms-nav') ?>&nbsp;
                    <label><input type="radio" name="icl_blog_menu_contents" value="categories" <?php if($cms_navigation_settings['cat_menu_contents']=='categories'): ?>checked="checked"<?php endif ?> /><?php _e('Categories', 'wpml-cms-nav') ?></label>&nbsp;
                    <label><input type="radio" name="icl_blog_menu_contents" value="posts" <?php if($cms_navigation_settings['cat_menu_contents']=='posts'): ?>checked="checked"<?php endif ?> /><?php _e('Recent posts', 'wpml-cms-nav') ?></label>&nbsp;
                    <label><input type="radio" name="icl_blog_menu_contents" value="nothing" <?php if($cms_navigation_settings['cat_menu_contents']=='nothing'): ?>checked="checked"<?php endif ?> /><?php _e('Nothing', 'wpml-cms-nav') ?></label>
                    </p>
                </div>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row"><?php echo __('Sidebar pages menu', 'wpml-cms-nav')?></th>
            <td valign="top">
                <p style="padding-top:0;margin-top:3px;">
                <label for="icl_navigation_heading_start"><?php echo __('Heading start', 'wpml-cms-nav')?> <input type="text" size="6" id="icl_navigation_heading_start" name="icl_navigation_heading_start" value="<?php echo esc_attr($cms_navigation_settings['heading_start']) ?>" /></label>
                <label for="icl_navigation_heading_end"><?php echo __('Heading end', 'wpml-cms-nav')?> <input type="text" size="6" id="icl_navigation_heading_end" name="icl_navigation_heading_end" value="<?php echo esc_attr($cms_navigation_settings['heading_end']) ?>" /></label>
                </p>
            </td>
        </tr>    
        
        <tr valign="top">
            <th scope="row"><?php echo __('Breadcrumbs separator', 'wpml-cms-nav')?></th>
            <td valign="top">
                <input type="text" name="icl_breadcrumbs_separator" value="<?php echo esc_attr($cms_navigation_settings['breadcrumbs_separator']) ?>" size="6" />
            </td>
        </tr>            
        
        <?php if(!defined('ICL_DISABLE_CACHE') || !ICL_DISABLE_CACHE):?>            
        <tfoot>
        <tr valign="top">
            <th scope="row"><?php echo __('Caching', 'wpml-cms-nav')?></th>
            <td>
                <p>
                <label for="icl_navigation_caching"><input type="checkbox" id="icl_navigation_caching" name="icl_navigation_caching" value="1" <?php if($cms_navigation_settings['cache']): ?>checked="checked"<?php endif ?> /> <?php echo __('Cache navigation elements for super fast performance', 'wpml-cms-nav')?></label>
                </p>
                <input id="icl_navigation_caching_clear" class="button" name="icl_navigation_caching_clear" value="<?php echo esc_attr(__('Clear cache now', 'wpml-cms-nav')) ?>" type="button"/>
                <span id="icl_ajx_response_clear_cache"></span>
            </td>
        </tr>  
        </tfoot>   
        <?php endif; ?>
        
    </table>
    
    <p class="submit">
    <input class="button-primary" type="submit" value="<?php echo esc_attr(__('Save Changes', 'wpml-cms-nav')) ?>" name="Submit"/>
    <span class="icl_ajx_response" id="icl_ajx_response_nav"></span>
    </p>  
    
    </form>  
    
    
    <h4><?php echo __('Instructions for adding the navigation to your theme', 'wpml-cms-nav') ?></h4>
    
    <table class="widefat" cellspacing="0">
    <thead>
        <tr>
            <th scope="col"><?php echo __('Navigation element', 'wpml-cms-nav') ?></th>
            <th scope="col"><?php echo __('Description', 'wpml-cms-nav') ?></th>
            <th scope="col"><?php echo __('HTML to add', 'wpml-cms-nav') ?></th>        
            <th scope="col"><?php echo __('Where to add', 'wpml-cms-nav') ?></th>        
        </tr>        
    </thead>        
    <tbody>
        <tr>
            <td scope="col" nowrap="nowrap"><?php echo __('Top navigation', 'wpml-cms-nav') ?></td>          
            <td scope="col"><?php echo __('A list of the top level pages with drop down menus for second level menus. Can optionally contain the post categories', 'wpml-cms-nav') ?></td>          
            <td scope="col" nowrap="nowrap"><code>&lt;?php  do_action('icl_navigation_menu'); ?&gt;</code></td>          
            <td scope="col">header.php</td>          
        </tr>
        <tr>
            <td scope="col" nowrap="nowrap"><?php echo __('Breadcrumbs trails', 'wpml-cms-nav') ?></td>          
            <td scope="col"><?php echo __('Lists the path back to the home page', 'wpml-cms-nav') ?></td>          
            <td scope="col" nowrap="nowrap"><code>&lt;?php  do_action('icl_navigation_breadcrumb', ['separator']); ?&gt;</code></td>          
            <td scope="col"><?php printf(__('%s or %s, %s, %s, %s and %s', 'wpml-cms-nav'), 'header.php', 'single.php', 'page.php', 'archive.php', 'tag.php', 'search.php');?></td>          
        </tr>
        <tr>
            <td scope="col" nowrap="nowrap"><?php echo __('Sidebar navigation', 'wpml-cms-nav'); ?> <sup>*</sup></td>          
            <td scope="col"><?php echo __('Local navigation tree with page siblings, parent and brothers', 'wpml-cms-nav') ?></td>          
            <td scope="col" nowrap="nowrap"><code>&lt;?php  do_action('icl_navigation_sidebar'); ?&gt;</code></td>          
            <td scope="col">sidebar.php</td>          
        </tr>        
    </tbody>        
    </table>    
    <p><sup>*</sup> <?php printf(__('You can also add the sidebar navigation as a <a%s>widget</a>.', 'wpml-cms-nav'), '  href="'.admin_url('widgets.php').'"')?></p>
    
    <p><?php echo __('To customize the appearance of the navigation elements, you will need to override the styling provided in the plugin\'s CSS file.', 'wpml-cms-nav')?></p>
    
    <p><?php printf(__('Visit %s for full CSS customization information.', 'wpml-cms-nav'), '<a href="https://wpml.org">wpml.org</a>')?></p>
    
    <?php do_action('icl_menu_footer'); ?>
    
</div>