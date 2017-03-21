<?php

final class NF_Display_Shortcodes
{
    public function __construct()
    {
        add_shortcode( 'nf_preview',  array( $this, 'display_form_preview' ) );
        add_shortcode( 'ninja_form',  array( $this, 'display_form_front_end' ) );
        add_shortcode( 'ninja_forms', array( $this, 'display_form_front_end' ) );
        add_shortcode( 'ninja_forms_display_form', array( $this, 'display_form_front_end' ) );
    }

    public function display_form_preview( $atts = array() )
    {
        if( ! isset( $atts[ 'id' ] ) ) return $this->display_no_id();

        ob_start();
        Ninja_Forms()->display( $atts['id'], TRUE );
        return ob_get_clean();
    }

    public function display_form_front_end( $atts = array() )
    {
        if( ! isset( $atts[ 'id' ] ) ) return $this->display_no_id();

        ob_start();
        Ninja_Forms()->display( $atts['id'] );
        return ob_get_clean();
    }

    /**
     * TODO: Extract output to template files.
     * @return string
     */
    private function display_no_id()
    {
        $output = __( 'Notice: Ninja Forms shortcode used without specifying a form.', 'ninja-forms' );

        // TODO: Maybe support filterable permissions.
        if( ! current_user_can( 'manage_options' ) ) return "<!-- $output -->";

        // TODO: Log error for support reference.
        // TODO: Maybe display notice if not logged in.
        trigger_error( __( 'Ninja Forms shortcode used without specifying a form.', 'ninja-forms' ) );

        return "<div style='border: 3px solid red; padding: 1em; margin: 1em auto;'>$output</div>";
    }
}
