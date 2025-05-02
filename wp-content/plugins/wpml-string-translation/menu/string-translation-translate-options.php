<div class="wrap">
	<h2><?php echo __( 'Admin Texts Translation', 'wpml-string-translation' ); ?></h2>
	<?php
		$notices              = wpml_get_admin_notices();
		$noticeId             = 'AutoRegisterStringsNotice3';

		$linkHref   = 'https://wpml.org/documentation/getting-started-guide/string-translation/finding-strings-that-dont-appear-on-the-string-translation-page/?utm_source=plugin&utm_campaign=string-translation&utm_medium=gui&utm_term=admin-texts-page#translate-admin-and-settings-strings';
		$openLink   = '<a href="' . $linkHref . '" target="_blank">';
		$closeLink  = '</a>';
	?>
	<div class="notice otgs-notice">
		<p>
			<?php echo __( 'Translate texts you can customize from the WordPress admin but which appear on the front-end.', 'wpml-string-translation' ); ?>
		</p>
		<p>
			<?php echo __( 'This includes strings like footer text, copyright notices, plugin options and settings, time format, widget texts, and more.', 'wpml-string-translation' ); ?>
		</p>
		<p>
			<?php echo sprintf( __( 'Learn more about %1$stranslating admin and settings strings.%2$s', 'wpml-string-translation' ), $openLink, $closeLink ); ?>
		</p>
	</div>

	<div id="icl_st_option_writes">
		<div id="wpml-admin-text-options"/>

	</div>
	<p>
		<a href="<?php echo admin_url( 'admin.php?page=' . WPML_ST_FOLDER . '/menu/string-translation.php' ); ?>">
			&laquo; <?php _e( 'Return to String Translation', 'wpml-string-translation' ); ?>
		</a>
	</p>
</div>
