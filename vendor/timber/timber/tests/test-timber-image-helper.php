<?php

	class TestTimberImageHelper extends TimberImage_UnitTestCase {

		function testHTTPAnalyze() {
			$url = 'http://example.org/wp-content/uploads/2017/06/dog.jpg';
			$info = Timber\ImageHelper::analyze_url($url);
			$this->assertEquals('/2017/06', $info['subdir']);
		}

		function testHTTPSAnalyze() {
			$url = 'https://example.org/wp-content/uploads/2017/06/dog.jpg';
			$info = Timber\ImageHelper::analyze_url($url);
			$this->assertEquals('/2017/06', $info['subdir']);
		}

		function testIsAnimatedGif() {
			$image = TestTimberImage::copyTestImage('robocop.gif');
			$this->assertTrue( TimberImageHelper::is_animated_gif($image) );
		}

		function testIsRegularGif() {
			$image = TestTimberImage::copyTestImage('boyer.gif');
			$this->assertFalse( TimberImageHelper::is_animated_gif($image) );
		}

		function testIsNotGif() {
			$arch = TestTimberImage::copyTestImage('arch.jpg');
			$this->assertFalse( TimberImageHelper::is_animated_gif($arch) );
		}

		function testIsSVG() {
			$image = TestTimberImage::copyTestImage('timber-logo.svg');
			$this->assertTrue( TimberImageHelper::is_svg( $image ) );
		}

		function testServerLocation() {
			$arch = TestTimberImage::copyTestImage('arch.jpg');
			$this->assertEquals($arch, \Timber\ImageHelper::get_server_location($arch));
		}

		/**
     	 * @dataProvider customDirectoryData
     	 */
		function testCustomWordPressDirectoryStructure($template, $size) {
			$this->setupCustomWPDirectoryStructure();

			$upload_dir = wp_upload_dir();
			$post_id = self::factory()->post->create();
			$filename = TestTimberImage::copyTestImage( 'flag.png' );
			$destination_url = str_replace( ABSPATH, 'http://'.$_SERVER['HTTP_HOST'].'/', $filename );
			$wp_filetype = wp_check_filetype( basename( $filename ), null );
			$attachment = array(
				'post_mime_type' => $wp_filetype['type'],
				'post_title' => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
				'post_content' => '',
				'post_status' => 'inherit'
			);
			$attach_id = wp_insert_attachment( $attachment, $filename, $post_id );
			add_post_meta( $post_id, '_thumbnail_id', $attach_id, true );
			$data = array();
			$data['post'] = new TimberPost( $post_id );
			$data['size'] = $size;
			$data['crop'] = 'default';
			Timber::compile( $template, $data );

			$this->tearDownCustomWPDirectoryStructure();

			$exists = file_exists( $filename );
			$this->assertTrue( $exists );
			$resized_path = $upload_dir['path'].'/flag-'.$data['size']['width'].'x'.$data['size']['height'].'-c-'.$data['crop'].'.png';
			$exists = file_exists( $resized_path );
			$this->assertTrue( $exists );
		}

		function testDeleteSideloadedFile() {
			$filename = 'acGwPDj4_400x400';
			$img = Timber\ImageHelper::sideload_image('https://pbs.twimg.com/profile_images/768086933310476288/'.$filename.'.jpg');
			$files = scandir('/tmp');
			$matches = false;
			foreach ($files as $file) {
				$substr = substr($file, 0, strlen($filename));
				if ( $substr == $filename ) {
					$matches = true;
				}
			}
			$this->assertFalse($matches);
		}

		/**
		 * @doesNotPerformAssertions
		 */
		function testDeleteFalseFile() {
			TimberImageHelper::delete_generated_files('/etc/www/image.jpg');
		}

		function testLetterbox() {
			$file_loc = TestTimberImage::copyTestImage( 'eastern.jpg' );
			$upload_dir = wp_upload_dir();
			$image = $upload_dir['url'].'/eastern.jpg';
			$new_file = TimberImageHelper::letterbox( $image, 500, 500, '#CCC', true );
			$location_of_image = TimberImageHelper::get_server_location( $new_file );
			$this->addFile( $location_of_image );
			$this->assertTrue (TestTimberImage::checkSize($location_of_image, 500, 500));
			//whats the bg/color of the image
			$this->assertTrue( TestTimberImage::checkPixel($location_of_image, 1, 1, "#CCC") );
		}

		function customDirectoryData() {
			return [
				[
					'assets/thumb-test.twig',
					['width' => 100, 'height' => 50]
				], [
					'assets/thumb-test-relative.twig',
					['width' => 50, 'height' => 100]
				]
			];
		}

	}
