<?php

// Compatibility functions for old version of WordPress / PHP / Other


/*
* Introduced in  WP 4.9.7 - https://developer.wordpress.org/reference/functions/wp_delete_attachment_files/
* Compat for previous versions.
*/
if (! function_exists('wp_delete_attachment_files'))
{
  function wp_delete_attachment_files($post_id, $meta, $backup_sizes, $file )
  {
    global $wpdb;
  $uploadpath = wp_get_upload_dir();
  $deleted    = true;

    if ( ! empty( $meta['thumb'] ) ) {
        // Don't delete the thumb if another attachment uses it.
        if ( ! $wpdb->get_row( $wpdb->prepare( "SELECT meta_id FROM $wpdb->postmeta WHERE meta_key = '_wp_attachment_metadata' AND meta_value LIKE %s AND post_id <> %d", '%' . $wpdb->esc_like( $meta['thumb'] ) . '%', $post_id ) ) ) {
            $thumbfile = str_replace( wp_basename( $file ), $meta['thumb'], $file );
            if ( ! empty( $thumbfile ) ) {
                $thumbfile = path_join( $uploadpath['basedir'], $thumbfile );
                $thumbdir  = path_join( $uploadpath['basedir'], dirname( $file ) );

                if ( ! wp_delete_file_from_directory( $thumbfile, $thumbdir ) ) {
                    $deleted = false;
                }
            }
        }
    }

    // Remove intermediate and backup images if there are any.
    if ( isset( $meta['sizes'] ) && is_array( $meta['sizes'] ) ) {
        $intermediate_dir = path_join( $uploadpath['basedir'], dirname( $file ) );
        foreach ( $meta['sizes'] as $size => $sizeinfo ) {
            $intermediate_file = str_replace( wp_basename( $file ), $sizeinfo['file'], $file );
            if ( ! empty( $intermediate_file ) ) {
                $intermediate_file = path_join( $uploadpath['basedir'], $intermediate_file );

                if ( ! wp_delete_file_from_directory( $intermediate_file, $intermediate_dir ) ) {
                    $deleted = false;
                }
            }
        }
    }

    if ( is_array( $backup_sizes ) ) {
        $del_dir = path_join( $uploadpath['basedir'], dirname( $meta['file'] ) );
        foreach ( $backup_sizes as $size ) {
            $del_file = path_join( dirname( $meta['file'] ), $size['file'] );
            if ( ! empty( $del_file ) ) {
                $del_file = path_join( $uploadpath['basedir'], $del_file );

                if ( ! wp_delete_file_from_directory( $del_file, $del_dir ) ) {
                    $deleted = false;
                }
            }
        }
    }

    if ( ! wp_delete_file_from_directory( $file, $uploadpath['basedir'] ) ) {
        $deleted = false;
    }

    return $deleted;

  }
} // end function


/*
* Introduced in  WP 4.9.7 - https://developer.wordpress.org/reference/functions/wp_delete_attachment_files/
* Compat for previous versions.
*/
if (! function_exists('wp_delete_file_from_directory'))
{
  function wp_delete_file_from_directory( $file, $directory ) {
    if ( wp_is_stream( $file ) ) {
        $real_file      = wp_normalize_path( $file );
        $real_directory = wp_normalize_path( $directory );
    } else {
        $real_file      = realpath( wp_normalize_path( $file ) );
        $real_directory = realpath( wp_normalize_path( $directory ) );
    }

    if ( false === $real_file || false === $real_directory || strpos( $real_file, trailingslashit( $real_directory ) ) !== 0 ) {
        return false;
    }

    wp_delete_file( $file );

    return true;
  }

} // end function


/*
* Introduced in WP 4.5.0 - needed for compat function of wp_delete_attachment_files
*/
if (! function_exists('wp_get_upload_dir'))
{
  function wp_get_upload_dir() {
	         return wp_upload_dir( null, false );
         }
}
