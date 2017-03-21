<?php if ( ! defined( 'ABSPATH' ) ) exit;

return array(

    /*
    |--------------------------------------------------------------------------
    | One Week Support
    |--------------------------------------------------------------------------
    */

    'one_week_support' => array(
        'title' => __( 'How\'s It Going?', 'ninja-forms' ),
        'msg' => __( 'Thank you for using Ninja Forms! We hope that you\'ve found everything you need, but if you have any questions:', 'ninja-forms' ),
        'link' => '<li><span class="dashicons dashicons-media-text"></span><a target="_blank" href="https://ninjaforms.com/documentation/?utm_medium=plugin&utm_source=admin-notice&utm_campaign=Ninja+Forms+Upsell&utm_content=Ninja+Forms+Docs">' . __( 'Check out our documentation', 'ninja-forms' ) . '</a></li>
                    <li><span class="dashicons dashicons-sos"></span><a target="_blank" href="https://ninjaforms.com/contact/?utm_medium=plugin&utm_source=admin-notice&utm_campaign=Ninja+Forms+Upsell&utm_content=Ninja+Forms+Support">' . __( 'Get Some Help' ,'ninja-forms' ) . '</a></li>
                    <li><span class="dashicons dashicons-dismiss"></span><a href="' . add_query_arg( array( 'nf_admin_notice_ignore' => __( 'one_week_support', 'ninja-forms' ) ) ) . '">' . __( 'Dismiss' ,'ninja-forms' ) . '</a></li>',
        'int' => 7,
        'blacklist' => array( 'ninja-forms-three' ),
    ),

    /*
    |--------------------------------------------------------------------------
    | Two Week Support
    |--------------------------------------------------------------------------
    */

//    $notices['two_week_review'] = array(
//        'title' => __( 'Leave A Review?', 'ninja-forms' ),
//        'msg' => __( 'We hope you\'ve enjoyed using Ninja Forms! Would you consider leaving us a review on WordPress.org?', 'ninja-forms' ),
//        'link' => '<li> <span class="dashicons dashicons-smiley"></span><a href="' . add_query_arg( array( 'nf_admin_notice_ignore' => 'two_week_review' ) ) . '"> ' . __( 'I\'ve already left a review', 'ninja-forms' ) . '</a></li>
//                    <li><span class="dashicons dashicons-calendar-alt"></span><a href="' . add_query_arg( array( 'nf_admin_notice_temp_ignore' => 'two_week_review', 'int' => 14 ) ) . '">' . __( 'Maybe Later' ,'ninja-forms' ) . '</a></li>
//                    <li><span class="dashicons dashicons-external"></span><a href="http://wordpress.org/support/view/plugin-reviews/ninja-forms?filter=5" target="_blank">' . __( 'Sure! I\'d love to!', 'ninja-forms' ) . '</a></li>',
//        'int' => 14
//    ),

);
