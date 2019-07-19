<?php

class S3_Uploads_Image_Editor_Imagick extends WP_Image_Editor_Imagick {

	protected $temp_file_to_cleanup = null;

	/**
	 * Hold on to a reference of all temp local files.
	 *
	 * These are cleaned up on __destruct.
	 *
	 * @var array
	 */
	protected $temp_files_to_cleanup = array();

	/**
	 * Loads image from $this->file into new Imagick Object.
	 *
	 * @return true|WP_Error True if loaded; WP_Error on failure.
	 */
	public function load() {
		if ( $this->image instanceof Imagick ) {
			return true;
		}

		if ( ! is_file( $this->file ) && ! preg_match( '|^https?://|', $this->file ) ) {
			return new WP_Error( 'error_loading_image', __( 'File doesn&#8217;t exist?' ), $this->file );
		}

		$upload_dir = wp_upload_dir();

		if ( strpos( $this->file, $upload_dir['basedir'] ) !== 0 ) {
			return parent::load();
		}

		$temp_filename = tempnam( get_temp_dir(), 's3-uploads' );
		$this->temp_files_to_cleanup[] = $temp_filename;

		copy( $this->file, $temp_filename );
		$this->remote_filename = $this->file;
		$this->file = $temp_filename;

		$result = parent::load();

		$this->file = $this->remote_filename;
		return $result;
	}

	/**
	 * Imagick by default can't handle s3:// paths
	 * for saving images. We have instead save it to a file file,
	 * then copy it to the s3:// path as a workaround.
	 */
	protected function _save( $image, $filename = null, $mime_type = null ) {
		list( $filename, $extension, $mime_type ) = $this->get_output_format( $filename, $mime_type );

		if ( ! $filename ) {
			$filename = $this->generate_filename( null, null, $extension );
		}

		$upload_dir = wp_upload_dir();

		if ( strpos( $filename, $upload_dir['basedir'] ) === 0 ) {
			$temp_filename = tempnam( get_temp_dir(), 's3-uploads' );
		}

		$save = parent::_save( $image, $temp_filename, $mime_type );

		if ( is_wp_error( $save ) ) {
			unlink( $temp_filename );
			return $save;
		}

		$copy_result = copy( $save['path'], $filename );

		unlink( $save['path'] );
		unlink( $temp_filename );

		if ( ! $copy_result ) {
			return new WP_Error( 'unable-to-copy-to-s3', 'Unable to copy the temp image to S3' );
		}

		return array(
			'path'      => $filename,
			'file'      => wp_basename( apply_filters( 'image_make_intermediate_size', $filename ) ),
			'width'     => $this->size['width'],
			'height'    => $this->size['height'],
			'mime-type' => $mime_type,
		);
	}

	public function __destruct() {
		array_map( 'unlink', $this->temp_files_to_cleanup );
		parent::__destruct();
	}
}
