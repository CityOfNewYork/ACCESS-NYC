<?php


namespace WPML\AI;

/**
 * Class WCML
 * @package WPML\AI
 *
 * WCML related actions.
 */
class WCML {

	/**
	 * Things to do before single post is going to be imported.
	 */
	public function beforePostImport() {
		$this->pauseSyncHooks();
	}

	/**
	 * Things to do after single post has been imported.
	 */
	public function afterPostImport() {
		$this->resumeSyncHooks();
	}

	/**
	 * Do not delete post metas from transnalted products.
	 */
	private function pauseSyncHooks() {
		$WCMLSynchronizeProductData = $this->getWCMLSynchronizeProductData();

		if ( false !== $WCMLSynchronizeProductData ) {
			remove_action( 'deleted_post_meta', [ $WCMLSynchronizeProductData, 'delete_empty_post_meta_for_translations' ], 10, 3 );
		}
	}

	/**
	 * Resume deleting post metas from translated products.
	 */
	private function resumeSyncHooks() {
		$WCMLSynchronizeProductData = $this->getWCMLSynchronizeProductData();

		if ( false !== $WCMLSynchronizeProductData ) {
			add_action( 'deleted_post_meta', [ $this->getWCMLSynchronizeProductData(), 'delete_empty_post_meta_for_translations' ], 10, 3 );
		}

	}

	/**
	 * Return WCML_Synchronize_Product_Data object or false if WCML is not active.
	 *
	 * @return false|\WCML_Synchronize_Product_Data
	 */
	private function getWCMLSynchronizeProductData() {
		global $woocommerce_wpml;

		if ( is_object( $woocommerce_wpml )
		     && class_exists( 'WCML_Synchronize_Product_Data' )
		     && is_a( $woocommerce_wpml->sync_product_data, 'WCML_Synchronize_Product_Data' )
		) {
			return $woocommerce_wpml->sync_product_data;
		}

		return false;
	}

	/**
	 * @param int                $importId
	 * @param PMXI_Import_Record $import
	 */
	public function afterFullImport( $importId, $import ) {
		$defaultLanguage = apply_filters( 'wpml_default_language', null );
		$currentLanguage = isset( $import->options['wpml_addon']['lng'] ) ? $import->options['wpml_addon']['lng'] : apply_filters( 'wpml_current_language', null );
		if ( $currentLanguage !== $defaultLanguage ) {
			$importedProducts = $this->getImportedProducts( $importId );
			foreach ( $importedProducts as $product ) {
				$this->updateSellIds( $product->post_id, $currentLanguage, '_upsell_ids' );
				$this->updateSellIds( $product->post_id, $currentLanguage, '_crosssell_ids' );
			}
		}
	}

	/**
	 * @param int $importId
	 *
	 * @return array
	 */
	private function getImportedProducts( $importId ) {
		global $wpdb;

		$importedProductsQuery = "SELECT post_id FROM {$wpdb->prefix}pmxi_posts
    								LEFT JOIN {$wpdb->posts} AS p ON p.ID = {$wpdb->prefix}pmxi_posts.post_id
    								WHERE import_id = %d
        							AND p.post_type = 'product'";

		return $wpdb->get_results( $wpdb->prepare( $importedProductsQuery, $importId ) ) ?: [];
	}

	/**
	 * @param int    $postId
	 * @param string $currentLanguage
	 * @param string $kind
	 */
	private function updateSellIds( $postId, $currentLanguage, $kind ) {
		$xsellIds = \get_post_meta( $postId, $kind, true );
		if ( is_array( $xsellIds ) ) {
			foreach( $xsellIds as $index => $idToUpdate ) {
				$xsellIds[$index] = apply_filters( 'wpml_object_id', $idToUpdate, 'product', true, $currentLanguage );
			}
			\update_post_meta( $postId, $kind, $xsellIds );
		}
	}

}