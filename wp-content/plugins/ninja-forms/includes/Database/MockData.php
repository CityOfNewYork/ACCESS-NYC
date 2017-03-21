<?php if ( ! defined( 'ABSPATH' ) ) exit;

final class NF_Database_MockData
{

    public function __construct()
    {
        $this->_migrate();
    }

    public function saved_fields()
    {
        $field = Ninja_Forms()->form()->field()->get();
        $field->update_setting( 'label', __( 'Foo', 'ninja-forms' ) );
        $field->update_setting( 'key', 'foo' );
        $field->update_setting( 'type', 'textbox' );
        $field->update_setting( 'saved', 1 );
        $field->save();

        $field = Ninja_Forms()->form()->field()->get();
        $field->update_setting( 'label', __( 'Bar', 'ninja-forms' ) );
        $field->update_setting( 'key', 'bar' );
        $field->update_setting( 'type', 'checkbox' );
        $field->update_setting( 'saved', 1 );
        $field->save();

        $field = Ninja_Forms()->form()->field()->get();
        $field->update_setting( 'label', __( 'Baz', 'ninja-forms' ) );
        $field->update_setting( 'key', 'baz' );
        $field->update_setting( 'type', 'listselect' );
        $field->update_setting( 'saved', 1 );
        $field->save();
    }

    public function form_blank_form()
    {
        /*
         * FORM
         */

        $form = Ninja_Forms()->form()->get();
        $form->update_setting( 'title', __( 'Blank Forms', 'ninja-forms' ) );
        $form->update_setting( 'default_label_pos', 'above' );
        $form->save();
    }

