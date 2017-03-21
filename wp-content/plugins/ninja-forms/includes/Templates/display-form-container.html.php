<?php do_action( 'ninja_forms_before_form_display', $form_id ); ?>
<?php do_action( 'ninja_forms_display_pre_init', $form_id ); ?>
<?php do_action( 'ninja_forms_display_init', $form_id ); ?>
<?php if( is_user_logged_in() )do_action( 'ninja_forms_display_user_not_logged_in', $form_id ); ?>
<div id="nf-form-<?php echo $form_id; ?>-cont" class="nf-form-cont">

    <div class="nf-loading-spinner"></div>

</div>
<?php do_action('ninja_forms_display_after_form', $form_id); ?>
<?php do_action( 'ninja_forms_after_form_display', $form_id ); ?>