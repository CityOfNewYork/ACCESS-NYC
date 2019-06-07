<?php

if( !function_exists('pmai_get_join_attr') ):

    /**
     * @param bool $attributes
     * @return string
     */
    function pmai_get_join_attr($attributes = false )
    {
        // validate
        if( empty($attributes) )
        {
            return '';
        }


        // vars
        $e = array();


        // loop through and render
        foreach( $attributes as $k => $v )
        {
            $e[] = $k . '="' . esc_attr( $v ) . '"';
        }


        // echo
        return implode(' ', $e);
    }

endif;

if( !function_exists('pmai_join_attr') ):
    /**
     * @param bool $attributes
     */
    function pmai_join_attr($attributes = false ){
        echo pmai_get_join_attr( $attributes );
    }

endif;