    public function form_contact_form_1()
    {
        /*
         * FORM
         */

        $form = Ninja_Forms()->form()->get();
        $form->update_setting( 'title', __( 'Contact Me', 'ninja-forms' ) );
        $form->update_setting( 'default_label_pos', 'above' );
        $form->save();

        $form_id = $form->get_id();

        /*
         * FIELDS
         */

        $field = Ninja_Forms()->form( $form_id )->field()->get();
        $field->update_setting( 'type', 'textbox' )
            ->update_setting( 'label', __( 'Name', 'ninja-forms' ) )
            ->update_setting( 'label_pos', 'above' )
            ->update_setting( 'required', 1 )
            ->update_setting( 'order', 1 )
            ->update_setting( 'key', 'name' )
            ->update_setting( 'placeholder', '' )
            ->update_setting( 'default', '' )
            ->update_setting( 'wrapper_class', '' )
            ->update_setting( 'element_class', '' )
            ->save();

        $name_field_id = $field->get_id();

        $field = Ninja_Forms()->form( $form_id )->field()->get();
        $field->update_setting( 'type', 'email' )
            ->update_setting( 'label', __( 'Email', 'ninja-forms' ) )
            ->update_setting( 'label_pos', 'above' )
            ->update_setting( 'required', 1 )
            ->update_setting( 'order', 2 )
            ->update_setting( 'key', 'email' )
            ->update_setting( 'placeholder', '' )
            ->update_setting( 'default', '' )
            ->update_setting( 'wrapper_class', '' )
            ->update_setting( 'element_class', '' )
            ->save();

        $email_field_id = $field->get_id();

        // $field = Ninja_Forms()->form( $form_id )->field()->get();
        // $field->update_setting( 'type', 'textbox' )
        //     ->update_setting( 'label', 'Confirm Email')
        //     ->update_setting( 'label_pos', 'above' )
        //     ->update_setting( 'confirm_field', $email_field_id )
        //     ->update_setting( 'required', 1 )
        //     ->update_setting( 'order', 3 )
        //     ->save();

        $field = Ninja_Forms()->form( $form_id )->field()->get();
        $field->update_setting( 'type', 'textarea' )
            ->update_setting( 'label', __( 'Message', 'ninja-forms' ) )
            ->update_setting( 'label_pos', 'above' )
            ->update_setting( 'required', 1 )
            ->update_setting( 'order', 3 )
            ->update_setting( 'key', 'message' )
            ->update_setting( 'placeholder', '' )
            ->update_setting( 'default', '' )
            ->update_setting( 'wrapper_class', '' )
            ->update_setting( 'element_class', '' )
            ->save();

        // $field = Ninja_Forms()->form( $form_id )->field()->get();
        // $field->update_setting( 'type', 'textbox' )
        //     ->update_setting( 'label', 'Mirror Name')
        //     ->update_setting( 'label_pos', 'above' )
        //     ->update_setting( 'required', 1 )
        //     ->update_setting( 'mirror_field', $name_field_id )
        //     ->update_setting( 'order', 5 )
        //     ->save();

        $options = array(
            array(
                'label' => __( 'One', 'ninja-forms' ),
                'value' => '1',
                'calc' => 1,
                'order' => 1,
                'selected' => 0,
            ),
            array(
                'label' => __( 'Two', 'ninja-forms' ),
                'value' => '2',
                'calc' => 2,
                'order' => 2,
                'selected' => 1,
            ),
            array(
                'label' => __( 'Three', 'ninja-forms' ),
                'value' => '3',
                'calc' => 3,
                'order' => 3,
                'selected' => 0,
            ),
        );

        $field = Ninja_Forms()->form( $form_id )->field()->get();
        $field->update_setting( 'type', 'listradio' )
            ->update_setting( 'label_pos', 'above')
            ->update_setting( 'label', __( 'List', 'ninja-forms' ) )
            ->update_setting( 'required', 0)
            ->update_setting( 'options', $options)
            ->update_setting( 'order', 4 )
            ->update_setting( 'key', 'list' )
            ->update_setting( 'placeholder', '' )
            ->update_setting( 'default', '' )
            ->update_setting( 'wrapper_class', '' )
            ->update_setting( 'element_class', '' )
            ->save();

        $field = Ninja_Forms()->form( $form_id )->field()->get();
        $field->update_setting( 'type', 'submit' )
            ->update_setting( 'label', __( 'Submit', 'ninja-forms' ) )
            ->update_setting( 'processing_label', 'Processing' )
            ->update_setting( 'order', 5 )
            ->update_setting( 'key', 'submit' )
            ->save();

        /*
         * ACTIONS
         */

        $action = Ninja_Forms()->form( $form_id )->action()->get();
        $action->update_setting( 'label', __( 'Mock Success Message Action', 'ninja-forms' ) )
            ->update_setting( 'type', 'successmessage' )
            ->update_setting( 'message', __( 'Thank you {field:name} for filling out my form!', 'ninja-forms' ) )
            ->update_setting( 'active', TRUE )
            ->save();

//        $action = Ninja_Forms()->form( $form_id )->action()->get();
//        $action->update_setting( 'label',  'Mock Redirect Action' )
//            ->update_setting( 'type', 'redirect' )
//            ->update_setting( 'url', 'http://kstover.codes' )
//            ->update_setting( 'active', 0 )
//            ->save();

        $action = Ninja_Forms()->form( $form_id )->action()->get();
        $action->update_setting( 'label',  __( 'Mock Email Action', 'ninja-forms' ) )
            ->update_setting( 'type', 'email' )
            ->update_setting( 'to', 'myformbuildingbringsallthedeveloperstotheyard@wpninjas.com' )
            ->update_setting( 'subject', __( 'This is an email action.', 'ninja-forms' ) )
            ->update_setting( 'message', __( 'Hello, Ninja Forms!', 'ninja-forms' ) )
            ->update_setting( 'active', FALSE )
            ->save();

//        $action = Ninja_Forms()->form( $form_id )->action()->get();
//        $action->update_setting( 'label',  'Run WordPress Action' )
//            ->update_setting( 'type', 'custom' )
//            ->update_setting( 'hook', 'action' )
//            ->update_setting( 'tag', 'blarg_action' )
//            ->save();

        $action = Ninja_Forms()->form( $form_id )->action()->get();
        $action->update_setting( 'label',  __( 'Mock Save Action', 'ninja-forms' ) )
            ->update_setting( 'type', 'save' )
            ->update_setting( 'active', TRUE )
            ->save();

        /*
         * SUBMISSIONS
         */

        $sub = Ninja_Forms()->form( $form_id )->sub()->get();
        $sub->update_field_value( 1, __( 'Foo Bar', 'ninja-forms' ) )
            ->update_field_value( 2, __( 'foo@wpninjas.com', 'ninja-forms' ) )
            ->update_field_value( 3, __( 'This is a test', 'ninja-forms' ) )
            ->update_field_value( 4, '2' )
            ->update_field_value( 5, __( 'Foo Bar', 'ninja-forms' ) );
        $sub->save();

        // Delay Execution for different submission dates
        sleep(1);

        $sub = Ninja_Forms()->form( $form_id )->sub()->get();
        $sub->update_field_value( 1, __( 'John Doe', 'ninja-forms' ) )
            ->update_field_value( 2, __( 'user@gmail.com', 'ninja-forms' ) )
            ->update_field_value( 3, __( 'This is another test.', 'ninja-forms' ) )
            ->update_field_value( 4, '3' )
            ->update_field_value( 5, __( 'John Doe', 'ninja-forms' ) );
        $sub->save();
    }

