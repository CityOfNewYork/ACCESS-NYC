<?php
/**
* Plugin Name: BSD Tools Signup Form
* Description: Widget to display a small signup form
* Version: 0.1
* Author: Blue State Digital
* Author URI: http://www.bluestatedigital.com
*/

class BSD_Signup_Form_Widget extends WP_Widget {

    /**
    * Constructor
    */
    function __construct() {
        parent::__construct('bsd_signup_form', __( 'BSD Tools Signup Form' ), array('description' => __('Display a small signup form')));

        if ( is_active_widget(false, false, $this->id_base) ) {
            add_action( 'wp_enqueue_scripts', array($this, 'bsd_signup_form_scripts') );
        }
    }

    public function bsd_signup_form_scripts() {
        wp_enqueue_script( 'bsd-signup-form-js', plugin_dir_url( __FILE__ ) . '/bsd-signup-form.min.js', array( 'jquery' ), '0.0.2', true );
    }

    function form( $instance ) {
        $defaults = array(
            'title' => '',
            'form_url' => '',
            'thank_you_message' => '',
            'small_display' => false
        );

        $instance = wp_parse_args( (array) $instance, $defaults );

        $title = $instance['title'];
        $form_url = $instance['form_url'];
        $thank_you_message = $instance['thank_you_message'];
        $small_display = $instance['small_display'];
        ?>
        <p>
            <label for="<?php echo $this->get_field_id( 'title' );?>">Title</label>
            <input class="widefat" type="text" id="<?php echo $this->get_field_id( 'title' );?>" name="<?php echo $this->get_field_name( 'title' );?>" value="<?php echo esc_attr( $title ); ?>">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'form_url' );?>">Tools Form URL</label>
            <input class="widefat" type="text" id="<?php echo $this->get_field_id( 'form_url' );?>" name="<?php echo $this->get_field_name( 'form_url' );?>" value="<?php echo esc_attr( $form_url ); ?>">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'thank_you_message' );?>">Thank You Message</label>
            <textarea class="widefat" id="<?php echo $this->get_field_id( 'thank_you_message' );?>" name="<?php echo $this->get_field_name( 'thank_you_message' );?>"><?php echo $thank_you_message; ?></textarea>
        </p>
        <p>
            <input type="checkbox" id="<?php echo $this->get_field_id( 'small_display' );?>" name="<?php echo $this->get_field_name( 'small_display' );?>" value="true" <?php if ( $small_display ) : ?>checked<?php endif;?> >
            <label for="<?php echo $this->get_field_id( 'small_display' );?>">Display as a single line without zip</label>
        </p>
    <?php
    }

    function update( $new_instance, $old_instance ) {
        $instance = $old_instance;
        $instance['title'] = $new_instance['title'];
        $instance['form_url'] = $new_instance['form_url'];
        $instance['thank_you_message'] = $new_instance['thank_you_message'];
        $instance['small_display'] = !empty($new_instance['small_display']) ? true : false;
        return $instance;
    }

    function widget( $args, $instance ) {
        extract( $args );
        echo $before_widget;
        if (!empty($instance['title'])) {
            echo $before_title . $instance['title'] . $after_title;
        }
    ?>
    <form class="bsd-signup" action="<?php echo $instance['form_url'];?>" role="form">
        <label for="email"<?php if ( !empty( $instance['small_display'] ) ): ?> class="hide"<?php endif; ?>><?php echo __('Email Address');?></label>
        <?php if ( !empty( $instance['small_display'] ) ): ?>
        <div class="row collapse">
        <div class="small-9 medium-8 columns">
        <?php endif; ?>
        <input type="email" name="email" id="email" placeholder="<?php echo __('Email Address');?>" required>
        <?php if ( !empty( $instance['small_display'] ) ): ?>
        </div> <!-- /.columns -->
        <div class="small-3 medium-4 columns">
        <?php else: ?>
        <label for="zip"><?php echo __('Zip/Postal Code');?></label>
        <input type="tel" name="zip" id="zip" placeholder="<?php echo __('Zip/Postal Code');?>" required>
        <?php endif; ?>
        <button type="submit" class="button<?php if ( !empty( $instance['small_display'] ) ): ?> expand<?php endif;?>">Sign Up</button>
        <?php if ( !empty( $instance['small_display'] ) ): ?>
        </div> <!-- /.columns -->
        </div> <!-- /.row -->
        <?php endif; ?>
    </form>
    <p class="hide"><?php echo $instance['thank_you_message']; ?></p>
    <?php
        echo $after_widget;
    }
}

function bsd_signup_form_register() {
    register_widget( 'BSD_Signup_Form_Widget' );
}
add_action( 'widgets_init', 'bsd_signup_form_register' );