<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * This module utilizes deprecated code, which should eventually be replaced.
 */

require_once Ninja_Forms::$dir . 'lib/StepProcessing/step-processing.php';

class NF_Admin_CPT_DownloadAllSubmissions extends NF_Step_Processing {

    function __construct() {
        $this->action = 'download_all_subs';

        parent::__construct();

        add_action( 'admin_footer-edit.php', array( $this, 'bulk_admin_footer' ) );
    }

    public function loading() {
        $form_id  = isset( $this->args['form_id'] ) ? absint( $this->args['form_id'] ) : 0;

        if ( empty( $form_id ) ) {

            return array( 'complete' => true );
        }

        $sub_count = $this->get_sub_count( $form_id );

        if( empty( $this->total_steps ) || $this->total_steps <= 1 ) {
            $this->total_steps = round( ( $sub_count / 250 ), 0 ) + 2;
        }

        $args = array(
            'total_steps' => $this->total_steps,
        );

        $this->args['filename'] = $this->random_filename( 'all-subs' );
        update_user_option( get_current_user_id(), 'nf_download_all_subs_filename', $this->args['filename'] );
        $this->redirect = esc_url_raw( add_query_arg( array( 'download_all' => $this->args['filename'] ), $this->args['redirect'] ) );

        $this->loaded = true;
        return $args;
    }

    public function step() {
        if( ! is_numeric( $this->args[ 'form_id' ] ) ){
            wp_die( __( 'Invalid form id', 'ninja-forms' ) );
        }

        $this->args[ 'filename' ] = wp_kses_post( $this->args[ 'filename' ] );

        $exported_subs = get_user_option( get_current_user_id(), 'nf_download_all_subs_ids' );
        if ( ! is_array( $exported_subs ) ) {
            $exported_subs = array();
        }

        $previous_name = get_user_option( get_current_user_id(), 'nf_download_all_subs_filename' );
        if ( $previous_name ) {
            $this->args['filename'] = $previous_name;
        }

        $args = array(
            'posts_per_page' => 250,
            'paged' => $this->step,
            'post_type' => 'nf_sub',
            'meta_query' => array(
                array(
                    'key' => '_form_id',
                    'value' => $this->args['form_id'],
                ),
            ),
        );

        $subs_results = get_posts( $args );

        if ( is_array( $subs_results ) && ! empty( $subs_results ) ) {
            $upload_dir = wp_upload_dir();
            $file_path = trailingslashit( $upload_dir['path'] ) . $this->args['filename'] . '.csv';
            $myfile = fopen( $file_path, 'a' ) or die( 'Unable to open file!' );
            $x = 0;
            $export = '';
            foreach ( $subs_results as $sub ) {
                $sub_export = NF_Database_Models_Submission::export( $this->args['form_id'], array( $sub->ID ), TRUE );
                if ( $x > 0 || $this->step > 1 ) {
                    $sub_export = substr( $sub_export, strpos( $sub_export, "\n" ) + 1 );
                }
                if ( ! in_array( $sub->ID, $exported_subs ) ) {
                    $export .= $sub_export;
                    $exported_subs[] = $sub->ID;
                }
                $x++;
            }
            fwrite( $myfile, $export );
            fclose( $myfile );
        }

        update_user_option( get_current_user_id(), 'nf_download_all_subs_ids', $exported_subs );
    }

    public function complete() {
        delete_user_option( get_current_user_id(), 'nf_download_all_subs_ids' );
        delete_user_option( get_current_user_id(), 'nf_download_all_subs_filename' );
    }

    /**
     * Add an integar to the end of our filename to make sure it is unique
     *
     * @access public
     * @since 2.7.6
     * @return $filename
     */
    public function random_filename( $filename ) {
        $upload_dir = wp_upload_dir();
        $file_path = trailingslashit( $upload_dir['path'] ) . $filename . '.csv';
        if ( file_exists ( $file_path ) ) {
            for ($x = 0; $x < 999 ; $x++) {
                $tmp_name = $filename . '-' . $x;
                $tmp_path = trailingslashit( $upload_dir['path'] );
                if ( file_exists( $tmp_path . $tmp_name . '.csv' ) ) {
                    $this->random_filename( $tmp_name );
                    break;
                } else {
                    $this->filename = $tmp_name;
                    break;
                }
            }
        }

        return $filename;
    }

    public function bulk_admin_footer() {
        global $post_type;

        if ( ! is_admin() )
            return false;

        if( $post_type == 'nf_sub' && isset ( $_REQUEST['post_status'] ) && $_REQUEST['post_status'] == 'all' ) {
            ?>
            <script type="text/javascript">
                jQuery(document).ready(function() {
                    jQuery('<option>').val('export').text('<?php _e('Export')?>').appendTo("select[name='action']");
                    jQuery('<option>').val('export').text('<?php _e('Export')?>').appendTo("select[name='action2']");
                    <?php
                    if ( ( isset ( $_POST['action'] ) && $_POST['action'] == 'export' ) || ( isset ( $_POST['action2'] ) && $_POST['action2'] == 'export' ) ) {
                    ?>
                    setInterval(function(){
                        jQuery( "select[name='action'" ).val( '-1' );
                        jQuery( "select[name='action2'" ).val( '-1' );
                        jQuery( '#posts-filter' ).submit();
                    },5000);
                    <?php
                    }

                    if ( isset ( $_REQUEST['form_id'] ) && ! empty ( $_REQUEST['form_id'] ) ) {
                    $redirect = urlencode( remove_query_arg( array( 'download_all', 'download_file' ) ) );
                    $url = admin_url( 'admin.php?page=nf-processing&action=download_all_subs&form_id=' . absint( $_REQUEST['form_id'] ) . '&redirect=' . $redirect );
                    $url = esc_url( $url );
                    ?>
                    var button = '<a href="<?php echo $url; ?>" class="button-secondary nf-download-all"><?php echo __( 'Download All Submissions', 'ninja-forms' ); ?></a>';
                    jQuery( '#doaction2' ).after( button );
                    <?php
                    }

                    if ( isset ( $_REQUEST['download_all'] ) && $_REQUEST['download_all'] != '' ) {
                    $redirect = esc_url_raw( add_query_arg( array( 'download_file' => esc_html( $_REQUEST['download_all'] ) ) ) );
                    $redirect = remove_query_arg( array( 'download_all' ), $redirect );
                    ?>
                    document.location.href = "<?php echo $redirect; ?>";
                    <?php
                    }

                    ?>
                });
            </script>
            <?php
        }
    }

    function get_sub_count( $form_id, $post_status = 'publish' ) {
        global $wpdb;

        $meta_key = '_form_id';
        $meta_value = $form_id;

        $sql = "SELECT count(DISTINCT pm.post_id)
	FROM $wpdb->postmeta pm
	JOIN $wpdb->posts p ON (p.ID = pm.post_id)
	WHERE pm.meta_key = %s
	AND pm.meta_value = %s
	AND p.post_type = 'nf_sub'
	AND p.post_status = %s";

        $count = $wpdb->get_var( $wpdb->prepare( $sql, $meta_key, $meta_value, $post_status ) );

        return $count;
    }

}