    public function form_contact_form_2()
    {
        /*
         * FORM
         */

        $form = Ninja_Forms()->form()->get();
        $form->update_setting( 'title', __( 'Get Help', 'ninja-forms' ) );
        $form->update_setting( 'default_label_pos', 'above' );
        $form->save();

        $form_id = $form->get_id();

        /*
         * FIELDS
         */

        $fields = array(
            array(
                'type' 			=> 'textbox',
                'label'			=> __( 'Name', 'ninja-forms' ),
                'label_pos' 	=> 'above',
                'order'         => 1,
                'key'           => 'name',
            ),
            array(
                'type'			=> 'email',
                'label'			=> __( 'Email', 'ninja-forms' ),
                'label_pos'		=> 'above',
                'order'         => 2,
                'key'           => 'email',
            ),
            array(
                'type' 			=> 'textarea',
                'label'			=> __( 'What Can We Help You With?', 'ninja-forms' ),
                'label_pos'		=> 'above',
                'order'         => 3,
                'key'           => 'message',
            ),
            array(
                'type' 			=> 'checkbox',
                'label'			=> __( 'Agree?', 'ninja-forms' ),
                'label_pos'		=> 'right',
                'order'         => 4,
                'key'           => 'agree',
            ),
            array(
                'type' 			=> 'listradio',
                'label'			=> __( 'Best Contact Method?', 'ninja-forms' ),
                'label_pos'		=> 'above',
                'options'		=> array(
                    array(
                        'label'	=> __( 'Phone', 'ninja-forms' ),
                        'value'	=> 'phone',
                        'calc'  => '',
                        'order' => 1,
                        'selected' => 0,
                    ),
                    array(
                        'label'	=> __( 'Email', 'ninja-forms' ),
                        'value'	=> 'email',
                        'calc'  => '',
                        'order' => 2,
                        'selected' => 0,
                    ),
                    array(
                        'label'	=> __( 'Snail Mail', 'ninja-forms' ),
                        'value'	=> 'snail-mail',
                        'calc'  => '',
                        'order' => 3,
                        'selected' => 0,
                    ),
                ),
                'show_other'	=> 1,
                'required'      => 1,
                'order'         => 5,
                'key'           => 'contact_method',
            ),
            array(
                'type'			=> 'submit',
                'label'			=> __( 'Send', 'ninja-forms' ),
                'order'         => 6,
                'key'           => 'submit',
            )
        );

        foreach( $fields as $settings ){

            $field = Ninja_Forms()->form( $form_id )->field()->get();
            $field->update_settings( $settings )->save();
        }

        /*
         * ACTIONS
         */

        $action = Ninja_Forms()->form( $form_id )->action()->get();
        $action->update_setting( 'label',  __( 'Mock Save Action', 'ninja-forms' ) )
            ->update_setting( 'type', 'save' )
            ->save();
    }

    public function form_kitchen_sink()
    {
        /*
         * FORM
         */
        $form = Ninja_Forms()->form()->get();
        $form->update_setting( 'title', __( 'Kitchen Sink', 'ninja-forms' ) );
        $form->update_setting( 'default_label_pos', 'above' );
        $form->save();

        $form_id = $form->get_id();

        /*
         * FIELDS
         */

        $fields = array(
            array(
                'type'          => 'html',
                'label'         => __( 'Textbox', 'ninja-forms' ),
                'key'           => 'textbox',
            ),
            array(
                'type' 			=> 'textbox',
                'label'			=> __( 'Textbox', 'ninja-forms' ),
                'key'           => 'textbox',
            ),
            array(
                'type' 			=> 'firstname',
                'label'			=> __( 'First Name', 'ninja-forms' ),
                'key'           => 'first_name',
            ),
            array(
                'type' 			=> 'lastname',
                'label'			=> __( 'Last Name', 'ninja-forms' ),
                'key'           => 'last_name',
            ),
            array(
                'type' 			=> 'hidden',
                'label'			=> __( 'Hidden', 'ninja-forms' ),
                'label_pos' 	=> 'hidden',
                'key'           => 'hidden',
            ),
            array(
                'type' 			=> 'textarea',
                'label'			=> __( 'Textarea', 'ninja-forms' ),
                'key'           => 'textarea',
            ),
            array(
                'type' 			=> 'listselect',
                'label'			=> __( 'Select List', 'ninja-forms' ),
                'options'      => array(
                    array(
                        'label' => __( 'Option One', 'ninja-forms' ),
                        'value' => 1,
                        'calc'  => '',
                        'order' => 1,
                        'selected' => 0,
                    ),
                    array(
                        'label' => __( 'Option Two', 'ninja-forms' ),
                        'value' => 2,
                        'calc'  => '',
                        'order' => 2,
                        'selected' => 0,
                    ),
                    array(
                        'label' => __( 'Option Three', 'ninja-forms' ),
                        'value' => 3,
                        'calc'  => '',
                        'order' => 3,
                        'selected' => 0,
                    )
                ),
                'key'           => 'select_list',
            ),
            array(
                'type' 			=> 'listradio',
                'label'			=> __( 'Radio List', 'ninja-forms' ),
                'options'       => array(
                    array(
                        'label' => __( 'Option One', 'ninja-forms' ),
                        'value' => 1,
                        'calc'  => '',
                        'order' => 1,
                        'selected' => 0,
                    ),
                    array(
                        'label' => __( 'Option Two', 'ninja-forms' ),
                        'value' => 2,
                        'calc'  => '',
                        'order' => 2,
                        'selected' => 0,
                    ),
                    array(
                        'label' => __( 'Option Three', 'ninja-forms' ),
                        'value' => 3,
                        'calc'  => '',
                        'order' => 3,
                        'selected' => 0,
                    )
                ),
                'key'           => 'radio_list',
            ),
            array(
                'type' 			=> 'checkbox',
                'label'			=> __( 'Checkbox', 'ninja-forms' ),
                'key'           => 'checkbox',
            ),
            // array(
            //     'type' 			=> 'button',
            //     'label'			=> 'Button',
            //     'label_pos' 	=> 'hidden',
            // ),
        );

        $order = 1;
        $i = 1;
        foreach( array( 'above', 'right', 'below', 'left', 'hidden' ) as $label_pos ) {


            foreach ($fields as $settings) {

                unset($settings['id']);

                $settings[ 'key' ] = $settings[ 'key' ] . '-' . $i;

                if ( ! isset( $settings['label_pos'] ) ) $settings['label_pos'] = $label_pos;

                if ( 'submit' != $settings['type'] ) $settings['label'] = $settings['label'] . ' - label ' . $label_pos;

                if ( 'hidden' == $settings['label_pos'] && 'submit' != $settings['type'] ) $settings['placeholder'] = $settings['label'];

                $field = Ninja_Forms()->form($form_id)->field()->get();

                $settings[ 'order' ] = $order;

                $field->update_settings($settings)->save();

                $order++;
            }
            $order++;
            $i++;
        }

        $submit = Ninja_Forms()->form($form_id)->field()->get();
        $submit->update_setting( 'label', __( 'Submit', 'ninja-forms' ) )
                ->update_setting( 'type', 'submit' )
                ->update_setting( 'order', $order)
                ->update_setting( 'key', 'submit' )
                ->save();

        $action = Ninja_Forms()->form( $form_id )->action()->get();
        $action->update_setting( 'label',  __( 'Mock Save Action', 'ninja-forms' ) )
            ->update_setting( 'type', 'save' )
            ->save();
    }

