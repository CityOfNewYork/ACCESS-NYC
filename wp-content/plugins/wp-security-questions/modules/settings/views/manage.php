<?php
/**
 * This class used to manage settings page in backend.
 * @author Flipper Code <hello@flippercode.com>
 * @version 1.0.0
 * @package Forms
 */

$form  = new WSQ_FORM();
$form->set_header( __( 'Setup Questions', WSQ_TEXT_DOMAIN ), $response );
$other_settings = get_option( 'wpr_security_ques_setting' );
$data = $other_settings;
$data['security_questions'] = get_option( 'wpr_register_security_ques' );
if ( isset( $data['security_questions'] ) ) {
	foreach ( $data['security_questions'] as $i => $label ) {
		$form->set_col( 2 );
		$form->add_element( 'text', 'security_questions['.$i.']', array(
			'value' => (isset( $data['security_questions'][ $i ] ) and ! empty( $data['security_questions'][ $i ] )) ? $data['security_questions'][ $i ] : '',
			'desc' => '',
			'class' => 'form-control',
			'placeholder' => __( 'Question', WSQ_TEXT_DOMAIN ),
			'before' => '<div class="fc-8">',
			'after' => '</div>',
		));
		$form->add_element( 'button', 'security_questions_repeat['.$i.']', array(
			'value' => __( 'Remove',WSQ_TEXT_DOMAIN ),
			'desc' => '',
			'class' => 'repeat_remove_button btn btn-info btn-sm',
			'before' => '<div class="fc-2">',
			'after' => '</div>',
		));
	}
}

$form->set_col( 2 );
if ( isset( $data['security_questions'] ) ) {
	$next_index = count( $data['security_questions'] ) + 1; } else {
	$next_index = 0;
	}
	$form->add_element( 'text', 'security_questions['.$next_index.']', array(
		'value' => (isset( $data['security_questions'][ $next_index ] ) and ! empty( $data['security_questions'][ $next_index ] )) ? $data['security_questions'][ $next_index ] : '',
		'desc' => '',
		'class' => 'form-control',
		'placeholder' => __( 'Question', WSQ_TEXT_DOMAIN ),
		'before' => '<div class="fc-8">',
		'after' => '</div>',
	));

$form->add_element( 'button', 'security_questions_repeat', array(
	'value' => __( 'Add More...',WSQ_TEXT_DOMAIN ),
	'desc' => '',
	'class' => 'repeat_button btn btn-info btn-sm',
	'before' => '<div class="fc-2">',
	'after' => '</div>',
));


$form->set_col( 1 );

$form->add_element( 'group', 'login_screen_settings', array(
	'value' => __( 'Login Screen', WSQ_TEXT_DOMAIN ),
	'before' => '<div class="fc-11">',
	'after' => '</div>',
));

$form->add_element( 'checkbox', 'allow_sec_ques_login', array(
	'lable' => __( 'Ask Security Question?', WSQ_TEXT_DOMAIN ),
	'value' => 'true',
	'id' => 'allow_sec_ques_login',
	'current' => $data['allow_sec_ques_login'],
	'desc' => __( 'Ask security question on login screen.', WSQ_TEXT_DOMAIN ),
	'class' => 'chkbox_class',
));


$form->add_element( 'group', 'register_screen_settings', array(
	'value' => __( 'Register Screen', WSQ_TEXT_DOMAIN ),
	'before' => '<div class="fc-11">',
	'after' => '</div>',
));

$form->add_element( 'checkbox', 'allow_sec_ques_register', array(
	'lable' => __( 'Ask Security Question?', WSQ_TEXT_DOMAIN ),
	'value' => 'true',
	'id' => 'allow_sec_ques_register',
	'current' => $data['allow_sec_ques_register'],
	'desc' => __( 'Set security answers on registration screen.', WSQ_TEXT_DOMAIN ),
	'class' => 'chkbox_class',
));


$form->add_element( 'group', 'forgot_settings', array(
	'value' => __( 'Forgot Password Screen', WSQ_TEXT_DOMAIN ),
	'before' => '<div class="fc-11">',
	'after' => '</div>',
));

$form->add_element( 'checkbox', 'allow_sec_ques_forgot', array(
	'lable' => __( 'Ask Security Question?', WSQ_TEXT_DOMAIN ),
	'value' => 'true',
	'id' => 'allow_sec_ques_forgot',
	'current' => $data['allow_sec_ques_forgot'],
	'desc' => __( 'Display security question on forgot password screen.', WSQ_TEXT_DOMAIN ),
	'class' => 'chkbox_class',
));

$form->add_element('submit','wsq_save_settings',array(
	'value' => __( 'Save Setting',WSQ_TEXT_DOMAIN ),
	));
$form->add_element('hidden','operation',array(
	'value' => 'save',
	));
$form->add_element('hidden','page_options',array(
	'value' => 'wsq_api_key,wsq_scripts_place',
	));
$form->render();
