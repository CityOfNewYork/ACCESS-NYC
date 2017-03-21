<?php
/**
* Plugin Name: Social Follow Widgets
* Description: Display links and icons for social network pages
* Version: 0.2
* Author: Blue State Digital
* Author URI: http://www.bluestatedigital.com
*/

class Social_Follow_Widget extends WP_Widget {

    /**
    * Constructor
    */
    function __construct() {
        parent::__construct('social_follow_widget', __( 'Social Follow Icons' ), array('description' => __('Display some social network icons')));
    }

    function form( $instance ) {
        $defaults = array(
            'facebook' => '',
            'twitter' => '',
            'googleplus' => '',
            'instagram' => '',
            'flickr' => '',
            'pinterest' => '',
            'linkedin' => '',
            'youtube' => '',
            'email' => ''
        );

        $instance = wp_parse_args( (array) $instance, $defaults );

        $facebook = $instance['facebook'];
        $twitter = $instance['twitter'];
        $googleplus = $instance['googleplus'];
        $instagram = $instance['instagram'];
        $flickr = $instance['flickr'];
        $pinterest = $instance['pinterest'];
        $linkedin = $instance['linkedin'];
        $youtube = $instance['youtube'];
        $email = $instance['email'];

        // Markup for form ?>
        <p>
            <label for="<?php echo $this->get_field_id( 'facebook' );?>">Facebook URL</label>
            <input class="widefat" type="text" id="<?php echo $this->get_field_id( 'facebook' );?>" name="<?php echo $this->get_field_name( 'facebook' );?>" value="<?php echo esc_attr( $facebook ); ?>">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'twitter' );?>">Twitter URL</label>
            <input class="widefat" type="text" id="<?php echo $this->get_field_id( 'twitter' );?>" name="<?php echo $this->get_field_name( 'twitter' );?>" value="<?php echo esc_attr( $twitter ); ?>">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'googleplus' );?>">Google+ URL</label>
            <input class="widefat" type="text" id="<?php echo $this->get_field_id( 'googleplus' );?>" name="<?php echo $this->get_field_name( 'googleplus' );?>" value="<?php echo esc_attr( $googleplus ); ?>">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'instagram' );?>">Instagram URL</label>
            <input class="widefat" type="text" id="<?php echo $this->get_field_id( 'instagram' );?>" name="<?php echo $this->get_field_name( 'instagram' );?>" value="<?php echo esc_attr( $instagram ); ?>">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'flickr' );?>">Flickr URL</label>
            <input class="widefat" type="text" id="<?php echo $this->get_field_id( 'flickr' );?>" name="<?php echo $this->get_field_name( 'flickr' );?>" value="<?php echo esc_attr( $flickr ); ?>">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'pinterest' );?>">Pinterest URL</label>
            <input class="widefat" type="text" id="<?php echo $this->get_field_id( 'pinterest' );?>" name="<?php echo $this->get_field_name( 'pinterest' );?>" value="<?php echo esc_attr( $pinterest ); ?>">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'linkedin' );?>">LinkedIn URL</label>
            <input class="widefat" type="text" id="<?php echo $this->get_field_id( 'linkedin' );?>" name="<?php echo $this->get_field_name( 'linkedin' );?>" value="<?php echo esc_attr( $linkedin ); ?>">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'youtube' );?>">YouTube URL</label>
            <input class="widefat" type="text" id="<?php echo $this->get_field_id( 'youtube' );?>" name="<?php echo $this->get_field_name( 'youtube' );?>" value="<?php echo esc_attr( $youtube ); ?>">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'email' );?>">Email Address</label>
            <input class="widefat" type="text" id="<?php echo $this->get_field_id( 'email' );?>" name="<?php echo $this->get_field_name( 'email' );?>" value="<?php echo esc_attr( $email ); ?>">
        </p>
    <?php
    }

    function update( $new_instance, $old_instance ) {
        $instance = $old_instance;
        $instance['facebook'] = $new_instance['facebook'];
        $instance['twitter'] = $new_instance['twitter'];
        $instance['googleplus'] = $new_instance['googleplus'];
        $instance['instagram'] = $new_instance['instagram'];
        $instance['flickr'] = $new_instance['flickr'];
        $instance['pinterest'] = $new_instance['pinterest'];
        $instance['linkedin'] = $new_instance['linkedin'];
        $instance['youtube'] = $new_instance['youtube'];
        $instance['email'] = $new_instance['email'];
        return $instance;
    }

    function widget( $args, $instance ) {
        extract( $args );
        echo $before_widget;
        $output = '<ul class="social-icons menu simple">';
        if (!empty($instance['facebook'])) {
            $output .= '<li class="facebook-link">';
            $output .= '<a href="' . $instance['facebook'] . '" target="_blank" class="fa fa-facebook">';
            $output .= '<span class="hide">' . __('Facebook') . '</span>';
            $output .= '</a>';
            $output .= '</li>';
        }
        if (!empty($instance['twitter'])) {
            $output .= '<li class="twitter-link">';
            $output .= '<a href="' . $instance['twitter'] . '" target="_blank" class="fa fa-twitter">';
            $output .= '<span class="hide">' . __('Twitter') . '</span>';
            $output .= '</a>';
            $output .= '</li>';
        }
        if (!empty($instance['googleplus'])) {
            $output .= '<li class="googleplus-link">';
            $output .= '<a href="' . $instance['googleplus'] . '" target="_blank" class="fa fa-googleplus">';
            $output .= '<span class="hide">' . __('Google+') . '</span>';
            $output .= '</a>';
            $output .= '</li>';
        }
        if (!empty($instance['instagram'])) {
            $output .= '<li class="instagram-link">';
            $output .= '<a href="' . $instance['instagram'] . '" target="_blank" class="fa fa-instagram">';
            $output .= '<span class="hide">' . __('Instagram') . '</span>';
            $output .= '</a>';
            $output .= '</li>';
        }
        if (!empty($instance['flickr'])) {
            $output .= '<li class="flickr-link">';
            $output .= '<a href="' . $instance['flickr'] . '" target="_blank" class="fa fa-flickr">';
            $output .= '<span class="hide">' . __('Flickr') . '</span>';
            $output .= '</a>';
            $output .= '</li>';
        }
        if (!empty($instance['pinterest'])) {
            $output .= '<li class="pinterest-link">';
            $output .= '<a href="' . $instance['pinterest'] . '" target="_blank" class="fa fa-pinterest">';
            $output .= '<span class="hide">' . __('Pinterest') . '</span>';
            $output .= '</a>';
            $output .= '</li>';
        }
        if (!empty($instance['linkedin'])) {
            $output .= '<li class="linkedin-link">';
            $output .= '<a href="' . $instance['linkedin'] . '" target="_blank" class="fa fa-linkedin">';
            $output .= '<span class="hide">' . __('LinkedIn') . '</span>';
            $output .= '</a>';
            $output .= '</li>';
        }
        if (!empty($instance['youtube'])) {
            $output .= '<li class="youtube-link">';
            $output .= '<a href="' . $instance['youtube'] . '" target="_blank" class="fa fa-youtube-play">';
            $output .= '<span class="hide">' . __('Youtube') . '</span>';
            $output .= '</a>';
            $output .= '</li>';
        }
        if (!empty($instance['email'])) {
            $output .= '<li class="email-link">';
            $output .= '<a href="mailto:' . $instance['email'] . '" target="_blank" class="fa fa-envelope">';
            $output .= '<span class="hide">' . __('Email') . '</span>';
            $output .= '</a>';
            $output .= '</li>';
        }
        $output .= '</ul>';
        echo $output;
        echo $after_widget;
    }

}

function social_widgets_register() {
    register_widget( 'Social_Follow_Widget' );
}
add_action( 'widgets_init', 'social_widgets_register' );