    public function form_bathroom_sink()
    {
        /*
         * FORM
         */
        $form = Ninja_Forms()->form()->get();
        $form->update_setting( 'title', __( 'Bathroom Sink', 'ninja-forms' ) );
        $form->update_setting( 'default_label_pos', 'above' );
        $form->save();

        $form_id = $form->get_id();

        /*
         * FIELDS
         */

        $fields = array(
            array(
                'type'          => 'html',
                'label'         => '',
                'label_pos'     => 'hidden',
                'key'           => 'html_1',
                'default'       => '<div style="background:#DBF0FD; padding: 15px;"><h3>Common Fields</h3><div>These are all the most common generic fields one might use.</div></div>',
            ),
            array(
                'type' 			=> 'textbox',
                'label'			=> __( 'Textbox', 'ninja-forms' ),
                'key'           => 'textbox',
            ),
            array(
                'type'          => 'textarea',
                'label'         => __( 'Textarea', 'ninja-forms' ),
                'key'           => 'textarea',
            ),
            array(
                'type'          => 'checkbox',
                'label'         => __( 'Checkbox', 'ninja-forms' ),
                'key'           => 'checkbox',
            ),
            array(
                'type'          => 'listselect',
                'label'         => __( 'Select List', 'ninja-forms' ),
                'options'      => array(
                    array(
                        'label' => __( 'Option One', 'ninja-forms' ),
                        'value' => 1,
                        'calc'  => '',
                        'order' => 1,
                        'selected' => 0,
                    ),
                    array(
                        'label' => __( 'Option Two', 'ninja-forms' ),
                        'value' => 2,
                        'calc'  => '',
                        'order' => 2,
                        'selected' => 0,
                    ),
                    array(
                        'label' => __( 'Option Three', 'ninja-forms' ),
                        'value' => 3,
                        'calc'  => '',
                        'order' => 3,
                        'selected' => 0,
                    )
                ),
                'key'           => 'select_list',
            ),
            array(
                'type'          => 'listradio',
                'label'         => __( 'Radio List', 'ninja-forms' ),
                'options'       => array(
                    array(
                        'label' => __( 'Option One', 'ninja-forms' ),
                        'value' => 1,
                        'calc'  => '',
                        'order' => 1,
                        'selected' => 0,
                    ),
                    array(
                        'label' => __( 'Option Two', 'ninja-forms' ),
                        'value' => 2,
                        'calc'  => '',
                        'order' => 2,
                        'selected' => 0,
                    ),
                    array(
                        'label' => __( 'Option Three', 'ninja-forms' ),
                        'value' => 3,
                        'calc'  => '',
                        'order' => 3,
                        'selected' => 0,
                    )
                ),
                'key'           => 'radio_list',
            ),
            array(
                'type'          => 'listcheckbox',
                'label'         => __( 'Checkbox List', 'ninja-forms' ),
                'options'       => array(
                    array(
                        'label' => __( 'Option One', 'ninja-forms' ),
                        'value' => 1,
                        'calc'  => '',
                        'order' => 1,
                        'selected' => 0,
                    ),
                    array(
                        'label' => __( 'Option Two', 'ninja-forms' ),
                        'value' => 2,
                        'calc'  => '',
                        'order' => 2,
                        'selected' => 0,
                    ),
                    array(
                        'label' => __( 'Option Three', 'ninja-forms' ),
                        'value' => 3,
                        'calc'  => '',
                        'order' => 3,
                        'selected' => 0,
                    )
                ),
                'key'           => 'checkbox_list',
            ),
            array(
                'type'          => 'date',
                'label'         => __( 'Date', 'ninja-forms' ),
                'key'           => 'date',
            ),
            array(
                'type'          => 'number',
                'label'         => __( 'Number', 'ninja-forms' ),
                'key'           => 'number',
                'num_min'       => '0',
                'num_max'       => '100',
                'num_step'      => '1',
            ),
            array(
                'type'          => 'hidden',
                'label'         => __( 'Hidden', 'ninja-forms' ),
                'label_pos'     => 'hidden',
                'key'           => 'hidden',
            ),
            // array(
            //     'type'          => 'hr',
            //     'label'         => 'Divider',
            //     'label_pos'     => 'hidden',
            //     'key'           => 'hr_1',
            // ),
            array(
                'type'          => 'html',
                'label'         => '',
                'label_pos'     => 'hidden',
                'key'           => 'html_2',
                'default'       => '<div style="background:#DBF0FD; padding: 15px;"><h3>' . __( 'User Information Fields', 'ninja-forms' ) .
                                    '</h3><div>' . __( 'These are all the fields in the User Information section.', 'ninja-forms' ) . '</div></div>',
            ),
            array(
                'type' 			=> 'firstname',
                'label'			=> __( 'First Name', 'ninja-forms' ),
                'key'           => 'first_name',
            ),
            array(
                'type' 			=> 'lastname',
                'label'			=> __( 'Last Name', 'ninja-forms' ),
                'key'           => 'last_name',
            ),
            array(
                'type'          => 'email',
                'label'         => __( 'Email', 'ninja-forms' ),
                'key'           => 'email',
            ),
            array(
                'type'          => 'phone',
                'label'         => __( 'Phone', 'ninja-forms' ),
                'key'           => 'phone',
            ),
            array(
                'type'          => 'address',
                'label'         => __( 'Address', 'ninja-forms' ),
                'key'           => 'address',
            ),
            array(
                'type'          => 'city',
                'label'         => __( 'City', 'ninja-forms' ),
                'key'           => 'city',
            ),
            // array(
            //     'type'          => 'liststate',
            //     'label'         => 'State',
            //     'key'           => 'state',
            // ),
            array(
                'type'          => 'zip',
                'label'         => __( 'Zip Code', 'ninja-forms' ),
                'key'           => 'zip',
            ),
            array(
                'type'          => 'html',
                'label'         => '',
                'label_pos'     => 'hidden',
                'key'           => 'html_3',
                'default'       => '<div style="background:#DBF0FD; padding: 15px;"><h3>' . __( "Pricing Fields", "ninja-forms" ) .
                                    '</h3><div>' . __( "These are all the fields in the Pricing section.", "ninja-forms" ) . '</div></div>',
            ),
            array(
                'type'                  => 'product',
                'label'                 => __( 'Product (quanitity included)', 'ninja-forms' ),
                'key'                   => 'product_qty',
                'product_use_quantity'  => 1,
                'product_price'         => '5.00',
            ),
            array(
                'type'                  => 'product',
                'label'                 => __( 'Product (seperate quantity)', 'ninja-forms' ),
                'key'                   => 'product',
                'product_use_quantity'  => 0,
                'product_price'         => '5.00',
            ),
            array(
                'type'                  => 'quantity',
                'label'                 => __( 'Quantity', 'ninja-forms' ),
                'key'                   => 'quantity',
                'product_assignment'    => '999',
                'num_min'               => '0',
                'num_max'               => '',
                'num_step'              => '1',
            ),
            array(
                'type'                  => 'shipping',
                'label'                 => __( 'Shipping', 'ninja-forms' ),
                'key'                   => 'shipping',
                'shipping_cost'         => '10.00',
            ),
            array(
                'type'          => 'total',
                'label'         => __( 'Total', 'ninja-forms' ),
                'key'           => 'total',
            ),
            array(
                'type'          => 'creditcardfullname',
                'label'         => __( 'Credit Card Full Name', 'ninja-forms' ),
                'key'           => 'creditcardfullname',
            ),
            array(
                'type'          => 'creditcardnumber',
                'label'         => __( 'Credit Card Number', 'ninja-forms' ),
                'key'           => 'creditcardnumber',
            ),
            array(
                'type'          => 'creditcardcvc',
                'label'         => __( 'Credit Card CVV', 'ninja-forms' ),
                'key'           => 'creditcardcvc',
            ),
            array(
                'type'          => 'creditcardexpiration',
                'label'         => __( 'Credit Card Expiration', 'ninja-forms' ),
                'key'           => 'creditcardexpiration',
            ),
            array(
                'type'          => 'creditcardzip',
                'label'         => __( 'Credit Card Zip Code', 'ninja-forms' ),
                'key'           => 'creditcardzip',
            ),
            array(
                'type'          => 'html',
                'label'         => '',
                'label_pos'     => 'hidden',
                'key'           => 'html_3',
                'default'       => '<div style="background:#DBF0FD; padding: 15px;"><h3>' . __( "Miscellaneous Fields", "ninja-forms" ) .
                                    '</h3><div>' . __( "These are various special fields.", "ninja-forms" ) . '</div></div>',
            ),
            array(
                'type'          => 'starrating',
                'label'         => __( 'Star Rating', 'ninja-forms' ),
                'key'           => 'starrating',
                'default'       => '5',
            ),
            array(
                'type'          => 'spam',
                'label'         => __( 'Anti-Spam Question (Answer = answer)', 'ninja-forms' ),
                'key'           => 'spam',
                'spam_answer'   => __( 'answer', 'ninja-forms' ),
            ),
            array(
                'type'          => 'hr',
                'label'         => '',
                'key'           => 'hr',
            ),
        );

        $order = 1;
        foreach ($fields as $settings) {

            unset($settings['id']);

            $field = Ninja_Forms()->form($form_id)->field()->get();

            $settings[ 'order' ] = $order;

            $settings[ 'label_pos' ] = 'default';

            $field->update_settings($settings)->save();

            $order++;
        }

        $submit = Ninja_Forms()->form($form_id)->field()->get();
        $submit->update_setting( 'label', __( 'Submit', 'ninja-forms' ) )
            ->update_setting( 'type', 'submit' )
            ->update_setting( 'order', $order)
            ->update_setting( 'process_label', __( 'processing', 'ninja-forms' ) )
            ->update_setting( 'key', 'submit' )
            ->save();

        $action = Ninja_Forms()->form( $form_id )->action()->get();
        $action->update_setting( 'label',  __( 'Mock Save Action', 'ninja-forms' ) )
            ->update_setting( 'type', 'save' )
            ->save();
    }

