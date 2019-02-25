<div class='notice' id='emr-news' style="padding-top: 7px">
    <div style="float:right;"><a href="javascript:emrDismissNews()" class="button" style="margin-top:10px;"><?php _e('Dismiss', 'enable-media-replace');?></a></div>
    <h3 style="margin:10px;"><?php _e('Enable Media Replace recommends optimizing your images with ShortPixel','enable-media-replace');?></h3>
    <p><strong>
            <a href="https://shortpixel.com/h/af/VKG6LYN28044" target="_blank">
                <?php _e( 'Test your website with  ShortPixel for free to see how much you could gain by optimizing your images.', 'enable-media-replace' ); ?>
            </a>
        </strong></p>
    <a href="https://shortpixel.com/h/af/VKG6LYN28044" target="_blank" style="float: left;margin-right: 10px;">
        <img src="<?php echo plugins_url('img/sp.png', __FILE__ ); ?>" class="emr-sp"/>
    </a>
    <p style="margin-bottom:0px;">
        <?php _e( 'ShortPixel is an easy to use, comprehensive, stable and frequently updated image optimization plugin supported by the friendly team that created it. Using a powerful set of specially tuned algorithms, it squeezes the most of each image striking the best balance between image size and quality. Current images can be all optimized with a single click. Newly added images are automatically resized/rescaled and optimized on the fly, in the background.', 'enable-media-replace' ); ?>
    </p>
    <p style="text-align: right;margin-top: 0;">
        <a href="https://shortpixel.com/h/af/VKG6LYN28044" target="_blank">&gt;&gt; <?php _e( 'More info', 'enable-media-replace' ); ?></a>
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

