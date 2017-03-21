<?php
/**
 * Add a button to tinyMCE editors when eidting posts/pages.
 *
 * @since 2.9.22
 */

class NF_Admin_AddFormModal {
    
    function __construct() {
        // Add a tinyMCE button to our post and page editor
        add_filter( 'media_buttons_context', array( $this, 'insert_form_tinymce_buttons' ) );
    }

    /**
     * Output our tinyMCE field buttons
     *
     * @access public
     * @since 2.8
     * @return void
     */
    public function insert_form_tinymce_buttons( $context ) {
        global $pagenow;

        if ( 'post.php' != $pagenow ) {
            return $context;
        }
        $html = '<style>
            span.nf-insert-form {
                color:#888;
                font: 400 18px/1 dashicons;
                -webkit-font-smoothing: antialiased;
                -moz-osx-font-smoothing: grayscale;
                display: inline-block;
                width: 18px;
                height: 18px;
                vertical-align: text-top;
                margin: 0 2px 0 0;
            }
        </style>';
        $html .= '<a href="#" class="button-secondary nf-insert-form"><span class="nf-insert-form dashicons dashicons-feedback"></span> ' . __( 'Add Form', 'ninja-forms' ) . '</a>';

        wp_enqueue_script( 'nf-combobox', Ninja_Forms::$url . 'assets/js/lib/combobox.min.js', array( 'jquery', 'jquery-ui-core', 'jquery-ui-button', 'jquery-ui-autocomplete' ) );
        wp_enqueue_script( 'jBox', Ninja_Forms::$url . 'assets/js/lib/jBox.min.js', array( 'jquery' ) );

        wp_enqueue_style( 'nf-combobox', Ninja_Forms::$url . 'assets/css/combobox.css' );
        wp_enqueue_style( 'jBox', Ninja_Forms::$url . 'assets/css/jBox.css' );

        // wp_enqueue_style( 'jquery-smoothness', NINJA_FORMS_URL .'css/smoothness/jquery-smoothness.css' );
        ?>
         <div id="nf-insert-form-modal" style="display:none;">
            <p>
                <?php
                $all_forms = Ninja_Forms()->form()->get_forms();
                $first_option = __( 'Select a form or type to search', 'ninja-forms' );
                echo '<select class="nf-forms-combobox" id="nf-form-select" data-first-option="">';
                echo '<option value=""></option>';
                foreach( $all_forms as $form ) {
                    // $form = Ninja_Forms()->form( $form_id )->get();
                    $label = $form->get_setting( 'title' );
                    $form_id = $form->get_id();
                    if ( strlen( $label ) > 30 )
                        $label = substr( $label, 0, 30 ) . '...';

                    echo '<option value="' . $form_id . '">' . $label . ' - ID: ' . $form_id . '</option>';
                }
                echo '</select>';
                ?>
            </p>
            <p>
                <input type="button" id="nf-insert-form" class="button-primary" value="Insert" />
            </p>
        </div>
        <?php
        add_action( 'admin_footer', array( $this, 'output_tinymce_button_js' ) );
        return $context . ' ' . $html;
    }

    /**
     * Output our tinyMCE field buttons
     *
     * @access public
     * @since 2.8
     * @return void
     */
    public function output_tinymce_button_js( $context ) {
        ?>
        <script type="text/javascript">
            jQuery( document ).ready( function( $ ) {
                var jBox = jQuery( '.nf-insert-form' ).jBox( 'Modal', {
                    title: 'Insert Form',
                    position: {
                        x: 'center',
                        y: 'center'
                    },
                    closeButton: 'title',
                    closeOnClick: 'overlay',
                    closeOnEsc: true,
                    // theme: 'TooltipBorder',
                    content: jQuery( '#nf-insert-form-modal' ),
                    onOpen: function() {
                        jQuery( '.nf-forms-combobox' ).combobox();
                        jQuery( this )[0].content.find( '.ui-autocomplete-input' ).attr( 'placeholder', 'Select a form or type to search' );
                        jQuery( this )[0].content.css( 'overflow', 'visible' );
                        jQuery( this )[0].content.find( '.ui-icon-triangle-1-s' ).addClass( 'dashicons dashicons-arrow-down' ).css( 'margin-left', '-7px' );
                    },
                    onClose: function() {
                        jQuery( '.nf-forms-combobox' ).combobox( 'destroy'  );
                    }
                });

                jQuery( document ).on( 'click', '#nf-insert-form', function( e ) {
                    e.preventDefault();
                    var form_id = jQuery( '#nf-form-select' ).val();
                    var shortcode = '[ninja_form id=' + form_id + ']';
                    window.parent.send_to_editor( shortcode );
                    jBox.close();
                    jQuery( '#nf-form-select' ).val( '' );
                } );
            } );
        </script>

        <?php
    }
}