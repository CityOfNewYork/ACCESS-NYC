<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_MergeTags_System
 */
final class NF_MergeTags_System extends NF_Abstracts_MergeTags
{
    protected $id = 'system';

    public function __construct()
    {
        parent::__construct();
        $this->title = __( 'System', 'ninja-forms' );
        $this->merge_tags = Ninja_Forms()->config( 'MergeTagsSystem' );
    }

    protected function system_date()
    {
        $format = Ninja_Forms()->get_setting( 'date_format' );
        if ( empty( $format ) ) {
            $format = 'Y/m/d';
        }
        return date( $format, time() );
    }

    protected function system_ip()
    {
        $ip = '127.0.0.1';
        if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
            //check ip from share internet
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            //to check ip is pass from proxy
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return apply_filters( 'ninja_forms-get_ip', apply_filters( 'nf_get_ip', $ip ) );
    }

    protected function admin_email()
    {
        return get_option( 'admin_email' );
    }

    protected function site_title()
    {
        return get_bloginfo( 'name' );
    }

    protected function site_url()
    {
        return get_bloginfo( 'url' );
    }

} // END CLASS NF_MergeTags_System