    public function form_long_form( $num_fields = 500 )
    {
        /*
        * FORM
        */

        $form = Ninja_Forms()->form()->get();
        $form->update_setting( 'title', __( 'Long Form - ', 'ninja-forms' ) . $num_fields . __( ' Fields', 'ninja-forms' ) );
        $form->update_setting( 'default_label_pos', 'above' );
        $form->save();

        $form_id = $form->get_id();

        /*
         * FIELDS
         */

        for( $i = 1; $i <= $num_fields; $i++ ) {
            $field = Ninja_Forms()->form($form_id)->field()->get();
            $field->update_setting( 'type', 'textbox' )
                ->update_setting( 'label', __( 'Field #', 'ninja-forms' ) . $i )
                ->update_setting( 'label_pos', 'above' )
                ->update_setting( 'required', 0 )
                ->update_setting( 'order', $i )
                ->update_setting( 'key', 'field_' . $i )
                ->save();
        }
    }

    public function form_email_submission()
    {
        /*
         * FORM
         */

        $form = Ninja_Forms()->form()->get();
        $form->update_setting( 'title', __( 'Email Subscription Form', 'ninja-forms' ) );
        $form->update_setting( 'default_label_pos', 'above' );
        $form->save();

        $form_id = $form->get_id();

        /*
         * FIELDS
         */

        $field = Ninja_Forms()->form( $form_id )->field()->get();
        $field->update_setting( 'type', 'email' )
            ->update_setting( 'label', __( 'Email Address', 'ninja-forms' ) )
            ->update_setting( 'label_pos', 'hidden' )
            ->update_setting( 'required', 1 )
            ->update_setting( 'order', 1 )
            ->update_setting( 'placeholder', __( 'Enter your email address', 'ninja-forms' ) )
            ->update_setting( 'wrapper_class', 'three-fourths first' )
            ->update_setting( 'key', 'email' )
            ->save();

        $email_field_id = $field->get_id();

        $field = Ninja_Forms()->form( $form_id )->field()->get();
        $field->update_setting( 'type', 'submit' )
            ->update_setting( 'label', __( 'Subscribe', 'ninja-forms' ) )
            ->update_setting( 'order', 5 )
            ->update_setting( 'wrapper_class', 'one-fourth' )
            ->update_setting( 'key', 'submit' )
            ->save();

    }

