<?php

namespace ACFML\Convertable;

class LinkFieldData implements \WPML_ACF_Convertable {

	/**
	 * Converts 'title' and 'url' in ACF Link field value to translated version.
	 *
	 * @param \WPML_ACF_Field $acf_field
	 *
	 * @return array|mixed|object|string
	 */
	public function convert( \WPML_ACF_Field $acf_field ) {
		$cameSerialized = is_serialized( $acf_field->meta_value );

		$dataUnpacked = (array) maybe_unserialize( $acf_field->meta_value );

		$translatedPageId = $this->getTranslatedPageId( $dataUnpacked, $acf_field );

		if ( is_numeric( $translatedPageId ) ) {
			$translatedPage = get_post( $translatedPageId );
		}

		if ( isset( $translatedPage->post_title, $translatedPage->ID ) ) {
			$dataUnpacked['title'] = $translatedPage->post_title;
			$dataUnpacked['url']   = $this->getPermalink( $translatedPage->ID, $acf_field->target_lang );
		}

		if ( $cameSerialized ) {
			$dataUnpacked = maybe_serialize( $dataUnpacked );
		}

		return $dataUnpacked;
	}

	/**
	 * @param array           $dataUnpacked
	 * @param \WPML_ACF_Field $acf_field
	 *
	 * @return int|false|void
	 */
	private function getTranslatedPageId( array $dataUnpacked, \WPML_ACF_Field $acf_field ) {
		$translatedPageId = false;

		if ( isset( $acf_field->target_lang ) ) {
			$targetLang = $acf_field->target_lang;
		} elseif ( isset( $_POST['lang'] ) ) {
			$targetLang = $_POST['lang'];
		}

		if ( isset( $dataUnpacked['url'], $targetLang ) ) {
			$pageId = url_to_postid( $dataUnpacked['url'] );
			if ( $pageId > 0 ) {
				$translatedPageId = apply_filters( 'wpml_object_id', $pageId, get_post_type( $pageId ), false, $targetLang );

			} elseif ( 0 === $pageId ) {
				$translatedPageId = $this->handleFrontPageCase( $dataUnpacked['url'], $targetLang );
			}
		}
		return $translatedPageId;
	}

	/**
	 * For URL to the front page, returns ID of translated version of this page.
	 *
	 * @param string $url
	 * @param string $languageCodeTo
	 *
	 * @return bool|integer
	 */
	private function handleFrontPageCase( $url, $languageCodeTo ) {
		$pageOnFront = get_option( 'page_on_front' );
		if ( $pageOnFront > 0 ) {
			if ( get_permalink( $pageOnFront ) === $url ) {
				return apply_filters( 'wpml_object_id', $pageOnFront, get_post_type( $pageOnFront ), false, $languageCodeTo );
			}
		}
		return false;
	}

	/**
	 * Get permalink for the post in target language.
	 *
	 * @param int    $ID         Post ID.
	 * @param string $targetLang Target language.
	 *
	 * @return false|string
	 */
	private function getPermalink( $ID, $targetLang ) {
		$currentLang = apply_filters( 'wpml_current_language', null );
		if ( $currentLang !== $targetLang ) {
			do_action( 'wpml_switch_language', $targetLang );
		}
		$permalink = get_permalink( $ID );
		if ( $currentLang !== $targetLang ) {
			do_action( 'wpml_switch_language', $currentLang );
		}
		return $permalink;
	}
}
