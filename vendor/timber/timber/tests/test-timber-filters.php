<?php

class TestTimberFilters extends Timber_UnitTestCase {

	function testPostMetaFieldFilter() {
		$post_id = self::factory()->post->create();
		update_post_meta( $post_id, 'Frank', 'Drebin' );
		$tp = new TimberPost( $post_id );
		add_filter( 'timber_post_get_meta_field', array( $this, 'filter_timber_post_get_meta_field' ), 10, 4 );
		$this->assertEquals( 'Drebin', $tp->meta( 'Frank' ) );
		remove_filter( 'timber_post_get_meta_field', array( $this, 'filter_timber_post_get_meta_field' ) );
	}

	function filter_timber_post_get_meta_field( $value, $pid, $field_name, $timber_post ) {
		$this->assertEquals( 'Frank', $field_name );
		$this->assertEquals( 'Drebin', $value );
		$this->assertSame( $timber_post->ID, $pid );
		return $value;
	}

	function testCommentMetaFilter() {
		$post_id = self::factory()->post->create();
		$comment_id = self::factory()->comment->create( array( 'comment_post_ID' => $post_id ) );
		$comment = new TimberComment( $comment_id );
		$comment->update( 'ghost', 'busters' );
		add_filter( 'timber_comment_get_meta_field', array( $this, 'filter_timber_comment_get_meta_field' ), 10, 4 );
		$this->assertEquals( $comment->meta( 'ghost' ), 'busters' );
		remove_filter( 'timber_comment_get_meta_field', array( $this, 'filter_timber_comment_get_meta_field' ) );
	}

	function filter_timber_comment_get_meta_field( $value, $cid, $field_name, $timber_comment ) {
		$this->assertEquals( $field_name, 'ghost' );
		$this->assertEquals( $value, 'busters' );
		$this->assertEquals( $cid, $timber_comment->ID );
		return $value;
	}

	function testUserMetaFilter() {
		$uid = self::factory()->user->create();
		$user = new TimberUser( $uid );
		$user->update( 'jared', 'novack' );
		add_filter( 'timber_user_get_meta_field', array( $this, 'filter_timber_user_get_meta_field' ), 10, 4 );
		$this->assertEquals( $user->meta( 'jared' ), 'novack' );
		remove_filter( 'timber_user_get_meta_field', array( $this, 'filter_timber_user_get_meta_field' ) );
	}

	function filter_timber_user_get_meta_field( $value, $uid, $field_name, $timber_user ) {
		$this->assertEquals( 'jared', $field_name );
		$this->assertEquals( 'novack', $value );
		$this->assertEquals( $timber_user->ID, $uid );
		return $value;
	}

	function testTermMetaFilter() {
		$tid = self::factory()->term->create();
		$term = new TimberTerm( $tid );
		add_filter( 'timber_term_get_meta_field', array( $this, 'filter_timber_term_get_meta_field' ), 10, 4 );
		$term->meta( 'panic', 'oh yeah' );
		remove_filter( 'timber_term_get_meta_field', array( $this, 'filter_timber_term_get_meta_field' ) );
	}

	function filter_timber_term_get_meta_field( $value, $tid, $field_name, $timber_term ) {
		$this->assertEquals( $tid, $timber_term->ID );
		$this->assertEquals( $field_name, 'panic' );
		return $value;
	}

	function testRenderDataFilter() {
		add_filter('timber/loader/render_data', array($this, 'filter_timber_render_data'), 10, 2);
		$output = Timber::compile('assets/output.twig', array('output' => 14) );
		$this->assertEquals('output.twig assets/output.twig', $output);
	}

	function filter_timber_render_data($data, $file) {
		$data['output'] = $file;
		return $data;
	}

	function testOutputFilter() {
		add_filter('timber/output', array($this, 'filter_timber_output'), 10, 3);
		$output = Timber::compile('assets/single.twig', array('number' => 14) );
		$this->assertEquals('assets/single.twig14', $output);
	}

	function filter_timber_output( $output, $data, $file ) {
		return $file . $data['number'];
	}

	function testReadMoreLinkFilter() {
		$link = "Foobar";
		add_filter( 'timber/post/get_preview/read_more_link', array( $this, 'filter_timber_post_get_preview_read_more_link' ), 10, 1 );
		$this->assertEquals( 'Foobar', apply_filters( 'timber/post/get_preview/read_more_link', $link ) );
		remove_filter( 'timber/post/get_preview/read_more_link', array( $this, 'filter_timber_post_get_preview_read_more_link' ) );
	}

	function filter_timber_post_get_preview_read_more_link( $link ) {
		return $link;
	}

}
