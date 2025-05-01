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
<div class="wpbitly-spacer"></div>

<h4>7 Day Performance</h4>
<div class="wpbitly-chart"></div>

<div id="wpbitly-actions">
    <div id="regenerate-action">
        <a href="<?php echo esc_html(add_query_arg('wpbr', 'true', esc_url($request_uri))); ?>" class="regeneratelink">Regenerate</a>
    </div>

    <div id="getshortlink-action">
        <button type="button" class="button button-large" onclick="alert('URL: ' + jQuery('#shortlink').val());">Get Shortlink</button>
    </div>
    <div class="clear"></div>
</div>

<script>
    jQuery(document).ready(function($) {
        setTimeout(function () {
            new Chartist.Line('.wpbitly-chart', {
                labels: [<?php echo esc_js(implode(',', $labels_arr));?>],
                series: [
                  [<?php echo esc_js($data_js ); ?>]
                ]
              }, {
                high: <?php echo (int) $max; ?>,
                low: 0,
                fullWidth: true,
                showArea: true,
                showPoint: false,
                axisY: {
                  onlyInteger: true
                },
                axisX: {
                  showLabel: false
                }
              });
        }, 500);
    });
  
</script>