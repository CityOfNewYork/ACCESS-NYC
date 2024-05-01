<?php

	class TestTimberPostComments extends Timber_UnitTestCase {

		function testComments() {
			$post_id = self::factory()->post->create(array('post_title' => 'Gobbles'));
			$comment_id_array = self::factory()->comment->create_many( 5, array('comment_post_ID' => $post_id) );
			$post = new TimberPost($post_id);
			$this->assertEquals( 5, count($post->comments()) );
			$this->assertEquals( 5, $post->get_comment_count() );
		}

		function testCommentCount() {
			$post_id = self::factory()->post->create(array('post_title' => 'Gobbles'));
			$comment_id_array = self::factory()->comment->create_many( 5, array('comment_post_ID' => $post_id) );
			$post = new TimberPost($post_id);
			$this->assertEquals( 2, count($post->comments(2)) );
			$this->assertEquals( 5, $post->get_comment_count() );
		}

		function testCommentCountZero() {
			$quote = 'Named must your fear be before banish it you can.';
            $post_id = self::factory()->post->create(array('post_content' => $quote));
            $post = Timber::get_post($post_id);
            $this->assertEquals(0, $post->get_comment_count());
		}

		function testShowUnmoderatedCommentIfByLoggedInUser() {
			$post_id = self::factory()->post->create();
			$uid = self::factory()->user->create();
			wp_set_current_user( $uid );
			$quote = "You know, I always wanted to pretend I was an architect";
			$comment_id = self::factory()->comment->create(array('comment_post_ID' => $post_id, 'comment_content' => $quote, 'user_id' => $uid, 'comment_approved' => 0));
			$post = new TimberPost($post_id);
			$this->assertEquals(1, count($post->comments()));
			wp_set_current_user( 0 );
			$post = new TimberPost($post_id);
			$this->assertEquals(0, count($post->comments()));
		}

		function testPostWithCustomCommentClass() {
			require_once(__DIR__.'/php/timber-custom-comment.php');
			$post_id = self::factory()->post->create(array('post_title' => 'Gobbles'));
			$comment_id_array = self::factory()->comment->create_many( 5, array('comment_post_ID' => $post_id) );
			$post = new TimberPost($post_id);
			$comments = $post->get_comments(null, 'wp', 'comment', 'approve', 'CustomComment');
			$this->assertEquals('CustomComment', get_class($comments[0]));
		}

		function testShowUnmoderatedCommentIfByCurrentUser() {
			$post_id = self::factory()->post->create();
			add_filter('wp_get_current_commenter', function($author_data) {
				$author_data['comment_author_email'] = 'jarednova@upstatement.com';
				return $author_data;
			});
			$commenter = wp_get_current_commenter();
			$quote = "And in that moment, I was a marine biologist";
			$comment_id = self::factory()->comment->create(array('comment_post_ID' => $post_id, 'comment_content' => $quote,'comment_approved' => 0, 'comment_author_email' => 'jarednova@upstatement.com'));
			$post = new TimberPost($post_id);
			$this->assertEquals(1, count($post->comments()));
		}



		function testMultilevelThreadedComments() {
			update_option('comment_order', 'ASC');
			$post_id = self::factory()->post->create(array('post_title' => 'Gobbles'));
			$comment_id = self::factory()->comment->create(array('comment_post_ID' => $post_id));
			$child_id = self::factory()->comment->create(array('comment_post_ID' => $post_id, 'comment_parent' => $comment_id));
			$grandchild_id = self::factory()->comment->create(array('comment_post_ID' => $post_id, 'comment_parent' => $child_id));
			$grandchild_id = self::factory()->comment->create(array('comment_post_ID' => $post_id, 'comment_parent' => $child_id));
			$post = new TimberPost($post_id);
			$comments = $post->get_comments();
			$this->assertEquals(1, count($comments));
			$children = $comments[0]->children();
			$this->assertEquals(1, count($children));
			$grand_children = $children[0]->children();
			$this->assertEquals(2, count($grand_children));
		}

		function testMultilevelThreadedCommentsCorrectParents(){
			update_option('comment_order', 'ASC');
			$post_id = self::factory()->post->create(array('post_title' => 'Gobbles', 'post_date' => '2016-11-28 12:00:00'));
			$uncle_id = self::factory()->comment->create(array('comment_post_ID' => $post_id, 'comment_date' => '2016-11-28 13:00:00', 'comment_content' => 'i am the UNCLE'));
			$parent_id = self::factory()->comment->create(array('comment_post_ID' => $post_id, 'comment_date' => '2016-11-28 14:00:00', 'comment_content' => 'i am the Parent'));
			$child_id = self::factory()->comment->create(array('comment_post_ID' => $post_id, 'comment_parent' => $parent_id, 'comment_date' => '2016-11-28 15:00:00', 'comment_content' => 'I am the child'));
			$grandchild_id = self::factory()->comment->create(array('comment_post_ID' => $post_id, 'comment_parent' => $child_id, 'comment_date' => '2016-11-28 16:00:00', 'comment_content' => 'I am the GRANDchild'));
			$post = new TimberPost($post_id);
			$comments = $post->get_comments();
			$children = $comments[1]->children();
			$this->assertEquals($parent_id, $children[0]->comment_parent);
			$grand_children = $children[0]->children();
			$grandchild = $grand_children[0];
			$this->assertEquals($child_id, $grandchild->comment_parent);
		}

		function testThreadedCommentsWithTemplate() {
			$post_id = self::factory()->post->create(array('post_title' => 'Gobbles'));
			$comment_id = self::factory()->comment->create(array('comment_post_ID' => $post_id, 'comment_content' => 'first!', 'comment_date' => '2016-11-28 12:58:18'));
			$comment2_id = self::factory()->comment->create(array('comment_post_ID' => $post_id, 'comment_content' => 'second!', 'comment_date' => '2016-11-28 13:58:18'));
			$comment2_child_id = self::factory()->comment->create(array('comment_post_ID' => $post_id, 'comment_parent' => $comment2_id, 'comment_content' => 'response', 'comment_date' => '2016-11-28 14:58:18'));
			$comment2_grandchild_id = self::factory()->comment->create(array('comment_post_ID' => $post_id, 'comment_parent' => $comment2_child_id, 'comment_content' => 'Respond2Respond', 'comment_date' => '2016-11-28 15:58:18'));
			$post = new TimberPost($post_id);
			$str = Timber::compile('assets/comments-thread.twig', array('post' => $post));
		}

	}
