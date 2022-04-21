<?php

namespace WPML\Ajax\Endpoint;

use WPML\Ajax\IHandler;
use WPML\Collect\Support\Collection;
use WPML\FP\Either;
use WPML\FP\Fns;
use WPML\FP\Obj;
use function WPML\FP\curryN;

class Upload implements IHandler {

	public function run( Collection $data ) {

		$upload = curryN( 2, function ( $path, $file ) {
			$upload_dir = function ( $uploads ) use ( $path ) {
				if ( $path ) {
					$uploads['path']   = $uploads['basedir'] . '/' . $path;
					$uploads['url']    = $uploads['baseurl'] . '/' . $path;
					$uploads['subdir'] = $path;
				}

				return $uploads;
			};

			add_filter( 'upload_dir', $upload_dir );
			$upload = wp_handle_upload( $file, [ 'test_form' => false ] );
			remove_filter( 'upload_dir', $upload_dir );

			return $upload;
		} );

		$resize = curryN( 2, function ( $settings, $file ) {
			list( $max_width, $max_height, $crop ) = Obj::props( [ 'max_w', 'max_h', 'crop' ], $settings );

			if ( $max_width && $max_height && function_exists( 'wp_get_image_editor' ) && 'image/svg+xml' !== $file['type'] ) {
				$image = wp_get_image_editor( $file['file'] );
				if ( ! is_wp_error( $image ) ) {
					$image->resize( $max_width, $max_height, (bool) $crop );
					$image->save( $file['file'] );
				}
			}

			return $file;
		} );

		$handleError = function ( $file ) {
			if ( Obj::has( 'error', $file ) ) {
				$error_message = __( 'There was an error uploading the file, please try again!', 'sitepress' );
				switch ( $file['error'] ) {
					case UPLOAD_ERR_INI_SIZE;
						$error_message = __( 'The uploaded file exceeds the upload_max_filesize directive in php.ini.', 'sitepress' );
						break;
					case UPLOAD_ERR_FORM_SIZE;
						$error_message = sprintf( __( 'The uploaded file exceeds %s bytes.', 'sitepress' ), 100000 );
						break;
					case UPLOAD_ERR_PARTIAL;
						$error_message = __( 'The uploaded file was only partially uploaded.', 'sitepress' );
						break;
					case UPLOAD_ERR_NO_FILE;
						$error_message = __( 'No file was uploaded.', 'sitepress' );
						break;
					case UPLOAD_ERR_NO_TMP_DIR;
						$error_message = __( 'Missing a temporary folder.', 'sitepress' );
						break;
					case UPLOAD_ERR_CANT_WRITE;
						$error_message = __( 'Failed to write file to disk.', 'sitepress' );
						break;
					case UPLOAD_ERR_EXTENSION;
						$error_message = __( 'A PHP extension stopped the file upload. PHP does not provide a way to ascertain which extension caused the file upload to stop; examining the list of loaded extensions with phpinfo() may help.', 'sitepress' );
						break;
				}

				return Either::left( $error_message );
			} else {
				return Either::right( $file );
			}
		};

		return Either::fromNullable( $data->get( 'name' ) )
		             ->map( Obj::prop( Fns::__, $_FILES ) )
		             ->map( $upload( $data->get( 'path' ) ) )
		             ->chain( $handleError )
		             ->map( $resize( $data->get( 'resize', [] ) ) );
	}

}