    public function form_product_1()
    {
        /* FORM */
        $form = Ninja_Forms()->form()->get();
        $form->update_setting( 'title', __( 'Product Form (with Quantity Field)', 'ninja-forms' ) );
        $form->update_setting( 'default_label_pos', 'above' );
        $form->update_setting( 'hide_successfully_completed_form', 1 );
        $form->save();

        $form_id = $form->get_id();

        /* Fields */
        $field = Ninja_Forms()->form( $form_id )->field()->get();
        $field->update_setting( 'type', 'product' )
            ->update_setting( 'label', __( 'Product', 'ninja-forms' ))
            ->update_setting( 'label_pos', 'above' )
            ->update_setting( 'product_price', 10.10 )
            ->update_setting( 'product_use_quantity', 0 )
            ->update_setting( 'order', 1 )
            ->update_setting( 'key', 'product' )
            ->save();

        $product_field_id = $field->get_id();

        $field = Ninja_Forms()->form( $form_id )->field()->get();
        $field->update_setting( 'type', 'quantity' )
            ->update_setting( 'label', __( 'Quantity', 'ninja-forms' ))
            ->update_setting( 'label_pos', 'above' )
            ->update_setting( 'product_assignment', $product_field_id )
            ->update_setting( 'default', 1 )
            ->update_setting( 'num_min', 1 )
            ->update_setting( 'num_max', NULL )
            ->update_setting( 'num_step', 1 )
            ->update_setting( 'order', 2 )
            ->update_setting( 'key', 'quantity' )
            ->save();

        $quantity_field_id = $field->get_id();

        $field = Ninja_Forms()->form( $form_id )->field()->get();
        $field->update_setting( 'type', 'shipping' )
            ->update_setting( 'label', __( 'Shipping', 'ninja-forms' ) )
            ->update_setting( 'label_pos', 'above' )
            ->update_setting( 'shipping_cost', 2.00 )
            ->update_setting( 'order', 4 )
            ->update_setting( 'key', 'shipping' )
            ->save();

        $field = Ninja_Forms()->form( $form_id )->field()->get();
        $field->update_setting( 'type', 'total' )
            ->update_setting( 'label', __( 'Total', 'ninja-forms' ) )
            ->update_setting( 'label_pos', 'above' )
            ->update_setting( 'key', 'total' )
            ->update_setting( 'order', 5 )
            ->update_setting( 'key', 'total' )
            ->save();

        $field = Ninja_Forms()->form( $form_id )->field()->get();
        $field->update_setting( 'type', 'submit' )
            ->update_setting( 'label', __( 'Purchase', 'ninja-forms' ) )
            ->update_setting( 'order', 1000 )
            ->update_setting( 'key', 'submit' )
            ->save();

        /*
         * ACTIONS
         */

        $action = Ninja_Forms()->form( $form_id )->action()->get();
        $action->update_setting( 'label',  __( 'Success Message', 'ninja-forms' ) )
            ->update_setting( 'type', 'successmessage' )
            ->update_setting( 'message', '<div style="border: 2px solid green; padding: 10px; color: green;">' . __( 'You purchased ', 'ninja-forms' ) .
                            '{field:' . $quantity_field_id . '}' .  __( 'product(s) for ', 'ninja-forms' ) . '${field:total}.</div>' )
            ->save();
    }

