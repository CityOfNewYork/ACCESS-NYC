<?php if( ninja_forms_three_addons_version_check() ): ?>
<a href="<?php menu_page_url( 'ninja-forms-three' ); ?>" class="button button-primary">Update & Convert Forms</a>
<?php else: ?>
    <button class="button" disabled><?php echo __( 'Please update your add-ons before upgrading.', 'ninja-forms' ); ?></button>
<?php endif; ?>
