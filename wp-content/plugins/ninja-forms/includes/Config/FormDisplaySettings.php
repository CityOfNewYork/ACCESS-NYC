<?php if ( ! defined( 'ABSPATH' ) ) exit;

return apply_filters( 'ninja_forms_form_display_settings', array(

    /*
    * FORM TITLE
    */

    'title' => array(
        'name' => 'title',
        'type' => 'textbox',
        'label' => __( 'Form Title', 'ninja-forms' ),
        'width' => 'full',
        'group' => 'primary',
        'value' => '',

    ),

    /*
    * SHOW FORM TITLE
    */

    'show_title' => array(
        'name' => 'show_title',
        'type' => 'toggle',
        'label' => __( 'Display Form Title', 'ninja-forms' ),
        'width' => 'full',
        'group' => 'primary',
        'value' => 1,

    ),

    /*
    * CLEAR SUCCESSFULLY COMPLETED FORM
    */

    'clear_complete' => array(
        'name' => 'clear_complete',
        'type' => 'toggle',
        'label' => __( 'Clear successfully completed form?', 'ninja-forms' ),
        'width' => 'full',
        'group' => 'primary',
        'value' => 1,
        'help'  => __( 'If this box is checked, Ninja Forms will clear the form values after it has been successfully submitted.', 'ninja-forms' ),
    ),

    /*
    * HIDE SUCCESSFULLY COMPLETED FORMS
    */

    'hide_complete' => array(
        'name' => 'hide_complete',
        'type' => 'toggle',
        'label' => __( 'Hide successfully completed form?', 'ninja-forms' ),
        'width' => 'full',
        'group' => 'primary',
        'value' => 1,
        'help'  => __( 'If this box is checked, Ninja Forms will hide the form after it has been successfully submitted.', 'ninja-forms' ),
    ),

    /*
    * Default Label Position
    */

    'default_label_pos' => array(
        'name' => 'default_label_pos',
        'type' => 'select',
        'label' => __( 'Default Label Position', 'ninja-forms' ),
        'width' => 'full',
        'group' => 'advanced',
        'options' => array(
            array(
                'label' => __( 'Above Element', 'ninja-forms' ),
                'value' => 'above'
            ),
            array(
                'label' => __( 'Below Element', 'ninja-forms' ),
                'value' => 'below'
            ),
            array(
                'label' => __( 'Left of Element', 'ninja-forms' ),
                'value' => 'left'
            ),
            array(
                'label' => __( 'Right of Element', 'ninja-forms' ),
                'value' => 'right'
            ),
            array(
                'label' => __( 'Hidden', 'ninja-forms' ),
                'value' => 'hidden'
            ),
        ),
        'value' => 'above',
    ),

    /*
     * Classes
     */

    'classes' => array(
        'name' => 'classes',
        'type' => 'fieldset',
        'label' => __( 'Custom Class Names', 'ninja-forms' ),
        'width' => 'full',
        'group' => 'advanced',
        'settings' => array(
            array(
                'name' => 'wrapper_class',
                'type' => 'textbox',
                'placeholder' => '',
                'label' => __( 'Wrapper', 'ninja-forms' ),
                'width' => 'one-half',
                'value' => '',
                'use_merge_tags' => FALSE,
            ),
            array(
                'name' => 'element_class',
                'type' => 'textbox',
                'label' => __( 'Element', 'ninja-forms' ),
                'placeholder' => '',
                'width' => 'one-half',
                'value' => '',
                'use_merge_tags' => FALSE,
            ),
        ),
    ),

    /*
     * KEY
     */

    'key' => array(
        'name' => 'key',
        'type' => 'textbox',
        'label' => __( 'Form Key', 'ninja-forms'),
        'width' => 'full',
        'group' => 'administration',
        'value' => '',
        'help' => __( 'Programmatic name that can be used to reference this form.', 'ninja-forms' ),
    ),

    /*
     * ADD SUBMIT CHECKBOX
     */

    'add_submit' => array(
        'name' => 'add_submit',
        'type' => 'toggle',
        'label' => __( 'Add Submit Button', 'ninja-forms'),
        'width' => 'full',
        'group' => '',
        'value' => 1,
        'help' => __( 'We\'ve noticed that don\'t have a submit button on your form. We can add one for your automatically.', 'ninja-forms' ),
    ),

    /*
     * Form Labels
     */

    'custom_messages' => array(
        'name' => 'custom_messages',
        'type' => 'fieldset',
        'label' => __( 'Custom Labels', 'ninja-forms' ),
        'width' => 'full',
        'group' => 'advanced',
        'settings' => array(
            array(
                'name' => 'changeEmailErrorMsg',
                'type' => 'textbox',
                'label' => __( 'Please enter a valid email address!', 'ninja-forms' ),
                'width' => 'full'
            ),
            array(
                'name' => 'confirmFieldErrorMsg',
                'type' => 'textbox',
                'label' => __( 'These fields must match!', 'ninja-forms' ),
                'width' => 'full'
            ),
            array(
                'name' => 'fieldNumberNumMinError',
                'type' => 'textbox',
                'label' => __( 'Number Min Error', 'ninja-forms' ),
                'width' => 'full'
            ),
            array(
                'name' => 'fieldNumberNumMaxError',
                'type' => 'textbox',
                'label' => __( 'Number Max Error', 'ninja-forms' ),
                'width' => 'full'
            ),
            array(
                'name' => 'fieldNumberIncrementBy',
                'type' => 'textbox',
                'label' => __( 'Please increment by ', 'ninja-forms' ),
                'width' => 'full'
            ),
            array(
                'name' => 'formErrorsCorrectErrors',
                'type' => 'textbox',
                'label' => __( 'Please correct errors before submitting this form.', 'ninja-forms' ),
                'width' => 'full'
            ),
            array(
                'name' => 'validateRequiredField',
                'type' => 'textbox',
                'label' => __( 'This is a required field.', 'ninja-forms' ),
                'width' => 'full'
            ),
            array(
                'name' => 'honeypotHoneypotError',
                'type' => 'textbox',
                'label' => __( 'Honeypot Error', 'ninja-forms' ),
                'width' => 'full'
            ),
            array(
                'name' => 'fieldsMarkedRequired',
                'type' => 'textbox',
                'label' => sprintf( __( 'Fields marked with an %s*%s are required', 'ninja-forms' ), '<span class="ninja-forms-req-symbol">', '</span>' ),
                'width' => 'full'
            ),
        )
    ),

    /*
     * CURRENCY
     */

    'currency' => array(
        'name'      => 'currency',
        'type'    => 'select',
        'options' => array_merge( array( array( 'label' => __( 'Plugin Default', 'ninja-forms' ), 'value' => '' ) ), Ninja_Forms::config( 'Currency' ) ),
        'label'   => __( 'Currency', 'ninja-forms' ),
        'width' => 'full',
        'group' => 'advanced',
        'value'   => ''
    ),

));
