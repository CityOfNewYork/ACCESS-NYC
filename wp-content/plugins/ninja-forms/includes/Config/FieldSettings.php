<?php if ( ! defined( 'ABSPATH' ) ) exit;

return apply_filters( 'ninja_forms_field_settings', array(

    /*
    |--------------------------------------------------------------------------
    | Primary Settings
    |--------------------------------------------------------------------------
    |
    | The most commonly used settings for a field.
    |
    */

    /*
     * LABEL
     */

    'label' => array(
        'name' => 'label',
        'type' => 'textbox',
        'label' => __( 'Label', 'ninja-forms'),
        'width' => 'one-half',
        'group' => 'primary',
        'value' => '',
        'help' => __( 'Enter the label of the form field. This is how users will identify individual fields.', 'ninja-forms' ),
    ),

    /*
     * LABEL POSITION
     */

    'label_pos' => array(
        'name' => 'label_pos',
        'type' => 'select',
        'label' => __( 'Label Position', 'ninja-forms' ),
        'options' => array(
            array(
                'label' => __( 'Form Default', 'ninja-forms' ),
                'value' => 'default'
            ),
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
        'width' => 'one-half',
        'group' => 'primary',
        'value' => 'default',
        'help' => __( 'Select the position of your label relative to the field element itself.', 'ninja-forms' ),

    ),

    /*
     * REQUIRED
     */

    'required' => array(
        'name' => 'required',
        'type' => 'toggle',
        'label' => __( 'Required Field', 'ninja-forms' ),
        'width' => 'one-half',
        'group' => 'primary',
        'value' => FALSE,
        'help' => __( 'Ensure that this field is completed before allowing the form to be submitted.', 'ninja-forms' ),
    ),

    /*
     * NUMBER
     */

    'number' => array(
        'name' => 'number',
        'type' => 'fieldset',
        'label' => __( 'Number Options', 'ninja-forms' ),
        'width' => 'full',
        'group' => 'primary',
        'settings' => array(
            array(
                'name' => 'num_min',
                'type' => 'number',
                'placeholder' => '',
                'label' => __( 'Min', 'ninja-forms' ),
                'width' => 'one-third',
                'value' => ''
            ),
            array(
                'name' => 'num_max',
                'type' => 'number',
                'label' => __( 'Max', 'ninja-forms' ),
                'placeholder' => '',
                'width' => 'one-third',
                'value' => ''
            ),
            array(
                'name' => 'num_step',
                'type' => 'textbox',
                'label' => __( 'Step', 'ninja-forms' ),
                'placeholder' => '',
                'width' => 'one-third',
                'value' => 1
            ),
        ),

    ),

    /*
     * Checkbox Default Value
     */

    'checkbox_default_value' => array(
        'name' => 'default_value',
        'type' => 'select',
        'label' => __( 'Default Value', 'ninja-forms' ),
        'options' => array(
            array(
                'label' => __( 'Unchecked', 'ninja-forms' ),
                'value' => 'unchecked'
            ),
            array(
                'label' => __( 'Checked', 'ninja-forms'),
                'value' => 'checked',
            ),
        ),
        'width' => 'one-half',
        'group' => 'primary',
        'value' => 'unchecked',

    ),

    /*
     * OPTIONS
     */

    'options' => array(
        'name' => 'options',
        'type' => 'option-repeater',
        'label' => __( 'Options', 'ninja-forms' ) . ' <a href="#" class="nf-add-new">' . __( 'Add New', 'ninja-forms' ) . '</a>',
        'width' => 'full',
        'group' => 'primary',
        // 'value' => 'option-repeater',
        'value' => array(
            array( 'label'  => __( 'One', 'ninja-forms' ), 'value' => __( 'one', 'ninja-forms' ), 'calc' => '', 'selected' => 0, 'order' => 0 ),
            array( 'label'  => __( 'Two', 'ninja-forms' ), 'value' => __( 'two', 'ninja-forms' ), 'calc' => '', 'selected' => 0, 'order' => 1 ),
            array( 'label'  => __( 'Three', 'ninja-forms' ), 'value' => __( 'three', 'ninja-forms' ), 'calc' => '', 'selected' => 0, 'order' => 2 ),
        ),
        'columns'           => array(
           'label'          => array(
                'header'    => __( 'Label', 'ninja-forms' ),
                'default'   => '',
            ),

            'value'         => array(
                'header'    => __( 'Value', 'ninja-forms' ),
                'default'   => '',
            ),
            'calc'          => array(
                'header'    =>__( 'Calc Value', 'ninja-forms' ),
                'default'   => '',
            ),
            'selected'      => array(
                'header'    => '<span class="dashicons dashicons-yes"></span>',
                'default'   => 0,
            ),
        ),

    ),

    /*
    |--------------------------------------------------------------------------
    | Restriction Settings
    |--------------------------------------------------------------------------
    |
    | Limit the behavior or validation of an input.
    |
    */

    /*
     * MASK
     */

    'mask' => array(
        'name' => 'mask',
        'type' => 'select',
        'label' => __( 'Input Mask', 'ninja-forms'),
        'width' => 'one-half',
        'group' => 'restrictions',
        'help'  => __( 'Restricts the kind of input your users can put into this field.', 'ninja-forms' ),
        'options' => array(
            array(
                'label' => __( 'none', 'ninja-forms' ),
                'value' => ''
            ),
            array(
                'label' => __( 'US Phone', 'ninja-forms' ),
                'value' => '(999) 999-9999',
            ),
            array(
                'label' => __( 'Date', 'ninja-forms' ),
                'value' => '99/99/9999',
            ),
            array(
                'label' => __( 'Custom', 'ninja-forms' ),
                'value' => 'custom',
            ),
        ),
        'value' => '',
    ),
    /*
     * CUSTOM MASK
     */

    'custom_mask'       => array(
        'name'          => 'custom_mask',
        'type'          => 'textbox',
        'label'         => __( 'Custom Mask', 'ninja-forms'),
        'width'         => 'one-half',
        'group'         => 'restrictions',
        'value'         => '',

        'deps'          => array(
            'mask'      => 'custom'
        ),
        'placeholder'   => '',
        'help'  => __( '<ul>
                            <li>a - Represents an alpha character (A-Z,a-z) - Only allows letters to be entered. </li>
                            <li>9 - Represents a numeric character (0-9) - Only allows numbers to be entered.</li>
                            <li>* - Represents an alphanumeric character (A-Z,a-z,0-9) - This allows both numbers and
                            letters to be entered.</li>
                        </ul>', 'ninja-forms' ),
    ),

    /*
     * INPUT LIMIT SET
     */

    'input_limit_set' => array(
        'name' => 'input_limit_set',
        'type' => 'fieldset',
        'label' => __( 'Limit Input to this Number', 'ninja-forms' ),
        'width' => 'full',
        'group' => 'restrictions',
        'settings' => array(
            array(
                'name' => 'input_limit',
                'type' => 'textbox',
                'width' => 'one-half',
                'value' => '',
                'label' => '',
            ),
            array(
                'name' => 'input_limit_type',
                'type' => 'select',
                'options' => array(
                    array(
                        'label' => __( 'Character(s)', 'ninja-forms' ),
                        'value' => 'char'
                    ),
                    array(
                        'label' => __( 'Word(s)', 'ninja-forms' ),
                        'value' => 'word'
                    ),
                ),
                'value' => 'characters',
                'label' => '',
            ),
            array(
                'name' => 'input_limit_msg',
                'type' => 'textbox',
                'label' => __( 'Text to Appear After Counter', 'ninja-forms' ),
                'placeholder' => __( 'Character(s) left' ),
                'width' => 'full',
                'value' => __( 'Character(s) left', 'ninja-forms' )
            )
        ),

    ),

    /*
    |--------------------------------------------------------------------------
    | Advanced Settings
    |--------------------------------------------------------------------------
    |
    | The least commonly used settings for a field.
    | These settings should only be used for specific reasons.
    |
    */

    /*
     * INPUT PLACEHOLDER
     */

    'placeholder' => array(
        'name' => 'placeholder',
        'type' => 'textbox',
        'label' => __( 'Placeholder', 'ninja-forms' ),
        'width' => 'full',
        'group' => 'advanced',
        'value' => '',
        'help' => __( 'Enter text you would like displayed in the field before a user enters any data.', 'ninja-forms' ),
        'use_merge_tags' => FALSE,
    ),


    /*
     * DEFAULT VALUE
     */

     'default' => array(
         'name' => 'default',
         'label' => __( 'Default Value', 'ninja-forms' ),
         'type' => 'textbox',
         'width' => 'full',
         'value' => '',
         'group' => 'advanced',
         'use_merge_tags' => array(
             'exclude' => array(
                 'fields'
             )
         ),
     ),

    /*
    * CLASSES
    */
    'classes' => array(
        'name' => 'classes',
        'type' => 'fieldset',
        'label' => __( 'Custom Class Names', 'ninja-forms' ),
        'width' => 'full',
        'group' => 'display',
        'settings' => array(
            array(
                'name' => 'container_class',
                'type' => 'textbox',
                'placeholder' => '',
                'label' => __( 'Container', 'ninja-forms' ),
                'width' => 'one-half',
                'value' => '',
                'use_merge_tags' => FALSE,
                'help' => __( 'Adds an extra class to your field wrapper.', 'ninja-forms' ),
            ),
            array(
                'name' => 'element_class',
                'type' => 'textbox',
                'label' => __( 'Element', 'ninja-forms' ),
                'placeholder' => '',
                'width' => 'one-half',
                'value' => '',
                'use_merge_tags' => FALSE,
                'help' => __( 'Adds an extra class to your field element.', 'ninja-forms' ),
            ),
        ),
    ),

    /*
     * DATE FORMAT
     */

    'date_format'        => array(
        'name'          => 'date_format',
        'type'          => 'select',
        'label'         => __( 'Format', 'ninja-forms' ),
        'width'         => 'full',
        'group'         => 'primary',
        'options'       => array(
            array(
                'label' => __( 'DD/MM/YYYY', 'ninja-forms' ),
                'value' => 'DD/MM/YYYY',
            ),
            array(
                'label' => __( 'DD-MM-YYYY', 'ninja-forms' ),
                'value' => 'DD-MM-YYYY',
            ),
            array(
                'label' => __( 'DD.MM.YYYY', 'ninja-forms' ),
                'value' => 'DD.MM.YYYY',
            ),
            array(
                'label' => __( 'MM/DD/YYYY', 'ninja-forms' ),
                'value' => 'MM/DD/YYYY',
            ),
            array(
                'label' => __( 'MM-DD-YYYY', 'ninja-forms' ),
                'value' => 'MM-DD-YYYY',
            ),
            array(
                'label' => __( 'MM.DD.YYYY', 'ninja-forms' ),
                'value' => 'MM.DD.YYYY',
            ),
            array(
                'label' => __( 'YYYY-MM-DD', 'ninja-forms' ),
                'value' => 'YYYY-MM-DD',
            ),
            array(
                'label' => __( 'YYYY/MM/DD', 'ninja-forms' ),
                'value' => 'YYYY/MM/DD',
            ),
            array(
                'label' => __( 'YYYY.MM.DD', 'ninja-forms' ),
                'value' => 'YYYY.MM.DD',
            ),
            array(
                'label' => __( 'Friday, November 18, 2019', 'ninja-forms' ),
                'value' => 'dddd, MMMM D YYYY',
            ),
        ),
        'value'         => 'DD/MM/YYYY',
    ),

    /*
     * DATE DEFAULT
     */

    'date_default'       => array(
        'name'          => 'date_default',
        'type'          => 'toggle',
        'label'         => __( 'Default To Current Date', 'ninja-forms' ),
        'width'         => 'one-half',
        'group'         => 'primary'
    ),

    /*
     * Year Range
     */

    'year_range' => array(
        'name' => 'year_range',
        'type' => 'fieldset',
        'label' => __( 'Year Range', 'ninja-forms' ),
        'width' => 'full',
        'group' => 'advanced',
        'settings' => array(
            array(
                'name' => 'year_range_start',
                'type' => 'number',
                'label' => __( 'Start Year', 'ninja_forms' ),
                'value' => ''
            ),
            array(
                'name' => 'year_range_end',
                'type' => 'number',
                'label' => __( 'End Year', 'ninja_forms' ),
                'value' => ''
            ),
        )
    ),

    /*
     * TIME SETTING
     */

    'time_submit' => array(
        'name' => 'time_submit',
        'type' => 'textbox',
        'label' => __( 'Number of seconds for timed submit.', 'ninja-forms' ),
        'width' => 'full',
        'group' => 'advanced',
        'value' => FALSE,

    ),

    /*
     * KEY
     */

    'key' => array(
        'name' => 'key',
        'type' => 'textbox',
        'label' => __( 'Field Key', 'ninja-forms'),
        'width' => 'full',
        'group' => 'administration',
        'value' => '',
        'help' => __( 'Creates a unique key to identify and target your field for custom development.', 'ninja-forms' ),
    ),

    /*
     * ADMIN LABEL
     */

    'admin_label'           => array(
        'name'              => 'admin_label',
        'type'              => 'textbox',
        'label'             => __( 'Admin Label', 'ninja-forms' ),
        'width'             => 'full',
        'group'             => 'administration',
        'value'             => '',
        'help'              => __( 'Label used when viewing and exporting submissions.', 'ninja-forms' ),
    ),

    /*
     * HELP
     */

    'help'           => array(
        'name'              => 'help',
        'type'              => 'fieldset',
        'label'             => __( 'Help Text', 'ninja-forms' ),
        'group'             => 'display',
        'help'              => __( 'Shown to users as a hover.', 'ninja-forms' ),
        'settings'          => array(
            /*
             * HELP TEXT
             */

            'help_text'             => array(
                'name'              => 'help_text',
                'type'              => 'rte',
                'label'             => '',
                'width'             => 'full',
                'group'             => 'advanced',
                'value'             => '',
                'use_merge_tags'    => true,
            ),
        ),
    ),


    /*
     * DESCRIPTION
     */
    'description'           => array(
        'name'              => 'description',
        'type'              => 'fieldset',
        'label'             => __( 'Description', 'ninja-forms' ),
        'group'             => 'display',
        'settings'          => array(
            /*
             * DESCRIPTION TEXT
             */

            'desc_text'           => array(
                'name'              => 'desc_text',
                'type'              => 'rte',
                'label'             => '',
                'width'             => 'full',
                'use_merge_tags'    => true,
            ),

            /*
             * DESCRIPTION POSITION
             */

            // 'desc_pos'              => array(
            //     'name'              => 'desc_pos',
            //     'type'              => 'select',
            //     'options'           => array(
            //         array(
            //             'label'     => __( 'None', 'ninja-forms' ),
            //             'value'     => 'none',
            //         ),
            //         array(
            //             'label'     => __( 'Before Everything', 'ninja-forms' ),
            //             'value'     => 'before_everything',
            //         ),
            //         array(
            //             'label'     => __( 'Before Label', 'ninja-forms' ),
            //             'value'     => 'before_label',
            //         ),
            //         array(
            //             'label'     => __( 'After Label', 'ninja-forms' ),
            //             'value'     => 'after_label',
            //         ),
            //         array(
            //             'label'     => __( 'After Everything', 'ninja-forms' ),
            //             'value'     => 'after_everything',
            //         ),
            //     ),
            //     'label'             => __( 'Display Position', 'ninja-forms' ),
            //     'width'             => 'full',
            //     'help'      => __( 'Determines the position of the label relative to the field element.', 'ninja-forms' ),
            // ),

        ),
    ),

    /*
     * NUMERIC SORT
     */

    'num_sort' => array(
        'name' => 'num_sort',
        'type' => 'toggle',
        'label' => __( 'Sort as Numeric', 'ninja-forms'),
        'width' => 'full',
        'group' => 'administration',
        'value' => '',
        'help' => __( 'This column in the submissions table will sort by number.', 'ninja-forms' ),
    ),

    /*
    |--------------------------------------------------------------------------
    | Un-Grouped Settings
    |--------------------------------------------------------------------------
    |
    | Hidden from grouped listings, but still searchable.
    |
    */

    'manual_key' => array(
        'name' => 'manual_key',
        'type' => 'bool',
        'value' => FALSE,
    ),

   /*
    * Timed Submit Label
    */

   // 'timed_submit' => array(
   //      'name' => 'timed_submit',
   //      'type' => 'fieldset',
   //      'label' => __( 'Timed Submit', 'ninja-forms' ),
   //      'width' => 'full',
   //      'group' => 'advanced',
   //      'settings' => array(
   //          array(
   //              'name' => 'timed_submit_countdown',
   //              'type' => 'number',
   //              'label' => __( 'Countdown', 'ninja-forms' ),
   //              'value' => 10,
   //              'placeholder' => '',
   //              'width' => 'one-half',
   //
   //          ),
   //          array(
   //              'name' => 'timed_submit_label',
   //              'type' => 'textbox',
   //              'placeholder' => '',
   //              'label' => __( 'Timer Label', 'ninja-forms' ),
   //              'value' => __( 'Please wait %n seconds', 'ninja-forms' ),
   //              'width' => 'one-half'
   //
   //          ),
   //      ),
   //  ),

                'timed_submit_label' => array(
                    'name' => 'timed_submit_label',
                    'type' => 'textbox',
                    'label' => __( 'Label', 'ninja-forms' ),
                    //The following text appears below the element
                    //'Submit button text after timer expires'
                    'width' => '',
                    'group' => '',
                    'value' => '',
                    'use_merge_tags' => TRUE,
                ),

               /*
                * Timed Submit Timer
                */

                'timed_submit_timer' => array(
                    'name' => 'timed_submit_timer',
                    'type' => 'textbox',
                    'label' => __( 'Label' , 'ninja-forms' ),
                    // This text was located below the element '%n will be used to signfify the number of seconds'
                    'value' => __( 'Please wait %n seconds', 'ninja-forms' ),
                    'width' => '',
                    'group' => '',

                ),

               /*
                * Timed Submit Countdown
                */

                'timed_submit_countdown' => array (
                    'name' => 'timed_submit_countdown',
                    'type' => 'number',
                    'label' => __( 'Number of seconds for the countdown', 'ninja-forms' ),
                    //The following text appears to the right of the element
                    //"This is how long the user must waitin to submit the form"
                    'value' => 10,
                    'width' => '',
                    'group' => '',

                ),

   /*
    * Password Registration checkbox
    */

    'password_registration_checkbox' => array(
        'name' => 'password_registration_checkbox',
        'type' => 'checkbox',
        'value' => 'unchecked',
        'label' => __( 'Use this as a reistration password field. If this box is check, both
                        password and re-password textboxes will be output', 'ninja-forms' ),
        'width' => '',
        'group' => '',

    ),


   /*
    * Number of Stars Textbox
    */

    'number_of_stars' => array(
        'name' => 'number_of_stars',
        'type' => 'textbox',
        'value' => 5,
        'label' => __( 'Number of stars', 'ninja-forms' ),
        'width' => '',
        'group' => '',

    ),

   /*
    * Disable Browser Autocomplete
    */

    'disable_browser_autocomplete' => array(
        'name' => 'disable_browser_autocomplete',
        'type' => 'toggle',
        'label' => __( 'Disable Browser Autocomplete', 'ninja-forms' ),
        'width' => 'full',
        'group' => 'restrictions',
    ),

    /*
     * Disable input
     */

    'disable_input' => array(
        'name'      => 'disable_input',
        'type'      => 'toggle',
        'label'     => __( 'Disable Input', 'ninja-forms' ),
        'width'     => 'full',
        'group'     => 'restrictions',
    ),

    //TODO: Ask about the list of states and countries.
   /*
    *  Country - Use Custom First Option
    */

    'use_custom_first_option' => array(
        'name' => 'use_custom_first_option',
        'type' => 'checkbox',
        'value' => 'unchecked',
        'label' => __( 'Use a custom first option', 'ninja-forms' ),
        'width' => '',
        'group' => '',

    ),

   /*
    * Country - Custom first option
    */

    'custom_first_option' => array(
        'name' => 'custom_first_option',
        'type' => 'textbox',
        'label' => __( 'Custom first option', 'ninja-forms' ),
        'width' => '',
        'group' => '',
        'value' => FALSE,

    ),

    'type'         => array(
        'name'              => 'type',
        'type'              => 'select',
        'options'           => array(),
        'label'             => __( 'Type', 'ninja-forms' ),
        'width'             => 'full',
        'group'             => 'primary',
        'value'             => 'single',
    ),

    'fieldset' => array(
        'name' => 'fieldset',
        'type' => 'fieldset',
        'label' => __( 'Settings', 'ninja-forms' ),
        'width' => 'full',
        'group' => 'primary',
        'settings' => array(),
    ),

    'confirm_field' => array(
        'name' => 'confirm_field',
        'type' => 'field-select',
        'label' => __( 'Confirm', 'ninja-forms' ),
        'width' => 'full',
        'group' => 'advanced'
    ),

    /*
    |--------------------------------------------------------------------------
    | Textarea Settings
    |--------------------------------------------------------------------------
    */

    'textarea_rte'          => array(
        'name'              => 'textarea_rte',
        'type'              => 'toggle',
        'label'             => __( 'Show Rich Text Editor', 'ninja-forms' ),
        'width'             => 'one-third',
        'group'             => 'display',
        'value'             => '',
        'help'              => __( 'Allows rich text input.', 'ninja-forms' ),
    ),

    'textarea_media'          => array(
        'name'              => 'textarea_media',
        'type'              => 'toggle',
        'label'             => __( 'Show Media Upload Button', 'ninja-forms' ),
        'width'             => 'one-third',
        'group'             => 'display',
        'value'             => '',
        'deps'              => array(
            'textarea_rte'  => 1
        )
    ),

    'disable_rte_mobile'    => array(
        'name'              => 'disable_rte_mobile',
        'type'              => 'toggle',
        'label'             => __( 'Disable Rich Text Editor on Mobile', 'ninja-forms' ),
        'width'             => 'one-third',
        'group'             => 'display',
        'value'             => '',
        'deps'              => array(
            'textarea_rte'  => 1
        )
    ),

    /*
    |--------------------------------------------------------------------------
    | Submit Button Settings
    |--------------------------------------------------------------------------
    */

    'processing_label' => array(
        'name' => 'processing_label',
        'type' => 'textbox',
        'label' => __( 'Processing Label', 'ninja-forms' ),
        'width' => 'full',
        'group' => 'primary',
        'value' => __( 'Processing', 'ninja-forms' )
    ),

    /*
    |--------------------------------------------------------------------------
    | Calc Value that is used for checkbox fields
    |--------------------------------------------------------------------------
    */

    'checked_calc_value'    => array(
        'name'      => 'checked_calc_value',
        'type'      => 'textbox',
        'label'     => __( 'Checked Calculation Value', 'ninja-forms' ),
        'width'     => 'one-half',
        'group'     => 'advanced',
        'help'      => __( 'This number will be used in calculations if the box is checked.', 'ninja-forms' ),
    ),

    'unchecked_calc_value'    => array(
        'name'      => 'unchecked_calc_value',
        'type'      => 'textbox',
        'label'     => __( 'Unchecked Calculation Value', 'ninja-forms' ),
        'width'     => 'one-half',
        'group'     => 'advanced',
        'help'      => __( 'This number will be used in calculations if the box is unchecked.', 'ninja-forms' ),
    ),

    /*
    |--------------------------------------------------------------------------
    | DISPLAY CALCULATION SETTINGS
    |--------------------------------------------------------------------------
    */
    'calc_var'              => array(
        'name'              => 'calc_var',
        'type'              => 'select',
        'label'             => __( 'Display This Calculation Variable', 'ninja-forms' ),
        'width'             => 'full',
        'group'             => 'primary',
        'options'           => array(),
        'select_product'    => array(
            'value'         => '',
            'label'         => __( '- Select a Variable', 'ninja-forms' ),
        ),
    ),

    /*
    |--------------------------------------------------------------------------
    | Pricing Fields Settings
    |--------------------------------------------------------------------------
    */

    'product_price' => array(
        'name' => 'product_price',
        'type' => 'textbox',
        'label' => __( 'Price', 'ninja-forms' ),
        'width' => 'one-half',
        'group' => 'primary',
        'value' => '1.00',
        'mask' => array(
            'type' => 'currency', // 'numeric', 'currency', 'custom'
            'options' => array()
        )
    ),

    'product_use_quantity' => array(
        'name' => 'product_use_quantity',
        'type' => 'toggle',
        'label' => __( 'Use Quantity', 'ninja-forms' ),
        'width' => 'one-half',
        'group' => 'primary',
        'value' => TRUE,
        'help'  => __( 'Allows users to choose more than one of this product.', 'ninja-forms' ),

    ),

    'product_type' => array(
        'name' => 'product_type',
        'type' => 'select',
        'label' => __( 'Product Type', 'ninja-forms' ),
        'width' => 'full',
        'group' => '',
        'options' => array(
            array(
                'label' => __( 'Single Product (default)', 'ninja-forms' ),
                'value' => 'single'
            ),
            array(
                'label' => __( 'Multi Product - Dropdown', 'ninja-forms' ),
                'value' => 'dropdown'
            ),
            array(
                'label' => __( 'Multi Product - Choose Many', 'ninja-forms' ),
                'value' => 'checkboxes'
            ),
            array(
                'label' => __( 'Multi Product - Choose One', 'ninja-forms' ),
                'value' => 'radiolist'
            ),
            array(
                'label' => __( 'User Entry', 'ninja-forms' ),
                'value' => 'user'
            ),
            array(
                'label' => __( 'Hidden', 'ninja-forms' ),
                'value' => 'hidden'
            ),
        ),
        'value' => 'single',
        'use_merge_tags' => FALSE
    ),

    'shipping_cost'         => array(
        'name'              => 'shipping_cost',
        'type'              => 'textbox',
        'label'             => __( 'Cost', 'ninja-forms' ),
        'width'             => 'full',
        'group'             => 'primary',
        'value'             => '0.00',
        'mask' => array(
            'type' => 'currency', // 'numeric', 'currency', 'custom'
            'options' => array()
        ),
        'deps'              => array(
            'shipping_type' => 'single',
        ),
    ),

    'shipping_options'      => array(
        'name'              => 'shipping_options',
        'type'              => 'option-repeater',
        'label'             => __( 'Cost Options', 'ninja-forms' ) . ' <a href="#" class="nf-add-new">' . __( 'Add New', 'ninja-forms' ) . '</a>',
        'width'             => 'full',
        'group'             => 'primary',
        'value'             => array(
            array( 'label'  => __( 'One', 'ninja-forms' ), 'value' => '1.00', 'order' => 0 ),
            array( 'label'  => __( 'Two', 'ninja-forms' ), 'value' => '2.00', 'order' => 1 ),
            array( 'label'  => __( 'Three', 'ninja-forms' ), 'value' => '3.00', 'order' => 2 ),
        ),
         'columns'          => array(
            'label'         => array(
                'header'    => __( 'Label', 'ninja-forms' ),
                'default'   => '',
            ),

            'value'         => array(
                'header'    => __( 'Value', 'ninja-forms' ),
                'default'   => '',
            ),
        ),
        'deps'              => array(
            'shipping_type' => 'select'
        ),
    ),

    'shipping_type'         => array(
        'name'              => 'shipping_type',
        'type'              => 'select',
        'options'           => array(
            array(
                'label'     => __( 'Single Cost', 'ninja-forms' ),
                'value'     => 'single',
            ),
            array(
                'label'     => __( 'Cost Dropdown', 'ninja-forms' ),
                'value'     => 'select',
            ),
        ),
        'label'             => __( 'Cost Type', 'ninja-forms' ),
        'width'             => 'full',
        'group'             => '', //'primary',
        'value'             => 'single',
    ),

    'product_assignment'      => array(
        'name'              => 'product_assignment',
        'type'              => 'select',
        'label'             => __( 'Product', 'ninja-forms' ),
        'width'             => 'full',
        'group'             => 'primary',
        'options'           => array(),
        'select_product'    => array(
            'value'         => '',
            'label'         => __( '- Select a Product', 'ninja-forms' ),
        ),
    ),

    /*
    |--------------------------------------------------------------------------
    | Anti-Spam Field Settings
    |--------------------------------------------------------------------------
    */

    /*
     * Spam Answer
     */

    'spam_answer' => array(
        'name' => 'spam_answer',
        'type' => 'textbox',
        'label' => __( 'Answer', 'ninja-forms'),
        'width' => 'full',
        'group' => 'primary',
        'value' => '',
        'help'  => __( 'A case sensitive answer to help prevent spam submissions of your form.', 'ninja-forms' ),
    ),

    /*
    |--------------------------------------------------------------------------
    | Term Field Settings
    |--------------------------------------------------------------------------
    */

    /*
     * Taxonomy
     */

    'taxonomy' => array(
        'name' => 'taxonomy',
        'type' => 'select',
        'label' => __( 'Taxonomy', 'ninja-forms'),
        'width' => 'full',
        'group' => 'primary',
        'options' => array(
            array(
                'label' => "-",
                'value' => ''
            )
        )
    ),

    /*
     * Add New Terms
     */

    'add_new_terms' => array(
        'name' => 'add_new_terms',
        'type' => 'toggle',
        'label' => __( 'Add New Terms', 'ninja-forms'),
        'width' => 'full',
        'group' => 'advanced',
    ),

    /*
    |--------------------------------------------------------------------------
    | Backwards Compatibility Field Settings
    |--------------------------------------------------------------------------
    */

    'user_state' => array(
        'name' => 'user_state',
        'type' => 'toggle',
        'label' => __( 'This is a user\'s state.', 'ninja-forms' ),
        'width' => 'full',
        'group' => 'administration',
        'value' => FALSE,
        'help' => __( 'Used for marking a field for processing.', 'ninja-forms' ),
    ),

));


// Example of settings

// Add all core settings. Fields can unset if unneeded.
// $this->_settings = $this->load_settings(
//     array( 'label', 'label_pos', 'required', 'number', 'spam_question', 'mask', 'input_limit_set','rich_text_editor', 'placeholder', 'textare_placeholder', 'default', 'checkbox_default_value', 'classes', 'timed_submit' )
// );
