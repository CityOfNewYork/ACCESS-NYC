<?php

/**
 * Display the WP Bitly Metabox on enabled posts
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://watermelonwebworks.com
 * @since      1.0.0
 *
 * @package    Wp_bitly
 * @subpackage Wp_bitly/admin/partials
 */
?>

<?php
if($shortlink){
    $text = "Regenerate";
}else{
    $text = "Generate new Shortlink";
}
?>

<div class="wpbitly-spacer"></div>


<div id="wpbitly-actions">
    <div id="regenerate-action">
        <a href="<?php echo add_query_arg('wpbr', 'true', $request_uri); ?>" class="regeneratelink"><?php echo $text;?></a>
    </div>
    <div class="clear"></div>

    
    <div class="clear"></div>
</div>