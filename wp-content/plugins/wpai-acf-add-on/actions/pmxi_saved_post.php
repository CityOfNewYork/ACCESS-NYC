<?php
/**
 * Copy meta from a post to it's latest revision.
 *
 * @param $pid
 */
function pmai_pmxi_saved_post($pid) {
    $post_type = get_post_type($pid);
    if( $post_type && post_type_supports($post_type, 'revisions') ) {
        acf_save_post_revision($pid);
    }
}