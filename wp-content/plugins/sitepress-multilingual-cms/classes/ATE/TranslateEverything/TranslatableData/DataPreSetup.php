<?php

namespace WPML\TM\ATE\TranslateEverything\TranslatableData;

use \wpdb;


class DataPreSetup {
	const POSTS_CHUNK_SIZE = 100;
	const TERMS_CHUNK_SIZE = 10000;

	const KEY_POST_TYPES = 'post_types';
	const KEY_TAXONOMIES = 'taxonomies';

	/** @var wpdb $db */
	private $db;

	/** @var Calculate $calculate */
	private $calculate;

	public function listTranslatableData() {
		// Top items will be fetched first.
		return [
			self::KEY_TAXONOMIES => [
				'category',
				'post_tag',
				'product_cat',
				'product_tag',
			],
			self::KEY_POST_TYPES => [
				'post',
				'page',
				'product',
			],
		];
	}

	/**
	 * @param wpdb      $db
	 * @param Calculate $calculate
	 *
	 * @return void
	 */
	public function __construct( wpdb $db, Calculate $calculate ) {
		$this->db        = $db;
		$this->calculate = $calculate;
	}

	public function fetch( Stack $stack ) {
		switch ( $stack->type() ) {
			case self::KEY_POST_TYPES:
				return $this->posts( $stack );
			case self::KEY_TAXONOMIES:
				return $this->terms( $stack );
		}

		throw new \InvalidArgumentException(
			'The stack type "' . $stack->type() . '" is not supported.'
		);
	}

	/**
	 * Calculates the words inside the posts of the given $type.
	 * This fetches a maximum of self::CHUNK_SIZE posts. To
	 * fetch all posts, multiple requests must be made by using the
	 * $offset parameter.
	 *
	 * The found data is applied to the given $stack.
	 *
	 * @param Stack $stack The stack to apply the data to.
	 *
	 * @return Stack
	 */
	private function posts( Stack $stack ) {
		$posts = $this->db->get_results(
			$this->db->prepare(
				"SELECT
					ID,
					post_content,
					post_title,
					post_excerpt
				FROM {$this->db->posts}
				WHERE `post_status` = 'publish'
				AND `post_type` = '%s'
				ORDER BY ID
				LIMIT %d, %d",
				$stack->name(),
				$stack->count(),
				self::POSTS_CHUNK_SIZE
			)
		);

		return $this->fillStack(
			$stack,
			$posts,
			function( $post ) {
				return $post->post_title .
					$post->post_content .
					$post->post_excerpt;
			},
			self::POSTS_CHUNK_SIZE
		);
	}


	/**
	 * Calculates the words of all terms in the given $taxonomy.
	 *
	 * The found data is applied to the given stack.
	 *
	 * @param Stack $stack The stack to apply the data to.
	 *
	 * @return Stack
	 */
	private function terms( Stack $stack ) {
		$terms = $this->db->get_results(
			$this->db->prepare(
				"SELECT name
				FROM {$this->db->terms} as t
				LEFT JOIN {$this->db->term_taxonomy} as tt
				ON t.term_id = tt.term_id
					WHERE tt.taxonomy = '%s'
					AND tt.count > 0
				LIMIT %d, %d",
				$stack->name(),
				$stack->count(),
				self::TERMS_CHUNK_SIZE
			)
		);

		return $this->fillStack(
			$stack,
			$terms,
			function( $term ) {
				return $term->name;
			},
			self::TERMS_CHUNK_SIZE
		);
	}

	/**
	 * @param Stack    $stack
	 * @param mixed    $dataSet
	 * @param callable $dataExtract
	 * @param int      $chunkSize
	 *
	 * @return Stack
	 */
	private function fillStack( Stack $stack, $dataSet, $dataExtract, $chunkSize ) {
		if ( ! is_array( $dataSet ) || count( $dataSet ) === 0 ) {
			// No data to add. Mark stack as completed.
			$stack->completed();
			return $stack;
		}

		if ( count( $dataSet ) < $chunkSize ) {
			// No more data to fetch.
			$stack->completed();
		}

		$stack->addCount( count( $dataSet ) );

		foreach ( $dataSet as $data ) {
			$stack->addWords( $this->calculate->words( $dataExtract( $data ) ) );
		}

		return $stack;
	}
}

