<div class='notice' id='emr-news' style="padding-top: 7px">
    <div style="float:right;"><a href="javascript:emrDismissNews()" class="button" style="margin-top:10px;"><?php _e('Dismiss', 'enable-media-replace');?></a></div>
    <a href="https://shortpixel.com/wp/af/VKG6LYN28044" target="_blank" style="float: left;margin-right: 10px;">
        <img src="<?php echo plugins_url('img/sp.png', __FILE__ ); ?>" class="emr-sp"/>
    </a>
    <h3 style="margin:10px;"><?php _e('Enable Media Replace is now compatible with ShortPixel!','enable-media-replace');?></h3>
    <p style="margin-bottom:0px;">
        <?php _e( '<a href="https://shortpixel.com/wp/af/VKG6LYN28044" target="_blank">ShortPixel</a> is an image optimization plugin and if you have it activated, upon replacing an image in Enable Media Replace, the image will be also automatically optimized.', 'enable-media-replace' ); ?>
    </p>
    <p style="text-align: right;margin-top: 0;">
        <a href="https://shortpixel.com/wp/af/VKG6LYN28044" target="_blank">&gt;&gt; <?php _e( 'More info', 'enable-media-replace' ); ?></a>
    </p>
</div>
<script>
    function emrDismissNews() {
        jQuery("#emr-news").hide();
        var data = { action  : 'emr_dismiss_notices'};
        jQuery.get('<?php echo admin_url('admin-ajax.php'); ?>', data, function(response) {
            data = JSON.parse(response);
            if(data["Status"] == 0) {
                console.log("dismissed");
            }
        });
    }
</script>

