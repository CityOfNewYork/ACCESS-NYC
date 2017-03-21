<div class="wrap">

    <h1>Forms
        <a href="<?php echo $add_new_url; ?>" class="page-title-action">
            <?php echo $add_new_text; ?>
        </a>
    </h1>

    <?php do_action( 'nf_admin_before_form_list' ); ?>

    <form method="post">
        <?php $table->display(); ?>
    </form>

    <?php

        if( isset( $_GET['debug' ] ) ){

            $forms = Ninja_Forms()->form()->get_forms();

            foreach( $forms as $form ){

                echo "<pre>";
                var_dump( $form->get_settings() );
                echo "</pre>";
            }
        }

    ?>

</div>