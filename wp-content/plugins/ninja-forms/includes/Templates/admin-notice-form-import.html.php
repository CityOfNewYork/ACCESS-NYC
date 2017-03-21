<div class="updated">
    <p>
        <?php _e( 'Form Imported Successfully.', 'ninja-forms' ); ?>
        <a href="<?php echo add_query_arg( 'form_id', $form_id, admin_url( 'admin.php?page=ninja-forms' ) ); ?>"><?php _e( 'View', 'ninja-forms' ); ?></a>
    </p>
</div>