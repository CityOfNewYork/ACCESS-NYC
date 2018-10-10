<? $option = $args['option'] ?>

<form method="POST" action="<?= admin_url('admin.php') ?>">
  <h2>Saved Transients</h2>

  <p>Add a new transients here. Enter a valid name (no spaces; only underscores, upper, and lower case letters) and valid URL endpoint. To update a transient url, enter the name you would like to update.</p>

  <table class="form-table">
    <tbody>
      <? if (null !== $option && $option !== '') : ?>
        <? $transients = json_decode($option, true) ?>
        <? foreach ($transients as $transient) : ?>
          <tr>
            <th scope="row"><label for="transient_name"><?= $transient['name'] ?></label></th>
            <td><?= $transient['url'] ?></td>
          </tr>
        <? endforeach ?>
      <? endif ?>

      <tr>
        <th scope="row">
          <label for="<?= $args['id'] ?>_name">Name</label>
          <input type="text" name="<?= $args['id'] ?>_name" id="<?= $args['id'] ?>_name" />
        </th>
        <td>
          <div><label for="<?= $args['id'] ?>_url"><b>URL</b></label></div>
          <input type="text" name="<?= $args['id'] ?>_url" id="<?= $args['id'] ?>_url" size="50"/>
        </td>
      </tr>
    </tbody>
  </table>

  <? wp_nonce_field('admin_action_' . $args['id'], $args['id'] . '_nonce') ?>

  <p class="description"></p>

  <input type="hidden" name="action" value="<?= $args['id'] ?>" />

  <p class="submit">
    <input type="submit" value="Save Transient" class="button button-primary" />
  </p>
</form>