<?php

namespace WPML\Legacy\Translation\Save\SyncParentPost;

/**
 *
 * Class containing the logic that we run when manipulating translations regarding synchronization of child-parent translated posts.
 */
class SyncParentPost {

	/**
	 * @var bool
	 */
	private $isSyncPageParentEnabled;

	/**
	 * @var \WPML_Post_Translation
	 */
	private $wpml_post_translations;

	/**
	 * @var \wpdb
	 */
	private $wpdb;

	public function __construct( \wpdb $wpdb, \SitePress $sitepress, \WPML_Post_Translation $wpml_post_translations ) {
		$this->isSyncPageParentEnabled = $sitepress->get_setting( 'sync_page_parent' );
		$this->wpml_post_translations  = $wpml_post_translations;
		$this->wpdb					= $wpdb;
	}

	/**
	 * @param int|null $original_parent_post_id
	 * @param string $language_code
	 * @param array $postarr
	 * @return array
	 */
	public function linkParentTranslatedPostOrFlagOriginal( $original_parent_post_id, $language_code, $postarr ) {
		if ( ! $this->isSyncPageParentEnabled ) {
			return $postarr;
		}
		if ( $original_parent_post_id ) {
			$translated_parent_id = $this->wpml_post_translations->element_id_in( $original_parent_post_id, $language_code );
			if ( isset( $translated_parent_id ) ) {

				// Replace the post_parent and parent_id keys with the translated parent id.
				// Either in the $_POST data, which may be used by WordPress or 3rd parties.
				// And $postarr, which is used later internally to fire some actions and run other logic.
				$_POST['post_parent'] = $postarr['post_parent'] = $translated_parent_id;
				$_POST['parent_id']   = $postarr['parent_id'] = $translated_parent_id;

			} else {
				// If the parent post has not been translated yet, we will mark it with a post_meta containing the original parent id.
				update_post_meta( $original_parent_post_id, $this->getMetaChildKey( $language_code ), true);
			}
		}

		return $postarr;
	}

	/**
	 * @param int $original_post_id
	 * @param string $language_code
	 * @param int $translated_post_id
	 */
	public function linkUnlinkedChildPosts( $original_post_id, $language_code, $translated_post_id ) {
		if ( ! $this->isSyncPageParentEnabled ) {
			return;
		}
		$hasUnlinkedChilds = get_post_meta( $original_post_id, $this->getMetaChildKey( $language_code ), true );

		if ( ! $hasUnlinkedChilds ) {
			return;
		}

		// Get all the child posts of the original post and preload them in batch, to avoid multiple queries.
		$query = $this->wpdb->prepare( "SELECT ID FROM {$this->wpdb->posts} WHERE post_parent = %d", $original_post_id );
		$original_child_post_ids = $this->wpdb->get_col($query);

		if ( empty( $original_child_post_ids ) ) {
			// There are not child posts anymore, so we can return.
			delete_post_meta( $original_post_id, $this->getMetaChildKey( $language_code ) );
			return;
		}

		$this->wpml_post_translations->prefetch_ids( $original_child_post_ids );
		foreach ( $original_child_post_ids as $original_child_post_id ) {
			$translated_child_post_id = $this->wpml_post_translations->element_id_in( $original_child_post_id, $language_code );
			if ( isset( $translated_child_post_id ) ) {
				wp_update_post( array(
					'ID' => $translated_child_post_id,
					'post_parent' => $translated_post_id,
				) );
			}
		}

		// We delete the post_meta, since we have already linked all the pending child posts for this language.
		delete_post_meta( $original_post_id, $this->getMetaChildKey( $language_code ) );
	}


	/**
	 * Generates a key for the post_meta that will be used to flag the original post as having unlinked child posts, per language.
	 * @param string $language_code
	 * @return string
	 */
	private function getMetaChildKey( $language_code ) {
		return sprintf( '_wpml_has_%s_unlinked_childs', $language_code );
	}
}