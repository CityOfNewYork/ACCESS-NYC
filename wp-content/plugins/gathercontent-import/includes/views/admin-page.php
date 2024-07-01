<div class="wrap gc-admin-wrap">
	<h2><?php $this->output( 'logo' ); ?></h2>
	<?php settings_errors(); ?>

	<form method="post" action="options.php">
		<?php
		settings_fields( $this->get( 'option_group' ) );
		$this->output( 'settings_sections' );
		?>
		<p class="submit">
			<?php if ( $this->get( 'refresh_button' ) ) : ?>
				<?php $this->output( 'refresh_button' ); ?>
				&nbsp;
			<?php endif; ?>
			<?php if ( $this->get( 'go_back_url' ) ) : ?>
				<a class="button button-large" href="<?php $this->output( 'go_back_url' ); ?>"><?php $this->output( 'go_back_button_text' ); ?></a>
				&nbsp;
			<?php endif; ?>
			<?php submit_button( $this->get( 'submit_button_text' ), 'primary large', 'submit', false ); ?>
		</p>
	</form>

</div>
