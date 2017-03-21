<div class="error se-error-box">
	<p>
		Oops, there are errors in your submit:
		<ul>
			<?php foreach($errors as $field => $message): ?>
			<li><?php echo sprintf($message, $fields[$field]); ?></li>
			<?php endforeach; ?>
		</ul>
	</p>
	<p>Please go <a href="#" class="se-back">back</a> and check your settings again.</p>
</div>
