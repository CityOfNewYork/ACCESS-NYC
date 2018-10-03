<input type="text" name="<?= $args['id'] ?>" id="<?= $args['id'] ?>" value="<?= get_option($args['id'], '') ?>" placeholder="<?= $args['placeholder'] ?>"/>

<p class="description">
  Your Application's Token ID should be created and managed on the Open Data portal.

  <? if ($_ENV[strtoupper($args['id'])]) : ?>
    Environment currently set to <code><?= $_ENV[strtoupper($args['id'])] ?></code>.
  <? else : ?>
    This plugin will also look for an environment variable called 'OPEN_DATA_APP_TOKEN'. ex; <code>$_ENV['DROOLS_URL']</code>.
  <? endif; ?>

  However, the plugin will prioritize the input here if set.
</p>