    public function form_product_2()
    {
        /* FORM */
        $form = Ninja_Forms()->form()->get();
        $form->update_setting( 'title', __( 'Product Form (Inline Quantity)', 'ninja-forms' ) );
        $form->update_setting( 'default_label_pos', 'above' );
        $form->update_setting( 'hide_successfully_completed_form', 1 );
        $form->save();

        $form_id = $form->get_id();

        /* Fields */
        $field = Ninja_Forms()->form( $form_id )->field()->get();
        $field->update_setting( 'type', 'product' )
            ->update_setting( 'label', __( 'Product', 'ninja-forms' ) )
            ->update_setting( 'label_pos', 'above' )
            ->update_setting( 'product_price', 10.10 )
            ->update_setting( 'product_use_quantity', 1 )
            ->update_setting( 'order', 1 )
            ->update_setting( 'key', 'product' )
            ->save();

        $product_field_id = $field->get_id();

        $field = Ninja_Forms()->form( $form_id )->field()->get();
        $field->update_setting( 'type', 'shipping' )
            ->update_setting( 'label', __( 'Shipping', 'ninja-forms' ) )
            ->update_setting( 'label_pos', 'above' )
            ->update_setting( 'shipping_cost', 2.00 )
            ->update_setting( 'order', 4 )
            ->update_setting( 'key', 'shipping' )
            ->save();

        $field = Ninja_Forms()->form( $form_id )->field()->get();
        $field->update_setting( 'type', 'total' )
            ->update_setting( 'label', __( 'Total', 'ninja-forms' ) )
            ->update_setting( 'label_pos', 'above' )
            ->update_setting( 'key', 'total' )
            ->update_setting( 'order', 5 )
            ->update_setting( 'key', 'total' )
            ->save();

        $field = Ninja_Forms()->form( $form_id )->field()->get();
        $field->update_setting( 'type', 'submit' )
            ->update_setting( 'label', __( 'Purchase', 'ninja-forms' ) )
            ->update_setting( 'order', 1000 )
            ->update_setting( 'key', 'submit' )
            ->save();

        /*
         * ACTIONS
         */

        $action = Ninja_Forms()->form( $form_id )->action()->get();
        $action->update_setting( 'label',  __( 'Success Message', 'ninja-forms' ) )
            ->update_setting( 'type', 'successmessage' )
            ->update_setting( 'message', '<div style="border: 2px solid green; padding: 10px; color: green;">' . __( 'You purchased ', 'ninja-forms' ) .
                                        '{field:' . $product_field_id . '}' . __( ' product(s) for ', 'ninja-forms' ) . '${field:total}' . '.' . '</div>' )
            ->save();
    }

