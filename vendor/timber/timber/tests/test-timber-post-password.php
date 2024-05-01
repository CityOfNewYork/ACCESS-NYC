<?php

	class TestTimberPostPassword extends Timber_UnitTestCase {

		function testPasswordedContentDefault(){
			$quote = 'The way to do well is to do well.';
			$post_id = self::factory()->post->create();
			$post = new TimberPost($post_id);
			$post->post_content = $quote;
			$post->post_password = 'burrito';
			wp_update_post($post);
			$password_form = get_the_password_form($post->ID);
			$this->assertEquals(wpautop($quote), $post->content());
			$this->assertEquals(wpautop($quote), $post->get_content());
		}

		function testPasswordedContentWhenEnabled(){
			add_filter('timber/post/content/show_password_form_for_protected', function($maybe_show) {
				return true;
			});
			$quote = 'The way to do well is to do well.';
			$post_id = self::factory()->post->create();
			$post = new TimberPost($post_id);
			$post->post_content = $quote;
			$post->post_password = 'burrito';
			wp_update_post($post);
			$password_form = get_the_password_form($post->ID);
			$this->assertEquals($password_form, $post->content());
			$this->assertEquals($password_form, $post->get_content());
		}

		function testPasswordedContentWhenEnabledWithCustomForm(){
			add_filter('timber/post/content/show_password_form_for_protected', function($maybe_show) {
				return true;
			});
			add_filter('timber/post/content/password_form', function($form, $post){
				return Timber::compile('assets/password-form.twig', array('post' => $post));
			}, 10, 2);
			$quote = 'The way to do well is to do well.';
			$post_id = self::factory()->post->create(array('post_title' => 'Secrets!'));
			$post = new TimberPost($post_id);
			$post->post_content = $quote;
			$post->post_password = 'burrito';
			wp_update_post($post);
			$password_form = '<form>Enter password to see Secrets!</form>';
			$this->assertEquals($password_form, $post->content());
			$this->assertEquals($password_form, $post->get_content());
		}

	}
