<?php if ( ! defined( 'ABSPATH' ) ) exit;

require_once Ninja_Forms::$dir . 'includes/Libraries/BackgroundProcessing/wp-background-processing.php';

final class NF_Database_PublishProcessing extends WP_Background_Process
{
    protected $action = 'ninja-forms-publish';

    protected function task( $item )
    {
        if( ! isset( $item[ 'id' ]       ) ) return false;
        if( ! isset( $item[ 'type' ]     ) ) return false;
        if( ! isset( $item[ 'settings' ] ) ) return false;

        switch ( $item[ 'type' ] ){
            case 'field':
                $object = Ninja_Forms()->form()->get_field( $item[ 'id' ] );
                break;
            default:
                return false;
        }

        $object->update_settings( $item[ 'settings' ] )->save();

        return false;
    }
}