    public function form_product_3()
    {
        /* FORM */
        $form = Ninja_Forms()->form()->get();
        $form->update_setting( 'title', __( 'Product Form (Multiple Products)', 'ninja-forms' ) );
        $form->update_setting( 'default_label_pos', 'above' );
        $form->update_setting( 'hide_successfully_completed_form', 1 );
        $form->save();

        $form_id = $form->get_id();

        /* Fields */
        $field = Ninja_Forms()->form( $form_id )->field()->get();
        $field->update_setting( 'type', 'product' )
            ->update_setting( 'label', __( 'Product A', 'ninja-forms' ) )
            ->update_setting( 'label_pos', 'above' )
            ->update_setting( 'product_price', 10.10 )
            ->update_setting( 'product_use_quantity', 0 )
            ->update_setting( 'order', 1 )
            ->update_setting( 'key', 'product_a' )
            ->save();

        $product_field_A_id = $field->get_id();

        $field = Ninja_Forms()->form( $form_id )->field()->get();
        $field->update_setting( 'type', 'quantity' )
            ->update_setting( 'label', __( 'Quantity for Product A', 'ninja-forms' ) )
            ->update_setting( 'label_pos', 'above' )
            ->update_setting( 'product_assignment', $product_field_A_id )
            ->update_setting( 'default', 1 )
            ->update_setting( 'num_min', 1 )
            ->update_setting( 'num_max', NULL )
            ->update_setting( 'num_step', 1 )
            ->update_setting( 'order', 2 )
            ->update_setting( 'key', 'qauntity_for_product_a' )
            ->save();

        $quantity_field_A_id = $field->get_id();

        $field = Ninja_Forms()->form( $form_id )->field()->get();
        $field->update_setting( 'type', 'product' )
            ->update_setting( 'label', __( 'Product B', 'ninja-forms' ) )
            ->update_setting( 'label_pos', 'above' )
            ->update_setting( 'product_price', 9.23 )
            ->update_setting( 'product_use_quantity', 0 )
            ->update_setting( 'order', 3 )
            ->update_setting( 'key', 'product_b' )
            ->save();

        $product_field_B_id = $field->get_id();

        $field = Ninja_Forms()->form( $form_id )->field()->get();
        $field->update_setting( 'type', 'quantity' )
            ->update_setting( 'label', __( 'Quantity for Product B', 'ninja-forms' ) )
            ->update_setting( 'label_pos', 'above' )
            ->update_setting( 'product_assignment', $product_field_B_id )
            ->update_setting( 'default', 1 )
            ->update_setting( 'num_min', 1 )
            ->update_setting( 'num_max', NULL )
            ->update_setting( 'num_step', 1 )
            ->update_setting( 'order', 4 )
            ->update_setting( 'key', 'quantity_for_product_b' )
            ->save();

        $quantity_field_B_id = $field->get_id();

        $field = Ninja_Forms()->form( $form_id )->field()->get();
        $field->update_setting( 'type', 'shipping' )
            ->update_setting( 'label', __( 'Shipping', 'ninja-forms' ) )
            ->update_setting( 'label_pos', 'above' )
            ->update_setting( 'shipping_cost', 2.00 )
            ->update_setting( 'order', 998 )
            ->update_setting( 'key', 'shipping' )
            ->save();

        $field = Ninja_Forms()->form( $form_id )->field()->get();
        $field->update_setting( 'type', 'total' )
            ->update_setting( 'label', __( 'Total', 'ninja-forms' ) )
            ->update_setting( 'label_pos', 'above' )
            ->update_setting( 'key', 'total' )
            ->update_setting( 'order', 999 )
            ->update_setting( 'key', 'total' )
            ->save();

        $field = Ninja_Forms()->form( $form_id )->field()->get();
        $field->update_setting( 'type', 'submit' )
            ->update_setting( 'label', __( 'Purchase', 'ninja-forms' ) )
            ->update_setting( 'order', 1000 )
            ->update_setting( 'key', 'submit' )
            ->save();

        /*
         * ACTIONS
         */

        $action = Ninja_Forms()->form( $form_id )->action()->get();
        $action->update_setting( 'label',  __( 'Success Message', 'ninja-forms' ) )
            ->update_setting( 'type', 'successmessage' )
            ->update_setting( 'message', '<div style="border: 2px solid green; padding: 10px; color: green;">' . __( 'You purchased ', 'ninja-forms' ) .
                            '{field:' . $quantity_field_A_id . '}' . __( 'of Product A and ', 'ninja-forms' ) . '{field:' . $quantity_field_B_id . '}' .
                            __( 'of Product B for $', 'ninja-forms' ) . '{field:total}.</div>' )
            ->save();
    }

    public function form_calc_form()
    {
        /*
         * FORM
         */

        $form = Ninja_Forms()->form()->get();
        $form->update_setting( 'title', __( 'Form with Calculations', 'ninja-forms' ) );
        $form->update_setting( 'default_label_pos', 'above' );
        $form->update_setting( 'calculations', array(
            array(
                'name' => __( 'My First Calculation', 'ninja-forms' ),
                'eq' => '2 * 3'
            ),
            array(
                'name' => __( 'My Second Calculation', 'ninja-forms' ),
                'eq' => '4 + 1'
            )
        ));
        $form->save();

        $form_id = $form->get_id();

        $field = Ninja_Forms()->form( $form_id )->field()->get();
        $field->update_setting( 'type', 'submit' )
            ->update_setting( 'label', __( 'Purchase', 'ninja-forms' ) )
            ->update_setting( 'order', 1000 )
            ->update_setting( 'key', 'submit' )
            ->save();

        $action = Ninja_Forms()->form( $form_id )->action()->get();
        $action->update_setting( 'label',  __( 'Success Message', 'ninja-forms' ) )
            ->update_setting( 'type', 'successmessage' )
            ->update_setting( 'message', __( 'Calculations are returned with the AJAX response ( response -> data -> calcs', 'ninja-forms' ) )
            ->save();
    }

    private function _migrate()
    {
        $migrations = new NF_Database_Migrations();
        $migrations->nuke(TRUE, TRUE);

        $posts = get_posts('post_type=nf_sub&numberposts=-1');
        foreach ($posts as $post) {
            wp_delete_post($post->ID, TRUE);
        }

        $migrations->migrate();
    }

} // END CLASS NF_Database_MockData
