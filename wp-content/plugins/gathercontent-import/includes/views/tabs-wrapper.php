<div class="gc-template-tab-group">

	<h5 class="nav-tab-wrapper gc-nav-tab-wrapper">
	<?php foreach ( $this->get( 'tabs' ) as $tab_id => $tab ) : ?>
		<a href="#<?php echo esc_attr( $tab['id'] ); ?>" class="nav-tab <?php echo isset( $tab['nav_class'] ) ? $tab['nav_class'] : ''; ?>"><?php echo $tab['label']; ?></a>
	<?php endforeach; ?>
	</h5>

	<?php $this->output( 'before_tabs_wrapper' ); ?>

	<?php foreach ( $this->get( 'tabs' ) as $tab_id => $tab ) : ?>
		<fieldset class="gc-template-tab <?php echo isset( $tab['tab_class'] ) ? $tab['tab_class'] : ''; ?>" id="<?php echo esc_attr( $tab['id'] ); ?>">
		<legend class="screen-reader-text"><?php echo $tab['label']; ?></legend>
		<?php echo $tab['content']; ?>
		</fieldset>
	<?php endforeach; ?>

	<?php $this->output( 'after_tabs_wrapper' ); ?>

</div>
