<?php foreach ($errors as $msg): ?>
	<div class="error"><p><?php echo wp_kses_post($msg) ?></p></div>
<?php endforeach ?>