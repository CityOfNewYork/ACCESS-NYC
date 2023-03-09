<div class="wpbitly-spacer"></div>

<h4>7 Day Performance</h4>
<div class="wpbitly-chart"></div>

<div id="wpbitly-actions">
    <div id="regenerate-action">
        <a href="<?php echo add_query_arg('wpbr', 'true', $request_uri); ?>" class="regeneratelink">Regenerate</a>
    </div>

    <div id="getshortlink-action">
        <button type="button" class="button button-large" onclick="prompt('URL:', jQuery('#shortlink').val());">Get Shortlink</button>
    </div>
    <div class="clear"></div>
</div>

<script>
  new Chartist.Line('.wpbitly-chart', {
    labels: [<?php echo $labels_js; ?>],
    series: [
      [<?php echo $data_js; ?>]
    ]
  }, {
    high: <?php echo $max; ?>,
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
</script>